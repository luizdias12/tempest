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
        ?bool $isSuporte = false
        ): array|null
    {
        $chamadosAbertos = QueryBuilder::table('helpdesk h', 'mysql')
        ->select('h.id', 'h.chapa', 'f.nome', 'h.dt_abertura',
        'h.dt_solucao', 'h.status', 'st.descricao as status_desc',
        'h.cab_problema', 'h.desc_problema', 'g.descricao as grupo',
        'sg.descricao as subgrupo', 'o.local', 'f2.nome as responsavel',
        'fu.nome as funcao', 'u.ramal as ramal', 'u.email as email',
        's.desc_sla as sla', 'h.cpf_ab', 'h.idgrupo', 'h.idsubgrupo', 'h.id_resp'
        )
        ->join('func f', 'f.cpf', '=', 'h.cpf_ab')
        ->leftJoin('func f2', 'f2.cpf', '=', 'h.id_resp')
        ->join('funcao fu', 'fu.codigo', '=', 'f.codfuncao')
        ->join('grupo g', 'g.id_grupo', '=', 'h.idgrupo')
        ->join('sla s', 's.id_sla', '=', 'h.sla')
        ->leftJoin('usuarios u', 'u.cpf', '=', 'h.cpf_ab')
        ->joinOn('subgrupo sg', function($join) {
            $join->on('sg.idgrupo', '=', 'g.id_grupo')
            ->on('sg.id', '=', 'h.idsubgrupo');
        })
        ->join('status st', 'st.status', '=', 'h.status')
        ->leftJoinOn('online o', function($join) use ($local) {
            $join->on('o.cpf', '=', 'f.cpf');
            if (!empty($local)) {
                $join->onValue('o.local', '=', $local);
            }
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
        ->when(empty($status) && $isSuporte, fn($q) => $q->whereNotIn('h.status', ['R','C']))
        ->when(!empty($id), fn($q) => $q->where('h.id', $id))
        ->when(!empty($emitente), fn($q) => $q->where('f.nome', 'LIKE', "%$emitente%"))
        ->when(!empty($status), fn($q) => $q->where('h.status', $status))
        ->when(!empty($idResp), fn($q) => $q->where('h.id_resp', $idResp));

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

    public static function obterEmailsNotificacao(int $id): array
    {
        $row = DB::first("
            SELECT ab.email AS email_abertura,
                   resp.email AS email_responsavel
            FROM helpdesk h
            LEFT JOIN usuarios ab ON ab.cpf = h.cpf_ab
            LEFT JOIN usuarios resp ON resp.cpf = h.id_resp
            WHERE h.id = :id
        ", ['id' => $id], 'mysql');

        if ($row === null) {
            return [];
        }

        return array_values(array_unique(array_filter([
            $row['email_abertura'] ?? '',
            $row['email_responsavel'] ?? '',
        ])));
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
}