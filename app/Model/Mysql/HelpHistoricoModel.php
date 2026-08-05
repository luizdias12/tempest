<?php

namespace App\Model\Mysql;

use App\Core\DB;
use App\Core\QueryBuilder;

class HelpHistoricoModel
{
    public static function obterHistoricoHelpdesk(int $helpId): array
    {
        $historico = QueryBuilder::table('help_hist h', 'mysql')
            ->select('h.id_hist', 'h.id_help', 'h.historico', 'h.status', 'h.data_hist', 'h.id_usu', 'h.file_str', 'f.nome')
            ->join('helpdesk hd', 'hd.id', '=', 'h.id_help')
            ->join('func f', 'f.cpf', '=', 'h.id_usu')
            ->where('h.id_help', $helpId)
            ->where('h.status', '<>', 'A')
            ->orderBy('h.data_hist', 'ASC')
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

    public static function contagemHistoricos(array $helpIds): array
    {
        if (empty($helpIds)) {
            return [];
        }

        $contagemHistoricos = QueryBuilder::table('help_hist h', 'mysql')
            ->select('h.id_help', 'COUNT(*) AS total')
            ->where('h.status', '<>', 'A')
            ->whereIn('h.id_help', $helpIds)
            ->groupBy('h.id_help')
            ->get();

        return array_map('intval', array_column($contagemHistoricos, 'total', 'id_help'));
    }

    public static function registrarInteracao(int $helpId, string $historico, string $idUsu, string $status, string $fileStr = ''): ?int
    {
        return DB::insert('help_hist', [
            'id_help' => $helpId,
            'historico' => $historico,
            'data_hist' => date('Y-m-d H:i:s'),
            'id_usu' => $idUsu,
            'status' => $status,
            'view' => 'N',
            'file_str' => $fileStr,
            'dtview' => null,
        ], 'mysql');
    }

    public static function atualizarFileStr(int $idHist, string $fileStr): bool
    {
        return DB::update('help_hist', 'id_hist', $idHist, ['file_str' => $fileStr], 'mysql');
    }

    public static function anexosAbertura(array $helpIds): array
    {
        if (empty($helpIds)) {
            return [];
        }

        $ids = implode(',', array_map('intval', $helpIds));

        $rows = DB::select("
            SELECT id_help, file_str
            FROM help_hist
            WHERE status = 'A'
            AND file_str <> ''
            AND id_help IN ({$ids})
        ", [], 'mysql');

        return array_column($rows, 'file_str', 'id_help');
    }
}
