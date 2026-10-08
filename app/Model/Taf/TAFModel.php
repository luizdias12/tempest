<?php

namespace App\Model\Taf;

use App\Core\DB;
use App\Core\QueryBuilder;

class TAFModel extends DB
{
    public static function get2230(string $competencia): array|null
    {
        return DB::select("SELECT DECODE(
        e.cm6_status,
        '0', 'Pendente de Envio',
        '2', 'Aguardando Governo',
        '3', 'Rejeitado',
        '4', 'Sucesso',
        '7', 'Excluido',
        'Nao Identificado') as status,
        c9v_cpf, trim(c9v_matric) as matric_social, trim(c9v_nome) as nome,
        cm6_motvaf as codmotivo, cm6_dtafas as dtinicio, cm6_dtfafa as dtfim,
        cm6_logope
        from cm6g10 e
        join c9vg10 f on f.c9v_id = e.cm6_func
        where e.R_E_C_D_E_L_ = 0
        and e.cm6_status in ('4')
        and (SUBSTR(cm6_dtafas,1,6) = :competencia OR SUBSTR(cm6_dtfafa,1,6) = :competencia)",
            ['competencia' => $competencia],
            'taf'
        );
    }
}
