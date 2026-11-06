<?php

namespace App\Model\Mysql;

use App\Core\DB;

class SlaStatusModel
{
    public static function listarTodos(): array
    {
        return DB::select("
            SELECT ss.id, ss.sla_id, ss.status_id, ss.contabiliza_tempo, b.nome AS sla_nome
            FROM sla_status ss
            LEFT JOIN sla_base b ON b.id = ss.sla_id
            ORDER BY b.nome, ss.status_id
        ", [], 'mysql');
    }

    public static function porSla(int $slaId): array
    {
        return DB::select("
            SELECT id, sla_id, status_id, contabiliza_tempo
            FROM sla_status
            WHERE sla_id = :sla_id
            ORDER BY status_id
        ", ['sla_id' => $slaId], 'mysql');
    }

    public static function buscar(int $id): ?array
    {
        return DB::first("
            SELECT id, sla_id, status_id, contabiliza_tempo
            FROM sla_status
            WHERE id = :id
        ", ['id' => $id], 'mysql');
    }

    public static function buscarPorSlaStatus(int $slaId, int $statusId): ?array
    {
        return DB::first("
            SELECT id, sla_id, status_id, contabiliza_tempo
            FROM sla_status
            WHERE sla_id = :sla_id AND status_id = :status_id
        ", ['sla_id' => $slaId, 'status_id' => $statusId], 'mysql');
    }

    public static function inserir(array $dados): ?int
    {
        return DB::insert('sla_status', $dados, 'mysql');
    }

    public static function atualizar(int $id, array $dados): bool
    {
        return DB::update('sla_status', 'id', $id, $dados, 'mysql');
    }

    public static function excluir(int $id): bool
    {
        $stmt = DB::connect('mysql')->prepare("DELETE FROM sla_status WHERE id = :id");

        return $stmt->execute(['id' => $id]);
    }
}