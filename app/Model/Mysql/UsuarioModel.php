<?php

namespace App\Model\Mysql;

use App\Core\QueryBuilder;

class UsuarioModel
{
    public static function all(): array|null
    {
        return QueryBuilder::table('usuario', 'mysql')
            ->select('cpf', 'nome')
            ->orderBy('nome', 'ASC')
            ->get();
    }
}
