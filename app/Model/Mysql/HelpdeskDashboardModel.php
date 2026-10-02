<?php

namespace App\Model\Mysql;

use App\Core\DB;

/**
 * Agregações do dashboard de Helpdesk.
 *
 * Observações de desempenho: `helpdesk` tem ~19 mil linhas e só três índices
 * (PRIMARY, cpf_ab e (status, id)). Não há índice em dt_abertura/dt_solucao,
 * então toda agregação por período paga uma varredura completa da tabela — a
 * 19 mil linhas isso é irrelevante, mas não espera o mesmo desenho se a base
 * crescer duas ordens de grandeza.
 *
 * Todos os LEFT JOINs são intencionais: `HelpdeskModel::chamadosAbertos` usa
 * JOIN em grupo/funcao/status e com isso descarta chamado sem grupo ou sem
 * função preenchida, o que faria os totais do dashboard não baterem com a fila.
 */
class HelpdeskDashboardModel
{
    /** Encerrados: R = Resolvido, C = Cancelado. */
    public const FECHADOS = ['R', 'C'];

    /** Catálogo de status (tabela `status`). 'A' = Aberto. */
    public const NAO_INICIADO = ['A'];

    /** Ball is with the support. */
    public const EM_ATENDIMENTO = ['EA', 'D'];

    /** Ball is with someone else: support, third party or the requester. */
    public const AGUARDANDO_TERCEIROS = ['E', 'PS', 'PU', 'ST'];

    /** Abertos = tudo que não foi encerrado. */
    public const ABERTOS = ['A', 'EA', 'D', 'E', 'PS', 'PU', 'ST'];

    private const LISTA_FECHADOS = "'R','C'";
    private const LISTA_NAO_INICIADO = "'A'";
    private const LISTA_EM_ATENDIMENTO = "'EA','D'";
    private const LISTA_AGUARDANDO = "'E','PS','PU','ST'";

    /** Quantos dias um chamado aberto pode ficar antes de contar como envelhecido. */
    public const DIAS_ENVELHECIDO = 7;

    /**
     * Cards de topo, fechamento do período e tempo médio, tudo em uma varredura.
     *
     * @return array{abertos:int,nao_iniciados:int,em_atendimento:int,aguardando_terceiros:int,envelhecidos:int,resolvidos:int,cancelados:int,tempo_medio_min:?float}
     */
    public static function kpis(string $de, string $ate): array
    {
        $row = DB::first("
            SELECT
                SUM(CASE WHEN h.status NOT IN (" . self::LISTA_FECHADOS . ") THEN 1 ELSE 0 END) AS abertos,
                SUM(CASE WHEN h.status IN (" . self::LISTA_NAO_INICIADO . ") THEN 1 ELSE 0 END) AS nao_iniciados,
                SUM(CASE WHEN h.status IN (" . self::LISTA_EM_ATENDIMENTO . ") THEN 1 ELSE 0 END) AS em_atendimento,
                SUM(CASE WHEN h.status IN (" . self::LISTA_AGUARDANDO . ") THEN 1 ELSE 0 END) AS aguardando_terceiros,
                SUM(CASE WHEN h.status NOT IN (" . self::LISTA_FECHADOS . ")
                    AND h.dt_abertura < (NOW() - INTERVAL " . self::DIAS_ENVELHECIDO . " DAY) THEN 1 ELSE 0 END) AS envelhecidos,
                SUM(CASE WHEN h.status = 'R' AND h.dt_solucao BETWEEN :res_de AND :res_ate THEN 1 ELSE 0 END) AS resolvidos,
                SUM(CASE WHEN h.status = 'C' AND h.dt_solucao BETWEEN :can_de AND :can_ate THEN 1 ELSE 0 END) AS cancelados,
                AVG(CASE WHEN h.status = 'R' AND h.dt_solucao BETWEEN :tempo_de AND :tempo_ate
                    THEN TIMESTAMPDIFF(MINUTE, h.dt_abertura, h.dt_solucao) END) AS tempo_medio_min
            FROM helpdesk h
        ", [
            'res_de' => $de,
            'res_ate' => $ate,
            'can_de' => $de,
            'can_ate' => $ate,
            'tempo_de' => $de,
            'tempo_ate' => $ate,
        ], 'mysql');

