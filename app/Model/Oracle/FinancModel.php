<?php

namespace App\Model\Oracle;

use App\Core\DB;
use App\Core\QueryBuilder;

class FinancModel extends DB
{
    public static function valoresHolerite(string $chapa, int $mescomp, int $anocomp, int $periodo, string $pdb): array|null
    {
        return QueryBuilder::table('pffinanc fi')
        ->select('fi.codevento', 'e.descricao', 'fi.valor')
        ->join('pfunc f', 'f.chapa', '=', 'fi.chapa')
        ->join('pevento e', 'e.codigo', '=', 'fi.codevento')
        ->where('e.provdescbase', $pdb)
        ->where('fi.chapa', $chapa)
        ->where('fi.mescomp', $mescomp)
        ->where('fi.anocomp', $anocomp)
        ->where('fi.nroperiodo', $periodo)
        ->where('fi.valor', '>', 0)
        ->get();
    }

    public static function valorBaseFgts(string $chapa, int $mescomp, int $anocomp, int $periodo): array|null
    {
        $result = DB::select("
        SELECT pf.basefgts, f.codtipo,
            CASE WHEN f.codtipo='N' THEN TRUNC(((pf.basefgts + pf.basefgts13) * 0.08), 2)
                 ELSE TRUNC(((pf.basefgts + pf.basefgts13) * 0.02), 2) END calc_fgts
        FROM pfperff pf
        JOIN pfunc f ON f.chapa = pf.chapa
        WHERE pf.chapa = :chapa
            AND pf.anocomp = :ano
            AND pf.mescomp = :mes
            AND pf.nroperiodo = :periodo",
       [
        'chapa'     => $chapa,
        'ano'       => $anocomp,
        'mes'       => $mescomp,
        'periodo'   => $periodo,
       ]);
       
       return $result;
    }

    public static function obterQuintoDiaUtil(string $dataref, int $codsecao): array|null
    {
        $quintoDiaUtil = DB::select("
            WITH dias AS (
                SELECT TRUNC(TO_DATE(:data_referencia, 'YYYYMMDD'), 'MM') + LEVEL - 1 AS dia
                FROM dual
                CONNECT BY LEVEL <=
                    LAST_DAY(TO_DATE(:data_referencia, 'YYYYMMDD'))
                    - TRUNC(TO_DATE(:data_referencia, 'YYYYMMDD'), 'MM')
                    + 1
            )
            SELECT dia AS quinto_dia_util
            FROM (
                SELECT d.dia,
                    ROW_NUMBER() OVER (ORDER BY d.dia) AS rn
                FROM dias d
                WHERE TRUNC(d.dia) - TRUNC(d.dia, 'IW') < 6
                AND NOT EXISTS (
                        SELECT 1
                        FROM GFERIADO f
                        JOIN GCALEND c ON c.CODIGO = f.CODCALENDARIO
                        JOIN PSECAO s ON s.CODCALENDARIO = c.CODIGO
                        WHERE TRUNC(f.DIAFERIADO) = d.dia
                        AND f.FERIADO = 'T'
                        AND s.CODIGO = :secao
                )
            )
            WHERE rn = 5
        ",
        [
            'data_referencia' => $dataref,
            'secao' => $codsecao
        ]);

        return $quintoDiaUtil[0] ?? null;
    }

}