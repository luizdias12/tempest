<?php

namespace App\Service;

use App\Model\Oracle\FuncionarioModel;
use App\Core\ApiException;
use App\Model\Oracle\FuncaoModel;

class FuncionarioService
{
    public static function paginate(int $page = 1, int $limit = 10, ?string $codfilial = null): array
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));

        return FuncionarioModel::all($page, $limit, $codfilial);
    }

    public static function ativos(
        int $page = 1,
        int $limit = 10,
        ?string $codfilial = null,
        ?string $secao = null,
        ?string $situacao = null,
        ?string $nome = null
    ): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));

        return FuncionarioModel::ativos($page, $limit, $codfilial, $secao, $situacao, $nome);
    }

    public static function findByChapa(string $chapa): array|null
    {
        $result = FuncionarioModel::findByChapa($chapa);

        if (!$result) {
            throw new ApiException('Funcionário não encontrado', 404);
        }

        return $result;
    }

    public static function findByNome(string $nome): array|null
    {
        return FuncionarioModel::findByNome($nome);
    }

    public static function findByCpfDados(string $cpf): array|null
    {
        return FuncionarioModel::findByCpfDados($cpf);
    }

    public static function dataFerias(string $chapa): ?array
    {
        $result = FuncionarioModel::dataFerias($chapa);

        if (!$result) {
            throw new ApiException('Funcionário não encontrado', 404);
        }

        return $result;
    }

    public static function aniversariantes(string $mes, ?string $codfilial = null): array
    {
        return FuncionarioModel::aniversariantes($mes, $codfilial);
    }

    public static function ti(int $page = 1, int $limit = 10): array
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));

        return FuncionarioModel::ti($page, $limit);
    }

    public static function listaTI(int $page = 1, int $limit = 10): array
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));

        return FuncionarioModel::listaTI($page, $limit);
    }

    public static function exportTi(): array
    {
        return FuncionarioModel::exportTi();
    }

    public static function admissoes(): array
    {
        return FuncionarioModel::admissoes();
    }

    public static function listAllFuncs(): array
    {
        return FuncionarioModel::listAllFuncs();
    }

    public static function listAllFuncoes(): array
    {
        return FuncaoModel::listAllFuncoes();
    }

    public static function listaCompradores(): array|null
    {
        return FuncionarioModel::listaCompradores();
    }

    public static function feriasComercial(): array|null
    {
        return FuncionarioModel::feriasComercial();
    }

}
