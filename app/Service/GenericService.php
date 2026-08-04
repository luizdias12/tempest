<?php

namespace App\Service;

use App\Model\Mysql\GenericModel;

class GenericService
{
    public static function listarLocais()
    {
        return GenericModel::listarLocais();
    }

    public static function obterTemaUsuario(string $cpf)
    {
        return GenericModel::obterTemaUsuario($cpf);
    }
}