<?php

namespace App\Model\Mysql;

use App\Core\DB;
use App\Core\QueryBuilder;
use App\Service\AuthService;

class HelpdeskModel
{
    public static function chamadosAbertos(
        int $page,
        int $limit,
        ?int $id = null,
        ?string $emitente = null,
        ?string $status = null,
        ?string $local = null,
        ?string $idResp = null,
        ?string $idMeu = null,
        ?bool $isSuporte = false,
        ?bool $isExterno = false
    ): array|null {
        $chamadosAbertos = QueryBuilder::table('helpdesk h', 'mysql')
            ->select(
                'h.id',
                'h.chapa',
                'COALESCE(f.nome, fe.nome) as nome',
                'h.dt_abertura',
                'h.dt_solucao',
                'h.status',
                'st.descricao as status_desc',
                'h.cab_problema',
                'h.desc_problema',
                'g.descricao as grupo',
                'sg.descricao as subgrupo',
                'o.local',
                'f2.nome as responsavel',
                'fu.nome as funcao',
                'u.ramal as ramal',
                'u.email as email',
                's.sla_sol as sla',
                'h.cpf_ab',
                'h.idgrupo',
                'h.idsubgrupo',
                'h.id_resp',
                'hc.codmotivo',
                "(SELECT MAX(hh.data_hist) FROM help_hist hh WHERE hh.id_help = h.id AND hh.status <> 'A') AS data_hist",
                'hs.date as data_status',
                "(SELECT dtview FROM help_hist hh WHERE hh.id_help = h.id AND hh.status = 'A' AND hh.view = 'S') AS dtview"
            )
            ->leftJoin('func f', 'f.cpf', '=', 'h.cpf_ab')
            ->leftJoin('func f2', 'f2.cpf', '=', 'h.id_resp')
            ->leftJoin('func_externo fe', 'fe.cpf', '=', 'h.id_resp')
            ->join('funcao fu', 'fu.codigo', '=', 'f.codfuncao')
            ->join('grupo g', 'g.id_grupo', '=', 'h.idgrupo')
            ->join('sla s', 's.id_sla', '=', 'h.sla')
            ->leftJoin('usuarios u', 'u.cpf', '=', 'h.cpf_ab')
            ->leftJoin('help_canc hc', 'hc.id_help', '=', 'h.id')
            ->leftJoinOn('help_status hs', function ($join) {
                $join->on('hs.id_help', '=', 'h.id')
                    ->onValue('hs.status', '<>', 'A');
            })
            ->joinOn('subgrupo sg', function ($join) {
                $join->on('sg.idgrupo', '=', 'g.id_grupo')
                    ->on('sg.id', '=', 'h.idsubgrupo');
            })
            ->join('status st', 'st.status', '=', 'h.status')
            ->leftJoinOn('online o', function ($join) {
                $join->on('o.cpf', '=', 'f.cpf');
            })
            ->orderBy('h.id', 'DESC')
            ->when(!$isSuporte, function ($q) {
                $cpf = AuthService::getUserCpf();

                if (!empty($cpf)) {
                    $q->where('h.cpf_ab', $cpf);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->when(!empty($id), fn($q) => $q->where('h.id', $id))
            ->when(!empty($emitente), fn($q) => $q->where('f.nome', 'LIKE', "%$emitente%"))
            ->when(!empty($status), fn($q) => $q->where('h.status', $status))
            ->when(!empty($local), fn($q) => $q->where('o.local', $local))
            ->when(!empty($idResp), fn($q) => $q->where('h.id_resp', $idResp)->whereNotNull('h.status'))
            ->when(!empty($idMeu), fn($q) => $q->where('h.cpf_ab', $idMeu)->whereNotNull('h.status'))
            ->when($isExterno === true, fn($q) => $q->where('h.id_resp', AuthService::getUserCpf()))
            ->when(empty($status) && empty($idMeu) && $isSuporte && empty($id), fn($q) => $q->whereNotIn('h.status', ['R', 'C']));

        return $chamadosAbertos->paginate($page, $limit);
    }

    public static function obterSla(int $idgrupo, int $idsubgrupo): ?int
    {
        $row = DB::first("
            SELECT id_sla
            FROM sla
            WHERE id_grupo = :grupo
            AND id_subgrupo = :subgrupo
            LIMIT 1
        ", ['grupo' => $idgrupo, 'subgrupo' => $idsubgrupo], 'mysql');

        return $row['id_sla'] ?? null;
    }

    public static function criar(array $data): ?int
    {
        return DB::insert('helpdesk', $data, 'mysql');
    }

    public static function atualizar(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        return DB::update('helpdesk', 'id', $id, $data, 'mysql');
    }

    public static function atualizaStatusChamado(int $helpId, string $status): bool
    {
        return DB::update('helpdesk', 'id', $helpId, ['status' => $status], 'mysql');
    }

    public static function obterCpfAbertura(int $id): ?string
    {
        return DB::first("SELECT cpf_ab FROM helpdesk WHERE id = :id", ['id' => $id], 'mysql')['cpf_ab'] ?? null;
    }

    public static function obterStatus(int $id): ?string
    {
        return DB::first("SELECT status FROM helpdesk WHERE id = :id", ['id' => $id], 'mysql')['status'] ?? null;
    }

    public static function registrarCancelamento(int $idHelp, int $codmotivo, string $idCanc, string $ip, string $acao): ?int
    {
        return DB::insert('help_canc', [
            'id_help' => $idHelp,
            'codmotivo' => $codmotivo,
            'id_canc' => $idCanc,
            'datacanc' => date('Y-m-d H:i:s'),
            'acao' => $acao,
            'ip' => $ip,
        ], 'mysql');
    }

    public static function upsertHelpStatus(int $idHelp, string $statusValue): bool
    {
        $stmt = DB::connect('mysql')->prepare("
            INSERT INTO help_status (id_help, status, date)
            VALUES (:id, :status, NOW())
            ON DUPLICATE KEY UPDATE status = :statusNovo, date = NOW()
        ");

        return $stmt->execute([
            'id' => $idHelp,
            'status' => $statusValue,
            'statusNovo' => $statusValue,
        ]);
    }

    public static function obterEmailsNotificacao(int $id, ?string $idUsuExcluir = null): array
    {
        $row = DB::first("
            SELECT ab.email AS email_abertura,
                   ab.cpf AS cpf_abertura,
                   resp.email AS email_responsavel,
                   resp.cpf AS cpf_responsavel
            FROM helpdesk h
            LEFT JOIN usuarios ab ON ab.cpf = h.cpf_ab
            LEFT JOIN usuarios resp ON resp.cpf = h.id_resp
            WHERE h.id = :id
        ", ['id' => $id], 'mysql');

        if ($row === null) {
            return [];
        }

        $emails = [];

        if ($idUsuExcluir === null || $idUsuExcluir !== ($row['cpf_abertura'] ?? null)) {
            $emails[] = $row['email_abertura'] ?? '';
        }

        if ($idUsuExcluir === null || $idUsuExcluir !== ($row['cpf_responsavel'] ?? null)) {
            $emails[] = $row['email_responsavel'] ?? '';
        }

        return array_values(array_unique(array_filter($emails)));
    }

    public static function listarGrupos(): array
    {
        return QueryBuilder::table('grupo g', 'mysql')
            ->select('g.id_grupo', 'g.descricao')
            ->orderBy('g.descricao', 'ASC')
            ->get();
    }

    public static function listarSubgrupos(): array
    {
        return QueryBuilder::table('subgrupo sg', 'mysql')
            ->select('sg.id', 'sg.idgrupo', 'sg.descricao')
            ->orderBy('sg.descricao', 'ASC')
            ->get();
    }

    public static function listarResponsaveis(): array
    {
        $resp = DB::select("SELECT u.cpf, f.nome
                FROM usuarios u 
                JOIN func f ON f.cpf = u.cpf
                WHERE u.suporte = 'S'
                AND u.ativo = 'S'
                UNION
                SELECT u.cpf, f.nome
                FROM usuarios u 
                JOIN func_externo f ON f.cpf = u.cpf
                WHERE u.suporte = 'S'
                ORDER BY 2", [], 'mysql');
        return $resp;
    }

    public static function cancelaChamado(int $idHelp): bool
    {
        return DB::update('helpdesk', 'id', $idHelp, [
            'status' => 'C',
            'dt_solucao' => date('Y-m-d H:i:s')
        ], 'mysql');
    }

    public static function chamadosPendentesSuporte(string $cpf): array|null
    {
        $pendentes = DB::select("SELECT count(*) AS total
            FROM helpdesk
            WHERE `status` IN ('PS', 'E', 'ST')
            AND id_resp = :resp
        ",
        ['resp' => $cpf], 'mysql');
        return $pendentes;
    }

    public static function chamadosPendentesUsuario(): array|null
    {
        return QueryBuilder::table('helpdesk h', 'mysql')
        ->select('h.id', 'h.status',
        "(SELECT MAX(hh.data_hist) FROM help_hist hh WHERE hh.id_help = h.id AND hh.status <> 'A') AS data_hist",
        'hs.date as data_status')
        ->leftJoinOn('help_status hs', function ($join) {
                $join->on('hs.id_help', '=', 'h.id')
                    ->onValue('hs.status', '<>', 'A');
        })
        ->where('h.status', 'PU')
        ->get();
    }
}
