<?php

namespace App\Service;

use App\Model\Mysql\HelpHistoricoModel;

class HelpHistoricoService
{
    public static function obterHistoricoHelpdesk(int $helpId): array
    {
        return HelpHistoricoModel::obterHistoricoHelpdesk($helpId);
    }

    public static function verificaVisualizacao(int $helpId): array|null
    {
        return HelpHistoricoModel::verificaVisualizacao($helpId);
    }

    public static function visualizacoesPendentes(array $helpIds): array
    {
        return HelpHistoricoModel::visualizacoesPendentes($helpIds);
    }

    public static function chamadosAtualizados(array $helpIds): array
    {
        return HelpHistoricoModel::chamadosAtualizados($helpIds);
    }

    public static function contagemHistoricos(array $helpIds): array
    {
        return HelpHistoricoModel::contagemHistoricos($helpIds);
    }

    public static function registrarInteracao(int $helpId, string $historico, string $idUsu, string $status, string $fileStr = ''): ?int
    {
        return HelpHistoricoModel::registrarInteracao($helpId, $historico, $idUsu, $status, $fileStr);
    }

    public static function atualizarFileStr(int $idHist, string $fileStr): bool
    {
        return HelpHistoricoModel::atualizarFileStr($idHist, $fileStr);
    }

    public static function marcarVisualizado(int $helpId, string $idUsu): bool
    {
        return HelpHistoricoModel::marcarVisualizado($helpId, $idUsu);
    }

    public static function anexosAbertura(array $helpIds): array
    {
        return HelpHistoricoModel::anexosAbertura($helpIds);
    }

    public static function possuiAnexo(array $helpIds): array
    {
        return HelpHistoricoModel::possuiAnexo($helpIds);
    }
}
