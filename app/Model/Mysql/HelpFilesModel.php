<?php

namespace App\Model\Mysql;

use App\Core\DB;

class HelpFilesModel
{
    public static function registrar(int $idHelp, int $idHist, string $nome): ?int
    {
        return DB::insert('help_files', [
            'id_help'  => $idHelp,
            'id_hist'  => $idHist,
            'file_str' => '',
            'nome'     => mb_substr($nome, 0, 255),
        ], 'mysql');
    }

    public static function atualizarFileStr(int $id, string $fileStr): bool
    {
        return DB::update('help_files', 'id', $id, ['file_str' => $fileStr], 'mysql');
    }

    /**
     * @param int[] $idHists
     * @return array<int, array<int, array{file_str: string, nome: string}>>
     */
    public static function arquivosPorHistorico(array $idHists): array
    {
        if (empty($idHists)) {
            return [];
        }

        $ids = implode(',', array_map('intval', $idHists));

        $rows = DB::select("
            SELECT id_hist, file_str, nome
            FROM help_files
            WHERE file_str <> ''
            AND id_hist IN ({$ids})
            ORDER BY id ASC
        ", [], 'mysql');

        $agrupado = [];

        foreach ($rows as $row) {
            $agrupado[(int) $row['id_hist']][] = [
                'file_str' => (string) $row['file_str'],
                'nome'     => (string) ($row['nome'] !== '' ? $row['nome'] : 'Anexo'),
            ];
        }

        return $agrupado;
    }

    /**
     * @param int[] $helpIds
     * @return int[]
     */
    public static function possuiArquivos(array $helpIds): array
    {
        if (empty($helpIds)) {
            return [];
        }

        $ids = implode(',', array_map('intval', $helpIds));

        $rows = DB::select("
            SELECT DISTINCT id_help
            FROM help_files
            WHERE file_str <> ''
            AND id_help IN ({$ids})
        ", [], 'mysql');

        return array_map('intval', array_column($rows, 'id_help'));
    }

    /**
     * Primeiro arquivo (menor id) da abertura de cada chamado, via
     * help_hist com status 'A'. Compatível com chamados cujo arquivo
     * de abertura ainda está apenas em help_hist.file_str.
     *
     * @param int[] $helpIds
     * @return array<int, string>
     */
    public static function anexoAbertura(array $helpIds): array
    {
        if (empty($helpIds)) {
            return [];
        }

        $ids = implode(',', array_map('intval', $helpIds));

        $rows = DB::select("
            SELECT hf.id_help, hf.file_str
            FROM help_files hf
            JOIN help_hist h ON h.id_hist = hf.id_hist
            WHERE h.status = 'A'
            AND hf.file_str <> ''
            AND hf.id_help IN ({$ids})
            ORDER BY hf.id ASC
        ", [], 'mysql');

        $resultado = [];

        foreach ($rows as $row) {
            $idHelp = (int) $row['id_help'];

            if (!isset($resultado[$idHelp])) {
                $resultado[$idHelp] = (string) $row['file_str'];
            }
        }

        return $resultado;
    }
}
