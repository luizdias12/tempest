<?php

namespace App\Model\Oracle;

use App\Core\DB;
use DateTime;

class PfdependModel extends DB
{
    //Parentecos considerados pelo RH para cartao de vacina e frequencia escolar
    private const GRAUPARENTESCO = "'1','3','I','N','T'";

    //14 anos em meses
    private const MESES_MAIORIDADE = 168;

    /**
     * Competencia corrente da folha (PPARAM) e a data em que a idade sera avaliada.
     * A referencia e o ultimo dia do mes anterior a competencia, assim quem
     * completar 14 anos em qualquer dia da competencia continua elegivel.
     */
    public static function competencia(): ?array
    {
        $param = DB::first('SELECT anocomp, mescomp FROM pparam', [], 'rm');

        if (empty($param) || empty($param['anocomp']) || empty($param['mescomp'])) {
            return null;
        }

        $ano = (int) $param['anocomp'];
        $mes = (int) $param['mescomp'];

        if ($ano < 1900 || $ano > 2999 || $mes < 1 || $mes > 12) {
            return null;
        }

        $inicio = DateTime::createFromFormat('Y-n-j', "{$ano}-{$mes}-01");

        if (!$inicio) {
            return null;
        }

        return [
            'anocomp'    => $ano,
            'mescomp'    => $mes,
            'rotulo'     => sprintf('%02d/%04d', $mes, $ano),
            'referencia' => (clone $inicio)->modify('-1 month')->format('Y-m-t'),
        ];
    }

    /**
     * Contagens da conferencia antes de executar os updates.
     */
    public static function conferencia(string $referencia): array
    {
        $row = DB::first(
            "
        SELECT
            COUNT(*) AS total,
            NVL(SUM(CASE WHEN incsalfam = 1 THEN 1 ELSE 0 END), 0) AS incsalfam_ativo,
            NVL(SUM(CASE WHEN " . self::elegiveis('r1') . " THEN 1 ELSE 0 END), 0) AS elegiveis,
            NVL(SUM(CASE WHEN " . self::elegiveis('r2') . " AND cartaovacina = 1 AND freqescolar = 1 THEN 1 ELSE 0 END), 0) AS ja_marcados,
            NVL(SUM(CASE WHEN " . self::foraDaRegra('r3') . " AND (cartaovacina = 1 OR freqescolar = 1) THEN 1 ELSE 0 END), 0) AS fora_da_regra
        FROM pfdepend",
            [
                'r1' => $referencia,
                'r2' => $referencia,
                'r3' => $referencia,
            ],
            'rm'
        );

        return [
            'total'         => (int) ($row['total'] ?? 0),
            'incsalfam'     => (int) ($row['incsalfam_ativo'] ?? 0),
            'elegiveis'     => (int) ($row['elegiveis'] ?? 0),
            'ja_marcados'   => (int) ($row['ja_marcados'] ?? 0),
            'fora_da_regra' => (int) ($row['fora_da_regra'] ?? 0),
        ];
    }

    public static function zerarIncsalfam(): int
    {
        return DB::execute('UPDATE pfdepend SET incsalfam = 0 WHERE incsalfam = 1');
    }

    public static function marcarVacinaEscolar(string $referencia): int
    {
        return DB::execute(
            "
        UPDATE pfdepend
        SET cartaovacina = 1, freqescolar = 1
        WHERE " . self::elegiveis('r1') . "
            AND (NVL(cartaovacina, 0) <> 1 OR NVL(freqescolar, 0) <> 1)",
            ['r1' => $referencia]
        );
    }

    private static function elegiveis(string $placeholder): string
    {
        return "grauparentesco IN (" . self::GRAUPARENTESCO . ")"
            . " AND dtnascimento > ADD_MONTHS(TO_DATE(:{$placeholder}, 'YYYY-MM-DD'), -" . self::MESES_MAIORIDADE . ")";
    }

    private static function foraDaRegra(string $placeholder): string
    {
        return "(NVL(grauparentesco, '~') NOT IN (" . self::GRAUPARENTESCO . ")"
            . " OR dtnascimento IS NULL"
            . " OR dtnascimento <= ADD_MONTHS(TO_DATE(:{$placeholder}, 'YYYY-MM-DD'), -" . self::MESES_MAIORIDADE . "))";
    }
}
