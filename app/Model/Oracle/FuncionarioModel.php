<?php

namespace App\Model\Oracle;

use App\Core\DB;
use App\Core\QueryBuilder;
use PDO;
use PDOException;

class FuncionarioModel extends DB
{

    public static function all(int $page, int $limit, ?string $codfilial = null): ?array
    {
        $query = QueryBuilder::table('pfunc f')
            ->select(
                'f.chapa',
                'f.nome',
                'f.codfilial',
                'f.codsecao',
                'f.codfuncao',
                'fu.nome as funcao',
                'f.dataadmissao',
                'f.datademissao',
                'f.tipodemissao',
                'f.codsituacao'
            )
            ->join('pfuncao fu', 'f.codfuncao', '=', 'fu.codigo')
            ->whereGroup(function ($q) {
                $q->whereNull('f.datademissao');
                $q->orWhere('f.tipodemissao', '<>', '5');
            })
            ->orderBy('f.codfilial', 'ASC')
            ->orderBy('f.nome', 'ASC');

        if ($codfilial !== null && $codfilial !== '') {
            $query->where('f.codfilial', '=', $codfilial);
        }

        return $query->paginate($page, $limit);
    }

    public static function findByCpf(string $cpf): ?array
    {
        return self::findWhere('ppessoa', ['cpf' => $cpf]);
    }

    public static function findByCpfDados(string $cpf): array|null
    {
        return QueryBuilder::table('pfunc f')
            ->select(
                'f.chapa',
                'f.nome',
                'f.codfilial',
                'f.codsecao',
                'f.codfuncao',
                'fu.nome as funcao',
                's.descricao as secao',
                'f.dataadmissao',
                'f.datademissao',
                'f.tipodemissao',
                'f.codsituacao',
                'p.cpf as cpf'
            )
            ->join('pfuncao fu', 'fu.codigo', '=', 'f.codfuncao')
            ->join('ppessoa p', 'p.codigo', '=', 'f.codpessoa')
            ->join('psecao s', 's.codigo', '=', 'f.codsecao')
            ->where('p.cpf', $cpf)
            ->where('f.codsituacao', '<>', 'D')
            ->first();
    }

    public static function findByChapa(string $chapa): array|null
    {
        return QueryBuilder::table('pfunc f')
            ->select(
                'f.chapa',
                'f.nome',
                'f.codfilial',
                'f.codsecao',
                'f.codfuncao',
                'fu.nome as funcao',
                'f.dataadmissao',
                'f.datademissao',
                'f.tipodemissao',
                'f.codsituacao'
            )
            ->join('pfuncao fu', 'fu.codigo', '=', 'f.codfuncao')
            ->where('f.chapa', '=', $chapa)
            ->first();
    }

    public static function findByNome(string $nome): array|null
    {
        return QueryBuilder::table('pfunc f')
            ->select(
                'f.chapa',
                'f.nome',
                'f.codfilial',
                'f.codsecao',
                'f.codfuncao',
                'fu.nome as funcao',
                's.descricao as secao',
                'f.dataadmissao',
                'f.datademissao',
                'f.tipodemissao',
                'f.codsituacao',
                'p.cpf as cpf'
            )
            ->join('pfuncao fu', 'fu.codigo', '=', 'f.codfuncao')
            ->join('ppessoa p', 'p.codigo', '=', 'f.codpessoa')
            ->join('psecao s', 's.codigo', '=', 'f.codsecao')
            ->whereRaw('UPPER(f.nome) = :nome', [
                'nome' => mb_strtoupper($nome, 'UTF-8')
            ])
            ->where('f.codsituacao', '<>', 'D')
            ->first();
    }

    public static function paginate(int $page = 1, int $limit = 10): array
    {
        return parent::paginateTable(
            'pfunc',
            $page,
            $limit,
            ['codfilial' => '31'],
            [
                'orderBy' => [
                    ['column' => 'codfilial', 'direction' => 'ASC'],
                    ['column' => 'nome', 'direction' => 'ASC']
                ]
            ]
        );
    }

