<?php

namespace App\Service;

use App\Model\Mysql\GenericModel;

class GenericService
{
    public static function listarLocais(): array
    {
        return GenericModel::listarLocais();
    }

    public static function obterTemaUsuario(string $cpf): array|null
    {
        return GenericModel::obterTemaUsuario($cpf);
    }

    public static function listaMotivosCancelamento(): array
    {
        return GenericModel::listaMotivosCancelamento();
    }

    public static function obterTextoCancelamento(int $idMotivo): string
    {
        return GenericModel::obterTextoCancelamento($idMotivo);
    }

    public static function buscaFuncExterno(string $nome): array|null
    {
        return GenericModel::buscaFuncExterno($nome);
    }

    public static function buscaFuncExternoPorCpf(string $cpf): array|null
    {
        return GenericModel::buscaFuncExternoPorCpf($cpf);
    }
}