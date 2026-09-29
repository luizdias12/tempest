<?php

namespace App\Model\Mysql;

use App\Core\DB;

class SlaRegraModel
{
    public static function listar(): array
    {
        return DB::select("
            SELECT r.id, r.sla_id, r.grupo_id, r.subgrupo_id, r.calendario_id,
                   r.prazo_primeira_resposta_min, r.prazo_resolucao_min, r.ordem, r.ativo,
                   b.nome AS sla_nome, c.nome AS calendario_nome,
                   g.descricao AS grupo_descricao, sg.descricao AS subgrupo_descricao
            FROM sla_regra r
            LEFT JOIN sla_base b ON b.id = r.sla_id
            LEFT JOIN sla_calendario c ON c.id = r.calendario_id
            LEFT JOIN grupo g ON g.id_grupo = r.grupo_id
            LEFT JOIN subgrupo sg ON sg.id = r.subgrupo_id
            ORDER BY r.sla_id, r.ordem, r.id
        ", [], 'mysql');
    }

    public static function buscar(int $id): ?array
    {
        return DB::first("
            SELECT r.id, r.sla_id, r.grupo_id, r.subgrupo_id, r.calendario_id,
                   r.prazo_primeira_resposta_min, r.prazo_resolucao_min, r.ordem, r.ativo
            FROM sla_regra r
            WHERE r.id = :id
        ", ['id' => $id], 'mysql');
    }

    public static function inserir(array $dados): ?int
    {
        return DB::insert('sla_regra', $dados, 'mysql');
    }

    public static function atualizar(int $id, array $dados): bool
    {
        return DB::update('sla_regra', 'id', $id, $dados, 'mysql');
    }

    public static function excluir(int $id): bool
    {
        $stmt = DB::connect('mysql')->prepare("DELETE FROM sla_regra WHERE id = :id");

        return $stmt->execute(['id' => $id]);
    }
}