    public static function ativos(
        int $page,
        int $limit,
        ?string $codfilial = null,
        ?string $secao = null,
        ?string $situacao = null,
        ?string $nome = null
    ): ?array {
        $query = QueryBuilder::table('ppessoa p')
            ->select(
                'p.codigo as codpessoa',
                'f.chapa',
                'p.nome',
                "TO_CHAR(p.dtnascimento, 'DD-MM-YYYY') as dtnascimento",
                'p.cpf',
                'p.cartidentidade',
                'f.codfilial',
                'g.vilnomefilial as filial',
                'f.codsecao',
                's.descricao as secao',
                'f.codfuncao',
                'fu.nome as funcao',
                'f.dataadmissao',
                'f.datademissao',
                'f.tipodemissao',
                'f.codsituacao',
                "CASE WHEN f.tipodemissao = '5' THEN 'Transferido' ELSE cs.descricao END situacao"
            )
            ->leftJoin('pfunc f', 'f.codpessoa', '=', 'p.codigo')
            ->leftJoin('pfuncao fu', 'f.codfuncao', '=', 'fu.codigo')
            ->leftJoin('psecao s', 'f.codsecao', '=', 's.codigo')
            ->leftJoin('gfilial g', 'f.codfilial', '=', 'g.codfilial')
            ->leftJoin('pcodsituacao cs', 'f.codsituacao', '=', 'cs.codinterno')
            ->orderBy('f.codfilial', 'ASC')
            ->orderBy('p.nome', 'ASC');

        if (!empty($codfilial)) {
            $query->where('f.codfilial', $codfilial);
        }

        if (!empty($secao)) {
            $query->where('f.codsecao', $secao);
        }

        if (!empty($situacao)) {
            $query->where('f.codsituacao', $situacao);
        }

        if (!empty($nome)) {
            $query->where('LOWER(p.nome)', 'LIKE', '%' . strtolower($nome) . '%');
        }

        return $query->paginate($page, $limit);
    }

    public static function aniversariantes(?string $mes, ?string $codfilial = null): array
    {
        $query = QueryBuilder::table('ppessoa p')
            ->select(
                'p.codigo as codpessoa',
                'f.chapa',
                'p.nome',
                "TO_CHAR(p.dtnascimento, 'YYYY-MM-DD') as dtnascimento",
                'p.cpf',
                'f.codfilial',
                'g.vilnomefilial as filial',
                'f.codsecao',
                's.descricao as secao',
                'f.codfuncao',
                'fu.nome as funcaorm',
                'initcap(pai.funcaopai) as funcao'
            )
            ->join('pfunc f', 'f.codpessoa', '=', 'p.codigo')
            ->leftJoin('pfuncao fu', 'f.codfuncao', '=', 'fu.codigo')
            ->leftJoin('consinco.vilrhfuncaofilho@consinco filho', 'filho.codfuncao', '=', 'f.codfuncao')
            ->leftJoin('consinco.vilrhfuncaopai@consinco pai', 'pai.codfuncaopai', '=', 'filho.codfuncaopai')
            ->leftJoin('psecao s', 'f.codsecao', '=', 's.codigo')
            ->leftJoin('gfilial g', 'f.codfilial', '=', 'g.codfilial')
            ->whereRaw('EXTRACT(MONTH FROM p.dtnascimento) = :mes', ['mes' => $mes])
            ->whereNotNull('p.dtnascimento')
            ->whereNotIn('f.codsituacao', ['D', 'L'])
            ->orderBy('f.codfilial', 'ASC')
            ->orderBy('EXTRACT(DAY FROM p.dtnascimento)', 'ASC')
            ->orderBy('p.nome', 'ASC');

        if (!empty($codfilial)) {
            $query->where('f.codfilial', $codfilial);
            return $query->get();
        } else {
            return [];
        }
    }

    public static function dataFerias(string $chapa): ?array
    {
        return QueryBuilder::table('pfuferiasper fp')
            ->select(
                'fp.chapa',
                'fp.datainicio',
                'fp.datafim'
            )
            ->where('fp.chapa', '=', $chapa)
            ->orderBy('fp.datainicio', 'DESC')
            ->first();
    }

    public static function listaTI(int $page, int $limit): ?array
    {
        $query = QueryBuilder::table('pfunc f')
            ->select(
                'f.chapa',
                'f.nome',
                'f.codfilial',
                'g.vilnomefilial as filial',
                'f.codfuncao',
                'fu.nome as funcao',
                'f.salario',
                'f.dataadmissao',
                'f.codsituacao',
                'cs.descricao as situacao'
            )
            ->join('pfuncao fu', 'f.codfuncao', '=', 'fu.codigo')
            ->join('psecao s', 'f.codsecao', '=', 's.codigo')
            ->join('gfilial g', 'f.codfilial', '=', 'g.codfilial')
            ->join('pcodsituacao cs', 'f.codsituacao', '=', 'cs.codinterno')
            ->leftJoin('pfhstsal hs', 'hs.chapa', '=', 'f.chapa')
            ->whereStartsWith('s.descricao', 'TECNOLOGIA')
            ->whereNotIn('f.codsituacao', ['D','L'])
            ->orderBy('f.nome', 'ASC');
        return $query->paginate($page, $limit);
        // return $query->toSql();
    }

