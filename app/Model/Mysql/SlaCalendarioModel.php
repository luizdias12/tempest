<?php

namespace App\Model\Mysql;

use App\Core\DB;

class SlaCalendarioModel
{
    public static function listar(): array
    {
        return DB::select("
            SELECT id, nome, segunda, terca, quarta, quinta, sexta, sabado, domingo,
                   hora_inicio, hora_fim, intervalo_inicio, intervalo_fim, ativo, criado_em, atualizado_em
            FROM sla_calendario
            ORDER BY nome ASC
        ", [], 'mysql');
    }

    public static function listarAtivos(): array
    {
        return DB::select("
            SELECT id, nome
            FROM sla_calendario
            WHERE ativo = 1
            ORDER BY nome ASC
        ", [], 'mysql');
    }

    public static function buscar(int $id): ?array
    {
        return DB::first("
            SELECT id, nome, segunda, terca, quarta, quinta, sexta, sabado, domingo,
                   hora_inicio, hora_fim, intervalo_inicio, intervalo_fim, ativo
            FROM sla_calendario
            WHERE id = :id
        ", ['id' => $id], 'mysql');
    }

    public static function inserir(array $dados): ?int
    {
        return DB::insert('sla_calendario', $dados, 'mysql');
    }

    public static function atualizar(int $id, array $dados): bool
    {
        return DB::update('sla_calendario', 'id', $id, $dados, 'mysql');
    }

    public static function excluir(int $id): bool
    {
        $stmt = DB::connect('mysql')->prepare("DELETE FROM sla_calendario WHERE id = :id");

        return $stmt->execute(['id' => $id]);
    }
}