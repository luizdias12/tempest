<?php

namespace App\Model\Mysql;

use App\Core\DB;
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

    public static function existe(string $cpf): bool
    {
        $row = QueryBuilder::table('usuarios', 'mysql')
            ->select('cpf')
            ->where('cpf', $cpf)
            ->first();
        return $row !== null;
    }

    public static function getUsuario(string $cpf): array|null
    {
        return QueryBuilder::table('usuarios', 'mysql')
            ->select('usuario')
            ->where('cpf', $cpf)
            ->first();
    }

    public static function existeLogin(string $usuario): bool
    {
        $row = QueryBuilder::table('usuarios', 'mysql')
            ->select('usuario')
            ->where('usuario', $usuario)
            ->first();
        return $row !== null;
    }

    public static function criar(array $data): bool
    {
        return DB::insert(
            'usuarios',
            array_merge([
                'admin' => 'N',
                'ativo' => 'S',
                'bloqueado' => 'N',
                'errosenha' => 0,
            ], $data),
            'mysql'
        ) !== null;
    }

    public static function redefinirSenha(string $cpf, string $senhaMd5): bool
    {
        return DB::update('usuarios', 'cpf', $cpf, ['senha' => $senhaMd5], 'mysql');
    }
}
