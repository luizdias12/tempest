<?php

namespace App\Service;

use App\Model\Mysql\HelpFilesModel;

class HelpFilesService
{
    public static function registrar(int $idHelp, int $idHist, string $nome): ?int
    {
        return HelpFilesModel::registrar($idHelp, $idHist, $nome);
    }

    public static function atualizarFileStr(int $id, string $fileStr): bool
    {
        return HelpFilesModel::atualizarFileStr($id, $fileStr);
    }

    /**
     * @param int[] $idHists
     * @return array<int, array<int, array{file_str: string, nome: string}>>
     */
    public static function arquivosPorHistorico(array $idHists): array
    {
        return HelpFilesModel::arquivosPorHistorico($idHists);
    }

    /**
     * @param int[] $helpIds
     * @return int[]
     */
    public static function possuiArquivos(array $helpIds): array
    {
        return HelpFilesModel::possuiArquivos($helpIds);
    }

    /**
     * @param int[] $helpIds
     * @return array<int, string>
     */
    public static function anexoAbertura(array $helpIds): array
    {
        return HelpFilesModel::anexoAbertura($helpIds);
    }
}
