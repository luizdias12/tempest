<?php

namespace App\Service;

use App\Model\Mysql\HelpdeskModel;

class HelpdeskService
{
    public static function chamadosAbertos(
        int $page = 1,
        int $limit = 10,
        ?string $id = null,
        ?string $emitente = null,
        ?string $status = null,
        ?string $local = null,
        ?string $idResp = null,
        ?bool $isSuporte = false
        ): array|null
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        
        return HelpdeskModel::chamadosAbertos($page, $limit, $id, $emitente, $status, $local, $idResp, $isSuporte);
    }

    public static function obterSla(int $idgrupo, int $idsubgrupo): ?int
    {
        return HelpdeskModel::obterSla($idgrupo, $idsubgrupo);
    }

    public static function criar(array $data): ?int
    {
        return HelpdeskModel::criar($data);
    }

    public static function atualizar(int $id, array $data): bool
    {
        return HelpdeskModel::atualizar($id, $data);
    }

    public static function atualizaStatusChamado(int $helpId, string $status): bool
    {
        return HelpdeskModel::atualizaStatusChamado($helpId, $status);
    }

    public static function obterCpfAbertura(int $id): ?string
    {
        return HelpdeskModel::obterCpfAbertura($id);
    }

    public static function obterEmailsNotificacao(int $id): array
    {
        return HelpdeskModel::obterEmailsNotificacao($id);
    }

    public static function listarGrupos(): array
    {
        return HelpdeskModel::listarGrupos();
    }

    public static function listarSubgrupos(): array
    {
        return HelpdeskModel::listarSubgrupos();
    }

    public static function listarResponsaveis(): array
    {
        return HelpdeskModel::listarResponsaveis();
    }
}