<?php

namespace App\Model\Mysql;

use App\Core\DB;

class ExtratoUnimedModel
{
    private const TABELA = 'tbl_extrato_unimed';

    public static function competencias(): array
    {
        return DB::select(
            'SELECT DISTINCT mes_comp, ano_comp FROM ' . self::TABELA . ' ORDER BY ano_comp DESC, mes_comp DESC',
            [],
            'mysql'
        );
    }

    public static function porCompetencia(string $mes, string $ano): array
    {
        return DB::select(
            'SELECT nome_cliente, nome_titular, nome_prestador, data_atendimento, tipo_procedimento, quantidade, valor
             FROM ' . self::TABELA . '
             WHERE mes_comp = ? AND ano_comp = ?
             ORDER BY data_atendimento, id_registro',
            [$mes, $ano],
            'mysql'
        );
    }
}
