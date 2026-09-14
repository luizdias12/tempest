<?php

namespace App\Model\Mysql;

use App\Core\QueryBuilder;

class FilialModel
{
    public static function all(): ?array
    {
        return QueryBuilder::table('filial', 'mysql')
            ->select('codfilial', 'codgfilial', 'nome', 'rede', 'CONCAT(nome, " (Rede - ",rede,")") AS descricao')
            ->where('exibir', 'S')
            ->orderBy('nome', 'ASC')
            ->get();
    }
}