    public static function exportTi(): array
    {
        return DB::select("
            SELECT
                sub.chapa,
                sub.nome,
                sub.codfilial,
                sub.filial,
                sub.codfuncao,
                sub.funcao,
                sub.salario,
                sub.dataadmissao,
                sub.codsituacao,
                sub.situacao,
                sub.ultima_alteracao,
                sub.motivo
            FROM (
                SELECT
                    f.chapa,
                    f.nome,
                    f.codfilial,
                    g.vilnomefilial AS filial,
                    f.codfuncao,
                    fu.nome AS funcao,
                    f.salario,
                    f.dataadmissao,
                    f.codsituacao,
                    cs.descricao AS situacao,
                    hs.dtmudanca AS ultima_alteracao,
                    ms.descricao AS motivo,
                    ROW_NUMBER() OVER (
                        PARTITION BY f.chapa
                        ORDER BY hs.dtmudanca DESC
                    ) AS rn
                FROM pfunc f
                    JOIN pfuncao fu ON fu.codigo = f.codfuncao
                    JOIN psecao s ON s.codigo = f.codsecao
                    JOIN gfilial g ON g.codfilial = f.codfilial
                    JOIN pcodsituacao cs ON cs.codinterno = f.codsituacao
                    LEFT JOIN pfhstsal hs
                        ON hs.chapa = f.chapa
                        AND hs.motivo IN ('02','03')
                    LEFT JOIN pmotmudsal ms
                        ON ms.codinterno = hs.motivo
                WHERE s.descricao LIKE '%TECNOLOGIA%'
                AND f.codsituacao not in ('D','L')
            ) sub
            WHERE rn = 1
            ORDER BY sub.nome ASC
        ");
    }

    public static function ti(int $page = 1, int $limit = 10): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $baseSql = "FROM (
            SELECT 
                f.chapa,
                f.nome,
                f.codfilial,
                g.vilnomefilial AS filial,
                f.codfuncao,
                fu.nome AS funcao,
                f.salario,
                f.dataadmissao,
                f.codsituacao,
                cs.descricao AS situacao,
                hs.dtmudanca AS ultima_alteracao,
                ms.descricao AS motivo,
                ROW_NUMBER() OVER (
                    PARTITION BY f.chapa
                    ORDER BY hs.dtmudanca DESC
                ) AS rn
            FROM pfunc f
                JOIN pfuncao fu 
                    ON fu.codigo = f.codfuncao
                JOIN psecao s 
                    ON s.codigo = f.codsecao
                JOIN gfilial g 
                    ON g.codfilial = f.codfilial
                JOIN pcodsituacao cs 
                    ON cs.codinterno = f.codsituacao
                LEFT JOIN pfhstsal hs 
                    ON hs.chapa = f.chapa
                    AND hs.motivo IN ('02','03')
                LEFT JOIN pmotmudsal ms 
                    ON ms.codinterno = hs.motivo
            WHERE s.descricao LIKE '%TECNOLOGIA%'
            AND f.codsituacao not in ('D','L')
        ) sub
        WHERE rn = 1";

        $total = (int) DB::first("SELECT COUNT(*) as total {$baseSql}")['total'];

        $data = DB::select(
            "SELECT 
                chapa,
                nome,
                codfilial,
                filial,
                codfuncao,
                funcao,
                salario,
                dataadmissao,
                codsituacao,
                situacao,
                ultima_alteracao,
                motivo
            {$baseSql}
            ORDER BY nome ASC
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY",
            ['offset' => $offset, 'limit' => $limit]
        );

        return [
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => (int) ceil($total / $limit)
            ]
        ];
    }

    public static function admissoes(): array
    {
        $query = QueryBuilder::table('pesocialeventos e')
        ->select('count(f.chapa) as pendentes', 'f.dataadmissao', "TO_CHAR(max(e.dataevento), 'YYYY-MM-DD HH24:MI:SS') as dataevento")
        ->join('pfunc f', 'f.chapa', '=', 'e.chapa')
        ->where('e.tipoevento', 'S-2200')
        ->whereIn('e.status', [0,1,2,6,9])
        ->groupBy('f.dataadmissao')
        ->get();
        return $query;
    }

    public static function listAllFuncs(): array
    {
        return QueryBuilder::table('pfunc f')
            ->select(
                'f.chapa',
                'INITCAP(f.nome) nome',
                'INITCAP(p.nomesocial) nomesocial',
                'f.codfuncao',
                'f.codhorario',
                'f.codsecao',
                'p.codigo codpessoa',
                'p.cpf',
                'f.codfilial',
                "TO_CHAR(p.dtnascimento, 'YYYY-MM-DD') dtnascimento",
                "TO_CHAR(f.dataadmissao, 'YYYY-MM-DD') dataadmissao",
                "TO_CHAR(f.datademissao, 'YYYY-MM-DD') datademissao",
                'f.codsituacao',
                'f.pisepasep'
            )
            ->join('ppessoa p', 'p.codigo', '=', 'f.codpessoa')
            ->whereNotIn('f.codsituacao', ['I','D','L'])
            ->where('f.chapa', '<>', '00001')
            ->whereNotNull('p.cpf')
            ->orderBy('f.nome', 'ASC')
            ->get();
    }
}