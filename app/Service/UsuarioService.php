<?php

namespace App\Service;

use App\Model\Mysql\UsuarioModel;
use RuntimeException;

class UsuarioService
{
    public const FOTO_MAX = 2097152;

    private const FOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private const FOTO_MIMES_DISPONIVEIS = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public static function salvarFoto(array $file): ?string
    {
        if (empty($file['name'])) {
            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::erroFoto($error));
        }

        if ((int) ($file['size'] ?? 0) > self::FOTO_MAX) {
            throw new RuntimeException('Tamanho máximo da foto deve ser de 2 MB.');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, self::FOTO_EXTENSIONS, true)) {
            throw new RuntimeException('Formato de imagem não permitido. Use jpg, png, gif ou webp.');
        }

        $info = getimagesize((string) $file['tmp_name']);

        if ($info === false || !in_array($info['mime'] ?? '', self::FOTO_MIMES_DISPONIVEIS, true)) {
            throw new RuntimeException('Arquivo inválido: envie uma imagem válida.');
        }

        $dir = basePath('public/assets/fotos/');

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Falha ao criar o diretório de fotos.');
        }

        $nome = substr(sha1((string) $file['name'] . microtime()), 7, 14) . '.' . $ext;

        self::redimensionar((string) $file['tmp_name'], $dir . $nome, $ext);

        return $nome;
    }

    public static function trocarFoto(string $cpf, array $file): bool
    {
        $foto = self::salvarFoto($file);

        if ($foto === null) {
            return false;
        }

        $antiga = UsuarioModel::getFoto($cpf);

        if (!UsuarioModel::atualizarFoto($cpf, $foto)) {
            @unlink(basePath('public/assets/fotos/' . $foto));
            return false;
        }

        if ($antiga !== null && $antiga !== '' && $antiga !== $foto) {
            @unlink(basePath('public/assets/fotos/' . $antiga));
        }

        return true;
    }

    public static function getFoto(string $cpf): ?string
    {
        $foto = UsuarioModel::getFoto($cpf);

        return ($foto !== null && $foto !== '') ? $foto : null;
    }

    public static function fotosPorCpfs(array $cpfs): array
    {
        $cpfs = array_values(array_filter(array_map('strval', $cpfs)));

        if ($cpfs === []) {
            return [];
        }

        $rows = \App\Core\QueryBuilder::table('usuarios', 'mysql')
            ->select('cpf', 'foto')
            ->whereIn('cpf', $cpfs)
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $foto = (string) ($row['foto'] ?? '');
            if ($foto !== '') {
                $map[$row['cpf']] = $foto;
            }
        }

        return $map;
    }

    public static function getAdmin(string $cpf): string|null
    {
        return UsuarioModel::getAdmin($cpf);
    }

    public static function existe(string $cpf): bool
    {
        return UsuarioModel::existe($cpf);
    }

    public static function getUsuario(string $cpf): array|null
    {
        return UsuarioModel::getUsuario($cpf);
    }

    public static function perfil(string $cpf): array|null
    {
        return UsuarioModel::perfil($cpf);
    }

    public static function atualizarPerfil(string $cpf, ?string $email = null, ?string $ramal = null, ?string $corporativo = null, ?int $idSetor = null): bool
    {
        if (!$cpf || ($email === null && $ramal === null && $idSetor === null)) {
            return false;
        }

        $data = [];

        if ($email !== null) {
            $data['email'] = trim($email) !== '' ? trim($email) : null;
        }

        if ($ramal !== null) {
            $data['ramal'] = trim($ramal) !== '' ? mb_substr(trim($ramal), 0, 5) : null;
        }

        if ($corporativo !== null) {
            $data['corporativo'] = trim($corporativo) !== '' ? mb_substr(trim($corporativo), 0, 11) : null;
        }

        if ($idSetor !== null && $idSetor > 0) {
            $data['id_setor'] = $idSetor;
        }

        if (empty($data)) {
            return false;
        }

        $data['suporte'] = ($data['id_setor'] ?? null) === 17 ? 'S' : 'N';

        return UsuarioModel::atualizarPerfil($cpf, $data);
    }

    public static function alterarSenha(string $cpf, string $senhaAtual, string $novaSenha): bool
    {
        if (!$cpf || $senhaAtual === '' || $novaSenha === '') {
            return false;
        }

        $row = \App\Core\QueryBuilder::table('usuarios', 'mysql')
            ->select('senha')
            ->where('cpf', $cpf)
            ->first();

        if ($row === null || !hash_equals((string) ($row['senha'] ?? ''), md5($senhaAtual))) {
            return false;
        }

        return UsuarioModel::redefinirSenha($cpf, md5($novaSenha));
    }

    public static function existeLogin(string $usuario): bool
    {
        return UsuarioModel::existeLogin($usuario);
    }

    public static function criarConta(string $cpf, string $usuario, string $email, string $senha, ?int $filialCad = 0, ?string $ramal = null, ?string $corporativo = null, ?int $idSetor = null, ?string $foto = null): bool
    {
        if (!$cpf || !$usuario || !$senha || mb_strlen($usuario) > 20) {
            return false;
        }

        if (self::existe($cpf) || self::existeLogin($usuario)) {
            return false;
        }

        $data = [
            'cpf' => $cpf,
            'usuario' => $usuario,
            'email' => $email !== '' ? $email : null,
            'senha' => md5($senha),
            'filial_cad' => (int) $filialCad,
        ];

        if ($ramal !== null && $ramal !== '') {
            $data['ramal'] = mb_substr($ramal, 0, 5);
        }

        if ($corporativo !== null && $corporativo !== '') {
            $data['corporativo'] = preg_replace('/\D/', '', $corporativo);
        }

        if ($idSetor !== null && $idSetor > 0) {
            $data['id_setor'] = $idSetor;
        }

        if ($foto !== null && $foto !== '') {
            $data['foto'] = $foto;
        }

        $data['suporte'] = $idSetor === 17 ? 'S' : 'N';

        return UsuarioModel::criar($data);
    }

    public static function redefinirSenha(string $cpf, string $senha): bool
    {
        if (!$cpf || !$senha) {
            return false;
        }

        return UsuarioModel::redefinirSenha($cpf, md5($senha));
    }

    private static function redimensionar(string $origem, string $destino, string $ext): void
    {
        $info = getimagesize($origem);

        if ($info === false) {
            throw new RuntimeException('Falha ao ler a imagem enviada.');
        }

        [$largura, $altura] = $info;

        $max = 500;

        if ($largura > $max || $altura > $max) {
            $escala = min($max / $largura, $max / $altura, 1);
            $novaLargura = (int) round($largura * $escala);
            $novaAltura = (int) round($altura * $escala);
        } else {
            $novaLargura = (int) $largura;
            $novaAltura = (int) $altura;
        }

        $origemImg = match ($info['mime'] ?? '') {
            'image/jpeg' => imagecreatefromjpeg($origem),
            'image/png' => imagecreatefrompng($origem),
            'image/gif' => imagecreatefromgif($origem),
            'image/webp' => imagecreatefromwebp($origem),
            default => false,
        };

        if ($origemImg === false) {
            throw new RuntimeException('Falha ao processar a imagem.');
        }

        $destinoImg = imagecreatetruecolor($novaLargura, $novaAltura);

        if ($ext === 'png' || $ext === 'webp') {
            imagealphablending($destinoImg, false);
            imagesavealpha($destinoImg, true);
        }

        imagecopyresampled($destinoImg, $origemImg, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

        $ok = match ($ext) {
            'png' => imagepng($destinoImg, $destino),
            'webp' => imagewebp($destinoImg, $destino),
            default => imagejpeg($destinoImg, $destino, 85),
        };

        imagedestroy($origemImg);
        imagedestroy($destinoImg);

        if ($ok === false) {
            @unlink($destino);
            throw new RuntimeException('Falha ao salvar a imagem em disco.');
        }
    }

    private static function erroFoto(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'A foto excede o limite de 2 MB.';
            case UPLOAD_ERR_PARTIAL:
                return 'O upload da foto foi feito parcialmente.';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhuma foto foi enviada.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Pasta temporária ausente no servidor.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Falha ao escrever a foto em disco.';
            case UPLOAD_ERR_EXTENSION:
                return 'Uma extensão do PHP interrompeu o upload.';
            default:
                return 'Erro desconhecido no upload da foto.';
        }
    }
}