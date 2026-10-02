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
}
