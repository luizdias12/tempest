<?php

namespace App\Model\Mysql;

use App\Core\QueryBuilder;

class HelpHistoricoModel
{
    public static function obterHistoricoHelpdesk(int $helpId): array
    {
        $historico = QueryBuilder::table('help_hist h', 'mysql')
            ->select('h.id_hist', 'h.id_help', 'h.action', 'h.data_hist', 'f.nome')
            ->join('helpdesk hd', 'hd.id', '=', 'h.id_help')
            ->join('func f', 'f.cpf', '=', 'h.id_usu')
            ->where('h.id_help', $helpId)
            ->orderBy('h.data_hist', 'DESC')
            ->get();

        return $historico;
    }

    public static function verificaVisualizacao(int $helpId): array|null
    {
        $visualizado = QueryBuilder::table('help_hist h', 'mysql')
        ->select('view', 'dtview')
        ->where('status', 'A')
        ->where('h.id_help', $helpId)
        ->where('view', 'N')
        ->first();

        return $visualizado;
    }

    public static function visualizacoesPendentes(array $helpIds): array
    {
        if (empty($helpIds)) {
            return [];
        }

        $pendentes = QueryBuilder::table('help_hist h', 'mysql')
            ->select('h.id_help')
            ->where('status', 'A')
            ->where('view', 'N')
            ->whereIn('h.id_help', $helpIds)
            ->get();

        return array_column($pendentes, 'id_help');
    }
}