        $tempo = isset($row['tempo_medio_min']) ? (float) $row['tempo_medio_min'] : null;

        return [
            'abertos' => (int) ($row['abertos'] ?? 0),
            'nao_iniciados' => (int) ($row['nao_iniciados'] ?? 0),
            'em_atendimento' => (int) ($row['em_atendimento'] ?? 0),
            'aguardando_terceiros' => (int) ($row['aguardando_terceiros'] ?? 0),
            'envelhecidos' => (int) ($row['envelhecidos'] ?? 0),
            'resolvidos' => (int) ($row['resolvidos'] ?? 0),
            'cancelados' => (int) ($row['cancelados'] ?? 0),
            'tempo_medio_min' => $tempo,
        ];
    }

    /**
     * Destino dos chamados abertos no período: em que fase estão hoje.
     *
     * @return array{nao_iniciados:int,em_atendimento:int,aguardando_terceiros:int,encerrados:int}
     */
    public static function bucketsPeriodo(string $de, string $ate): array
    {
        $row = DB::first("
            SELECT
                SUM(CASE WHEN h.status IN (" . self::LISTA_NAO_INICIADO . ") THEN 1 ELSE 0 END) AS nao_iniciados,
                SUM(CASE WHEN h.status IN (" . self::LISTA_EM_ATENDIMENTO . ") THEN 1 ELSE 0 END) AS em_atendimento,
                SUM(CASE WHEN h.status IN (" . self::LISTA_AGUARDANDO . ") THEN 1 ELSE 0 END) AS aguardando_terceiros,
                SUM(CASE WHEN h.status IN (" . self::LISTA_FECHADOS . ") THEN 1 ELSE 0 END) AS encerrados
            FROM helpdesk h
            WHERE h.dt_abertura BETWEEN :de AND :ate
        ", ['de' => $de, 'ate' => $ate], 'mysql');

        return [
            'nao_iniciados' => (int) ($row['nao_iniciados'] ?? 0),
            'em_atendimento' => (int) ($row['em_atendimento'] ?? 0),
            'aguardando_terceiros' => (int) ($row['aguardando_terceiros'] ?? 0),
            'encerrados' => (int) ($row['encerrados'] ?? 0),
        ];
    }

    /**
     * Abertos e resolvidos por dia, com as datas sem movimento preenchidas em
     * zero para o eixo do gráfico não ficar com buracos.
     *
     * @return list<array{data:string,abertos:int,resolvidos:int}>
     */
    public static function serieDiaria(string $de, string $ate): array
    {
        $rows = DB::select("
            SELECT d.data,
                   SUM(d.abertos) AS abertos,
                   SUM(d.resolvidos) AS resolvidos
            FROM (
                SELECT DATE(h.dt_abertura) AS data, COUNT(*) AS abertos, 0 AS resolvidos
                FROM helpdesk h
                WHERE h.dt_abertura BETWEEN ? AND ?
                GROUP BY DATE(h.dt_abertura)
                UNION ALL
                SELECT DATE(h.dt_solucao) AS data, 0 AS resolvidos, COUNT(*)
                FROM helpdesk h
                WHERE h.status = 'R'
                AND h.dt_solucao BETWEEN ? AND ?
                GROUP BY DATE(h.dt_solucao)
            ) d
            GROUP BY d.data
            ORDER BY d.data ASC
        ", [$de, $ate, $de, $ate], 'mysql');

        $porDia = [];
        foreach ($rows as $row) {
            $porDia[(string) $row['data']] = [
                'abertos' => (int) $row['abertos'],
                'resolvidos' => (int) $row['resolvidos'],
            ];
        }

        $serie = [];
        $dia = new \DateTimeImmutable($de);
        $fim = new \DateTimeImmutable($ate);

        while ($dia <= $fim) {
            $chave = $dia->format('Y-m-d');
            $serie[] = [
                'data' => $chave,
                'abertos' => $porDia[$chave]['abertos'] ?? 0,
                'resolvidos' => $porDia[$chave]['resolvidos'] ?? 0,
            ];
            $dia = $dia->modify('+1 day');
        }

        return $serie;
    }

    /** @return list<array{grupo:string,total:int}> */
    public static function porGrupo(int $limite = 8): array
    {
        $rows = DB::select("
            SELECT COALESCE(g.descricao, 'Sem grupo') AS grupo, COUNT(*) AS total
            FROM helpdesk h
            LEFT JOIN grupo g ON g.id_grupo = h.idgrupo
            WHERE h.status NOT IN (" . self::LISTA_FECHADOS . ")
            GROUP BY COALESCE(g.descricao, 'Sem grupo')
            ORDER BY total DESC, grupo ASC
            LIMIT {$limite}
        ", [], 'mysql');

        return array_map(
            static fn(array $r): array => ['grupo' => (string) $r['grupo'], 'total' => (int) $r['total']],
            $rows
        );
    }

    /** @return list<array{responsavel:string,total:int}> */
    public static function porResponsavel(int $limite = 8): array
    {
        $rows = DB::select("
            SELECT COALESCE(f.nome, fe.nome, 'Sem responsável') AS responsavel, COUNT(*) AS total
            FROM helpdesk h
            LEFT JOIN func f ON f.cpf = h.id_resp
            LEFT JOIN func_externo fe ON fe.cpf = h.id_resp
            WHERE h.status NOT IN (" . self::LISTA_FECHADOS . ")
            GROUP BY COALESCE(f.nome, fe.nome, 'Sem responsável')
            ORDER BY total DESC, responsavel ASC
            LIMIT {$limite}
        ", [], 'mysql');

        return array_map(
            static fn(array $r): array => ['responsavel' => (string) $r['responsavel'], 'total' => (int) $r['total']],
            $rows
        );
    }

    /**
     * Fila aberta agrupada por status, com o rótulo vindo do catálogo `status`
     * (nada de nome hardcoded aqui).
     *
     * @return list<array{status:string,status_desc:string,total:int}>
     */
    public static function porStatus(): array
    {
        $rows = DB::select("
            SELECT h.status AS status,
                   COALESCE(st.descricao, h.status) AS status_desc,
                   COUNT(*) AS total
            FROM helpdesk h
            LEFT JOIN status st ON st.status = h.status
            WHERE h.status NOT IN (" . self::LISTA_FECHADOS . ")
            GROUP BY h.status, COALESCE(st.descricao, h.status)
            ORDER BY total DESC, status ASC
        ", [], 'mysql');

        return array_map(
            static fn(array $r): array => [
                'status' => (string) $r['status'],
                'status_desc' => (string) $r['status_desc'],
                'total' => (int) $r['total'],
            ],
            $rows
        );
    }

    /**
     * Chamados abertos mais antigos, para a fila não estagnar sem ninguém ver.
     *
     * @return list<array<string,mixed>>
     */
    public static function maisAntigos(int $limite = 10): array
    {
        return DB::select("
            SELECT h.id,
                   h.chapa,
                   h.cab_problema,
                   h.status,
                   COALESCE(st.descricao, h.status) AS status_desc,
                   COALESCE(g.descricao, 'Sem grupo') AS grupo,
                   COALESCE(f.nome, fe.nome, 'Sem responsável') AS responsavel,
                   h.dt_abertura,
                   DATEDIFF(NOW(), h.dt_abertura) AS dias_aberto
            FROM helpdesk h
            LEFT JOIN status st ON st.status = h.status
            LEFT JOIN grupo g ON g.id_grupo = h.idgrupo
            LEFT JOIN func f ON f.cpf = h.id_resp
            LEFT JOIN func_externo fe ON fe.cpf = h.id_resp
            WHERE h.status NOT IN (" . self::LISTA_FECHADOS . ")
            ORDER BY h.dt_abertura ASC, h.id ASC
            LIMIT {$limite}
        ", [], 'mysql');
    }
}
