<?php

namespace App\Model\Mysql;

use App\Core\DB;
use App\Core\QueryBuilder;

class HelpHistoricoModel
{
    public static function obterHistoricoHelpdesk(int $helpId, string $cpfUsuario = '', bool $isSuporte = false): array
    {
        $query = QueryBuilder::table('help_hist h', 'mysql')
            ->select('h.id_hist', 'h.id_help', 'h.historico', 'h.status',
            'h.data_hist', 'h.id_usu', 'h.file_str', 'h.privado',
            'COALESCE(f.nome, fe.nome) as nome', 'h.dtview')
            ->join('helpdesk hd', 'hd.id', '=', 'h.id_help')
            ->leftJoin('func f', 'f.cpf', '=', 'h.id_usu')
            ->leftJoin('func_externo as fe', 'fe.cpf', '=', 'h.id_usu')
            ->where('h.id_help', $helpId)
            ->where('h.status', '<>', 'A');

        if (!$isSuporte) {
            $query->whereRaw(
                '(h.privado IS NULL OR h.id_usu = :privUsu)',
                ['privUsu' => $cpfUsuario]
            );
        }

        $query->orderBy('h.data_hist', 'ASC');

        return $query->get();
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

    public static function chamadosAtualizados(array $helpIds): array
    {
        if (empty($helpIds)) {
            return [];
        }

        $atualizados = QueryBuilder::table('help_hist h', 'mysql')
            ->select('h.id_help')
            ->where('status', '<>', 'A')
            ->where('view', 'N')
            ->whereIn('h.id_help', $helpIds)
            ->get();

        return array_column($atualizados, 'id_help');
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

    public static function registrarInteracao(int $helpId, string $historico, string $idUsu, string $status, string $fileStr = '', bool $privado = false): ?int
    {
        $data = [
            'id_help' => $helpId,
            'historico' => $historico,
            'data_hist' => date('Y-m-d H:i:s'),
            'id_usu' => $idUsu,
            'status' => $status,
            'view' => 'N',
            'file_str' => $fileStr,
            'dtview' => null,
        ];

        if ($privado) {
            $data['privado'] = 'S';
        }

        return DB::insert('help_hist', $data, 'mysql');
    }

    public static function atualizarFileStr(int $idHist, string $fileStr): bool
    {
        return DB::update('help_hist', 'id_hist', $idHist, ['file_str' => $fileStr], 'mysql');
    }

    public static function marcarVisualizado(int $helpId): bool
    {
        $stmt = DB::connect('mysql')->prepare("
            UPDATE help_hist
            SET view = 'S', dtview = NOW()
            WHERE id_help = :id
            AND view = 'N'
        ");

        return $stmt->execute(['id' => $helpId]);
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

    public static function possuiAnexo(array $helpIds): array
    {
       if (empty($helpIds)) {
            return [];
        }

        $possuiAnexo = QueryBuilder::table('help_hist h', 'mysql')
            ->select('h.id_help')
            ->where('file_str', '<>', '')
            ->whereIn('h.id_help', $helpIds)
            ->get();

        return array_column($possuiAnexo, 'id_help');
    }
}
