<?php

namespace App\Model\Mysql;

use App\Core\DB;

class SlaBaseModel
{
    public static function listar(): array
    {
        return DB::select("
            SELECT id, nome, descricao, ativo, criado_em, atualizado_em
            FROM sla_base
            ORDER BY nome ASC
        ", [], 'mysql');
    }

    public static function listarAtivas(): array
    {
        return DB::select("
            SELECT id, nome
            FROM sla_base
            WHERE ativo = 1
            ORDER BY nome ASC
        ", [], 'mysql');
    }

    public static function buscar(int $id): ?array
    {
        return DB::first("
            SELECT id, nome, descricao, ativo
            FROM sla_base
            WHERE id = :id
        ", ['id' => $id], 'mysql');
    }

    public static function inserir(array $dados): ?int
    {
        return DB::insert('sla_base', $dados, 'mysql');
    }

    public static function atualizar(int $id, array $dados): bool
    {
        return DB::update('sla_base', 'id', $id, $dados, 'mysql');
    }

    public static function excluir(int $id): bool
    {
        $stmt = DB::connect('mysql')->prepare("DELETE FROM sla_base WHERE id = :id");

        return $stmt->execute(['id' => $id]);
    }

    public static function countRegras(int $id): int
    {
        $row = DB::first("SELECT COUNT(*) AS total FROM sla_regra WHERE sla_id = :id", ['id' => $id], 'mysql');

        return (int) ($row['total'] ?? 0);
    }
}