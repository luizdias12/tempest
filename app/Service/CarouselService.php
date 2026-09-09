<?php

namespace App\Service;

use App\Model\Mysql\CarouselModel;
use RuntimeException;

class CarouselService
{
    public const MAX_SIZE = 104857600;

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov'];

    public static function slidesAtivos(): array
    {
        $slides = [];

        foreach (CarouselModel::listarAtivos() as $row) {
            if (empty($row['filename'])) {
                continue;
            }

            $arquivo = self::caminhoArquivo($row['filename']);

            if (!is_file($arquivo)) {
                continue;
            }

            $tipo = self::tipoArquivo($row['filename']);

            if ($tipo === null) {
                continue;
            }

            $slides[] = [
                'url' => asset('assets/caroussel/' . $row['filename']),
                'tipo' => $tipo,
                'link' => $row['link'] ?? null,
            ];
        }

        return $slides;
    }

    public static function listarTodos(): array
    {
        $slides = [];

        foreach (CarouselModel::listarTodos() as $row) {
            $arquivo = self::caminhoArquivo($row['filename']);
            $tipo = self::tipoArquivo($row['filename']);

            $slides[] = [
                'id' => (int) $row['id'],
                'filename' => $row['filename'],
                'ord' => (int) $row['ord'],
                'dtinicio' => $row['dtinicio'],
                'dtfim' => $row['dtfim'],
                'ativo' => $row['ativo'],
                'tipo' => $tipo,
                'existe' => is_file($arquivo),
                'url' => $tipo !== null && is_file($arquivo)
                    ? asset('assets/caroussel/' . $row['filename'])
                    : null,
                'link' => $row['link'] ?? null,
            ];
        }

        return $slides;
    }

    public static function salvarNovo(array $file, ?string $dtinicio, ?string $dtfim, string $ativo, ?string $link = null): string
    {
        if (empty($file['name'])) {
            throw new RuntimeException('Selecione um arquivo para o slide.');
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Falha no upload do arquivo.');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_SIZE) {
            throw new RuntimeException('Tamanho máximo do arquivo deve ser de 100 MB.');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Tipo de arquivo não permitido. Use imagem (jpg, png, gif, webp) ou vídeo (mp4, webm, mov).');
        }

        $dir = basePath('public/assets/caroussel/');

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Falha ao criar o diretório de upload.');
        }

        $nome = substr(sha1((string) $file['name'] . microtime()), 7, 14) . '.' . $ext;
        $destino = $dir . $nome;

        if (!move_uploaded_file((string) $file['tmp_name'], $destino)) {
            throw new RuntimeException('Falha ao salvar o arquivo em disco.');
        }

        $id = CarouselModel::inserir([
            'filename' => $nome,
            'ord' => CarouselModel::proximaOrdem(),
            'dtinicio' => self::dataOuNull($dtinicio),
            'dtfim' => self::dataOuNull($dtfim),
            'ativo' => $ativo === 'S' ? 'S' : 'N',
            'link' => self::linkOuNull($link),
        ]);

        if ($id === null) {
            @unlink($destino);
            throw new RuntimeException('Falha ao cadastrar o slide.');
        }

        return 'Slide cadastrado com sucesso.';
    }

    public static function alternarAtivo(int $id, string $ativo): string
    {
        $row = CarouselModel::buscar($id);

        if ($row === null) {
            throw new RuntimeException('Slide não encontrado.');
        }

        $novoAtivo = $ativo === 'S' ? 'S' : 'N';

        CarouselModel::alternarAtivo($id, $novoAtivo);

        return $novoAtivo === 'S' ? 'Slide ativado.' : 'Slide inativado.';
    }

    public static function subir(int $id): string
    {
        return self::moverOrdem($id, 1);
    }

    public static function descer(int $id): string
    {
        return self::moverOrdem($id, -1);
    }

    public static function excluir(int $id): string
    {
        $row = CarouselModel::excluir($id);

        if ($row === null) {
            throw new RuntimeException('Slide não encontrado.');
        }

        $arquivo = self::caminhoArquivo($row['filename']);

        if (is_file($arquivo)) {
            @unlink($arquivo);
        }

        return 'Slide excluído.';
    }

    private static function moverOrdem(int $id, int $sentido): string
    {
        $origem = CarouselModel::buscar($id);

        if ($origem === null) {
            throw new RuntimeException('Slide não encontrado.');
        }

        if ($sentido > 0) {
            $vizinho = CarouselModel::vizinhoPorOrdem($origem['ord'], '<', 'DESC');
        } else {
            $vizinho = CarouselModel::vizinhoPorOrdem($origem['ord'], '>', 'ASC');
        }

        if ($vizinho === null) {
            throw new RuntimeException($sentido > 0 ? 'O slide já está na primeira posição.' : 'O slide já está na última posição.');
        }

        CarouselModel::trocarOrdem($origem['id'], $vizinho['id']);

        return 'Ordem atualizada.';
    }

    private static function caminhoArquivo(string $filename): string
    {
        return basePath('public/assets/caroussel/' . $filename);
    }

    private static function tipoArquivo(string $filename): ?string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'imagem';
        }

        if (in_array($ext, ['mp4', 'webm', 'mov'], true)) {
            return 'video';
        }

        return null;
    }

    private static function dataOuNull(?string $data): ?string
    {
        $data = trim((string) $data);

        return ($data !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) ? $data : null;
    }

    private static function linkOuNull(?string $link): ?string
    {
        $link = trim((string) $link);

        if ($link === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $link) !== 1 && $link[0] !== '/') {
            throw new RuntimeException('Link inválido. Use https://... ou um caminho relativo (ex.: /documentos).');
        }

        return $link;
    }
}