<?php

namespace App\Model\Mysql;

use App\Core\DB;

class RelatorioModel
{
    /**
     * Chamados resolvidos e em andamento por responsável com permissão de suporte.
     * Sem data informada considera todo o histórico.
     *
     * @return list<array{tecnico:string,resolvidos:int,abertos:int}>
     */
    public static function resolvidosPorTecnico(?string $data = null): array
    {
        $sql = "
            WITH
            parametros AS (
                SELECT CAST(:data AS DATE) AS data_filtro
            ),
            resolvidos AS (
                SELECT
                    h.id_resp,
                    COUNT(*) AS resolvidos
                FROM helpdesk h
                CROSS JOIN parametros p
                WHERE h.status = 'R'
                  AND (
                      p.data_filtro IS NULL
                      OR (
                          h.dt_solucao >= p.data_filtro
                          AND h.dt_solucao < DATE_ADD(p.data_filtro, INTERVAL 1 DAY)
                      )
                  )
                GROUP BY h.id_resp
            ),
            abertos AS (
                SELECT
                    h.id_resp,
                    COUNT(*) AS abertos
                FROM helpdesk h
                CROSS JOIN parametros p
                WHERE h.status NOT IN ('A', 'R', 'C')
                  /*AND (
                      p.data_filtro IS NULL
                      OR (
                          h.dt_abertura >= p.data_filtro
                          AND h.dt_abertura < DATE_ADD(p.data_filtro, INTERVAL 1 DAY)
                      )
                  )*/
                GROUP BY h.id_resp
            ),
            tecnicos AS (
                SELECT id_resp
                FROM helpdesk
                GROUP BY id_resp
            )
            SELECT
                COALESCE(f.nome, fe.nome) AS tecnico,
                COALESCE(r.resolvidos, 0) AS resolvidos,
                COALESCE(a.abertos, 0) AS abertos
            FROM tecnicos t
            LEFT JOIN resolvidos r
                ON r.id_resp = t.id_resp
            LEFT JOIN abertos a
                ON a.id_resp = t.id_resp
            LEFT JOIN func f
                ON f.cpf = t.id_resp
            LEFT JOIN func_externo fe
                ON fe.cpf = t.id_resp
            INNER JOIN usuarios u
                ON u.cpf = f.cpf
                AND u.suporte = 'S'
            ORDER BY r.resolvidos DESC, a.abertos DESC, tecnico
        ";

        $rows = DB::select($sql, ['data' => $data], 'mysql');

        return array_map(
            static fn(array $r): array => [
                'tecnico' => (string) $r['tecnico'],
                'resolvidos' => (int) $r['resolvidos'],
                'abertos' => (int) $r['abertos'],
            ],
            $rows
        );
    }
}
