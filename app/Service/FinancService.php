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

    public static function holeriteLiberado(int $mescomp, int $anocomp, int $periodo, int $codsecao = 31): bool
    {
        $liberacao = self::dataLiberacaoHolerite($mescomp, $anocomp, $periodo, $codsecao);

        if ($liberacao === null) {
            return false;
        }

        return new DateTime('today') >= $liberacao;
    }

    private static function dataLiberacaoHolerite(int $mescomp, int $anocomp, int $periodo, int $codsecao): ?DateTime
    {
        if (!checkdate($mescomp, 1, $anocomp)) {
            return null;
        }

        // 13º salário: 1ª parcela em 30/11 e 2ª em 20/12, antecipando para o dia útil anterior
        if ($periodo === 2) {
            $dia = match ($mescomp) {
                11 => 30,
                12 => 20,
                default => null,
            };

            if ($dia === null) {
                return null;
            }

            $referencia = sprintf('%04d%02d%02d', $anocomp, $mescomp, $dia);
            $diaUtil = FinancModel::diaUtilAnteriorOuIgual($referencia, $codsecao);

            return $diaUtil ? new DateTime($diaUtil) : null;
        }

        // Folha mensal: 5º dia útil do mês seguinte à competência
        if ($periodo === 3) {
            $dataPagamento = new DateTime(sprintf('%04d-%02d-01', $anocomp, $mescomp));
            $dataPagamento->modify('+1 month');

            $quintoDiaUtil = FinancModel::obterQuintoDiaUtil($dataPagamento->format('Ymd'), $codsecao);

            if (!$quintoDiaUtil) {
                return null;
            }

            return new DateTime($quintoDiaUtil['quinto_dia_util']);
        }

        return null;
    }
}
