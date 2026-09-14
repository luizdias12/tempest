<?php

namespace App\Model\Oracle;

use App\Core\DB;
use App\Core\QueryBuilder;

class FuncaoModel extends DB
{
    public static function listAllFuncoes(): array
    {
        return QueryBuilder::table('pfuncao')
            ->select('codfuncao', 'descricao')
            ->orderBy('descricao', 'asc')
            ->get();
    }
}