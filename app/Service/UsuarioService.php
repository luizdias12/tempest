<?php

namespace App\Service;

use App\Model\Mysql\UsuarioModel;

class UsuarioService
{
    public static function getAdmin(string $cpf): string|null
    {
        return UsuarioModel::getAdmin($cpf);
    }
}