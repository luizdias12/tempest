<?php

namespace App\Service;

use App\Model\Mysql\ComercialModel;
use DateTime;
use RuntimeException;

class ComercialService
{
    public static function listar(?string $mesRef = null): array
    {
        return ComercialModel::listar($mesRef);
    }

    public static function listarCompradores(): array
    {
        return ComercialModel::listarCompradores();
    }

    public static function salvar(?int $id, string $data, string $comprador, string $feriado = ''): string
    {
        $data = trim($data);
        $cpf = preg_replace('/\D/', '', $comprador);

        if (!self::dataValida($data)) {
            throw new RuntimeException('Informe uma data válida.');
        }

        if (strlen($cpf) !== 11) {
            throw new RuntimeException('Informe o CPF do comprador.');
        }

        $func = GenericService::buscaFuncPorCpf($cpf);

        if (empty($func)) {
            throw new RuntimeException('Comprador não encontrado (CPF inválido).');
        }

        $feriado = trim($feriado);

        $dados = [
            'mesref' => substr($data, 5, 2),
            'data' => $data,
            'comprador' => $cpf,
            'feriado' => $feriado !== '' ? $feriado : null,
        ];

        if ($id !== null && $id > 0) {
            if (ComercialModel::buscar($id) === null) {
                throw new RuntimeException('Registro não encontrado.');
            }

            ComercialModel::atualizar($id, $dados);

            return 'Plantão atualizado com sucesso.';
        }

        $novoId = ComercialModel::inserir($dados);

        if ($novoId === null) {
            throw new RuntimeException('Falha ao cadastrar o plantão.');
        }

        return 'Plantão cadastrado com sucesso.';
    }

    public static function excluir(int $id): string
    {
        if (ComercialModel::buscar($id) === null) {
            throw new RuntimeException('Registro não encontrado.');
        }

        ComercialModel::excluir($id);

        return 'Plantão excluído.';
    }

    private static function dataValida(string $data): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $data);

        return $d !== false && $d->format('Y-m-d') === $data;
    }
}