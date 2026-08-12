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

    public static function getAdmin(string $cpf): string|null
    {
        $admin = QueryBuilder::table('usuarios', 'mysql')
            ->select('IFNULL(admin, "N") as admin')
            ->where('cpf', $cpf)
            ->first();
        return $admin['admin'] ?? '';
    }
}
