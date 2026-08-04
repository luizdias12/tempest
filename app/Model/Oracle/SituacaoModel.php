<?php

namespace App\Model\Oracle;

use App\Core\DB;
use App\Core\QueryBuilder;

class SituacaoModel extends DB
{
    public static function all(): ?array
    {
        return QueryBuilder::table('pcodsituacao')
            ->select('codinterno', 'descricao')
            ->orderBy('descricao', 'ASC')
            ->get();
    }
}