<?php

namespace App\Service;

use App\Model\Mysql\ContatoModel;

class ContatoService
{
    public static function listar(
        int $page,
        int $limit,
        ?string $nome,
        ?string $email,
        ?string $ramal,
        ?string $setor,
        ?string $filial
    ): array
    {
        return ContatoModel::listar($page, $limit, $nome, $email, $ramal, $setor, $filial);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function buscarPorNomeSetor(string $nome, ?string $setor = null, int $limite = 200): array
    {
        return ContatoModel::buscarPorNomeSetor($nome, $setor, $limite);
    }

    /**
     * @return array<int, array{id: int|string, setor: string}>
     */
    public static function listarSetores(): array
    {
        return ContatoModel::listarSetores();
    }
}
