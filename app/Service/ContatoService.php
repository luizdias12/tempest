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
}
