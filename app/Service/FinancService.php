<?php

namespace App\Service;

use App\Model\Oracle\FinancModel;
use DateTime;

class FinancService
{
    public static function valoresHolerite(string $chapa, int $mescomp, int $anocomp, int $periodo, string $pdb): array|null
    {
        return FinancModel::valoresHolerite($chapa, $mescomp, $anocomp, $periodo, $pdb);
    }

    public static function valorBaseFgts(string $chapa, int $mescomp, int $anocomp, int $periodo): array|null
    {
        return FinancModel::valorBaseFgts($chapa, $mescomp, $anocomp, $periodo);
    }
    public static function totaisHolerite(string $chapa, int $mescomp, int $anocomp, int $periodo): array|null
    {
        $proventos = self::valoresHolerite($chapa, $mescomp, $anocomp, $periodo, 'P');
        $descontos = self::valoresHolerite($chapa, $mescomp, $anocomp, $periodo, 'D');
        $fgts = self::valorBaseFgts($chapa, $mescomp, $anocomp, $periodo);
        $calc_fgts = (float) $fgts[0]['calc_fgts'] ?? 0.00;

        $totalProventos = array_sum(array_column($proventos, 'valor'));
        $totalDescontos = array_sum(array_column($descontos, 'valor'));

        return [
            'proventos'         => $proventos,
            'decontos'          => $descontos,
            'total_proventos'   => $totalProventos,
            'total_descontos'   => $totalDescontos,
            'valor_liquido'     => $totalProventos - $totalDescontos,
            'valor_fgts'        => $calc_fgts,
        ];
    }

    public static function holeriteLiberado(string $dataref, int $codsecao): bool
    {
        $quintoDiaUtil = FinancModel::obterQuintoDiaUtil($dataref, $codsecao);

        if (!$quintoDiaUtil) {
            return false;
        }

        $liberacao = new DateTime($quintoDiaUtil['quinto_dia_util']);

        return new DateTime() >= $liberacao;
    }
}
