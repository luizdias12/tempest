<?php

namespace App\Model\Oracle;

use App\Core\DB;
use App\Core\QueryBuilder;

class EsocialModel extends DB
{
    public static function getEsocialData(): array|null
    {
        return QueryBuilder::table('pesocialeventos')
            ->select('tipoevento', 'status', 'count(*) as total')
            ->whereNotIn('status', ['10', '11'])
            ->groupBy('tipoevento', 'status')
            ->orderBy('tipoevento', 'status')
            ->get();
    }

    public static function getFullData(): array|null
    {
        return DB::select("SELECT
            tipoevento,
            SUM(CASE WHEN status = '0' THEN 1 ELSE 0 END) AS pendente,
            SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END) AS xml_gerado,
            SUM(CASE WHEN status = '2' THEN 1 ELSE 0 END) AS erro_gerar_xml,
            SUM(CASE WHEN status = '3' THEN 1 ELSE 0 END) AS integrado_taf,
            SUM(CASE WHEN status = '4' THEN 1 ELSE 0 END) AS aceito_taf,
            SUM(CASE WHEN status = '5' THEN 1 ELSE 0 END) AS erro_integracao_taf,
            SUM(CASE WHEN status = '6' THEN 1 ELSE 0 END) AS rejeitado_taf,
            SUM(CASE WHEN status = '9' THEN 1 ELSE 0 END) AS rejeitado_ret,
            COUNT(*) AS total
        FROM pesocialeventos
        WHERE status NOT IN ('10', '11')
        GROUP BY tipoevento
        ORDER BY tipoevento");
    }

    public static function getEventosTabela(): array|null
    {
        return DB::select("SELECT
            tipoevento,
            SUM(CASE WHEN status = '0' THEN 1 ELSE 0 END) AS pendente,
            SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END) AS xml_gerado,
            SUM(CASE WHEN status = '2' THEN 1 ELSE 0 END) AS erro_gerar_xml,
            SUM(CASE WHEN status = '3' THEN 1 ELSE 0 END) AS integrado_taf,
            SUM(CASE WHEN status = '4' THEN 1 ELSE 0 END) AS aceito_taf,
            SUM(CASE WHEN status = '5' THEN 1 ELSE 0 END) AS erro_integracao_taf,
            SUM(CASE WHEN status = '6' THEN 1 ELSE 0 END) AS rejeitado_taf,
            SUM(CASE WHEN status = '9' THEN 1 ELSE 0 END) AS rejeitado_ret,
            COUNT(*) AS total
        FROM pesocialeventos
        WHERE status NOT IN ('10', '11')
        AND tipoevento IN ('S-1000', 'S-1005', 'S-1010', 'S-1020', 'S-1070')
        GROUP BY tipoevento
        ORDER BY tipoevento");
    }

    public static function getEventosPeriodicos(int $mescomp, int $anocomp): array|null
    {
        return DB::select("SELECT
            tipoevento,
            SUM(CASE WHEN status = '0' THEN 1 ELSE 0 END) AS pendente,
            SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END) AS xml_gerado,
            SUM(CASE WHEN status = '2' THEN 1 ELSE 0 END) AS erro_gerar_xml,
            SUM(CASE WHEN status = '3' THEN 1 ELSE 0 END) AS integrado_taf,
            SUM(CASE WHEN status = '4' THEN 1 ELSE 0 END) AS aceito_taf,
            SUM(CASE WHEN status = '5' THEN 1 ELSE 0 END) AS erro_integracao_taf,
            SUM(CASE WHEN status = '6' THEN 1 ELSE 0 END) AS rejeitado_taf,
            SUM(CASE WHEN status = '9' THEN 1 ELSE 0 END) AS rejeitado_ret,
            COUNT(*) AS total
        FROM pesocialeventos
        WHERE status NOT IN ('10', '11')
        AND tipoevento IN ('S-1200', 'S-1210', 'S-1270', 'S-1298', 'S-1299')
        AND mescomp = :mescomp
        AND anocomp = :anocomp
        GROUP BY tipoevento
        ORDER BY tipoevento",
        [
            'mescomp' => $mescomp,
            'anocomp' => $anocomp
        ]);
    }

    public static function getEventosNaoPeriodicos(): array|null
    {
        return DB::select("SELECT
            tipoevento,
            SUM(CASE WHEN status = '0' THEN 1 ELSE 0 END) AS pendente,
            SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END) AS xml_gerado,
            SUM(CASE WHEN status = '2' THEN 1 ELSE 0 END) AS erro_gerar_xml,
            SUM(CASE WHEN status = '3' THEN 1 ELSE 0 END) AS integrado_taf,
            SUM(CASE WHEN status = '4' THEN 1 ELSE 0 END) AS aceito_taf,
            SUM(CASE WHEN status = '5' THEN 1 ELSE 0 END) AS erro_integracao_taf,
            SUM(CASE WHEN status = '6' THEN 1 ELSE 0 END) AS rejeitado_taf,
            SUM(CASE WHEN status = '9' THEN 1 ELSE 0 END) AS rejeitado_ret,
            COUNT(*) AS total
        FROM pesocialeventos
        WHERE status NOT IN ('10', '11')
        AND tipoevento IN ('S-2190', 'S-2200', 'S-2205', 'S-2206', 'S-2230', 'S-2298','S-2299', 'S-2300', 'S-2306', 'S-2399', 'S-3000')
        GROUP BY tipoevento
        ORDER BY tipoevento");
    }

    public static function getEventosProcTrabalhista(): array|null
    {
        return DB::select("SELECT
            tipoevento,
            SUM(CASE WHEN status = '0' THEN 1 ELSE 0 END) AS pendente,
            SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END) AS xml_gerado,
            SUM(CASE WHEN status = '2' THEN 1 ELSE 0 END) AS erro_gerar_xml,
            SUM(CASE WHEN status = '3' THEN 1 ELSE 0 END) AS integrado_taf,
            SUM(CASE WHEN status = '4' THEN 1 ELSE 0 END) AS aceito_taf,
            SUM(CASE WHEN status = '5' THEN 1 ELSE 0 END) AS erro_integracao_taf,
            SUM(CASE WHEN status = '6' THEN 1 ELSE 0 END) AS rejeitado_taf,
            SUM(CASE WHEN status = '9' THEN 1 ELSE 0 END) AS rejeitado_ret,
            COUNT(*) AS total
        FROM pesocialeventos
        WHERE status NOT IN ('10', '11')
        AND tipoevento IN ('S-2500', 'S-2501', 'S-2555', 'S-3500')
        GROUP BY tipoevento
        ORDER BY tipoevento");
    }
}
