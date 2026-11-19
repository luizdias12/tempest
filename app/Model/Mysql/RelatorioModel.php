<?php

namespace App\Model\Mysql;

use App\Core\DB;

class RelatorioModel
{
    /**
     * Chamados resolvidos agrupados por responsável (interno ou externo).
     * Sem data informada considera todo o histórico.
     *
     * @return list<array{tecnico:string,total:int}>
     */
    public static function resolvidosPorTecnico(?string $data = null): array
    {
        $sql = "
            SELECT COALESCE(f.nome, fe.nome, 'Sem responsável') AS tecnico, COUNT(*) AS total
            FROM helpdesk h
            LEFT JOIN func f ON f.cpf = h.id_resp
            LEFT JOIN func_externo fe ON fe.cpf = h.id_resp
            WHERE h.status = 'R'
        ";

        $params = [];

        if ($data !== null) {
            $inicio = (new \DateTimeImmutable($data))->format('Y-m-d');
            $fim = (new \DateTimeImmutable($data))->modify('+1 day')->format('Y-m-d');

            $sql .= " AND h.dt_solucao >= :inicio AND h.dt_solucao < :fim";
            $params = ['inicio' => $inicio, 'fim' => $fim];
        }

        $sql .= "
            GROUP BY COALESCE(f.nome, fe.nome, 'Sem responsável')
            ORDER BY total DESC, tecnico ASC
        ";

        $rows = DB::select($sql, $params, 'mysql');

        return array_map(
            static fn(array $r): array => [
                'tecnico' => (string) $r['tecnico'],
                'total' => (int) $r['total'],
            ],
            $rows
        );
    }
}
