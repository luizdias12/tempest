<?php

namespace App\Service;

use App\Model\Mysql\UsuarioModel;

class UsuarioService
{
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

    public static function existeLogin(string $usuario): bool
    {
        return UsuarioModel::existeLogin($usuario);
    }

    public static function criarConta(string $cpf, string $usuario, string $email, string $senha, ?int $filialCad = 0, ?string $ramal = null, ?string $corporativo = null, ?int $idSetor = null): bool
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
}