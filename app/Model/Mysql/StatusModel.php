<?php

namespace App\Model\Mysql;

use App\Core\QueryBuilder;

class StatusModel
{
    public static function all(): ?array
    {
        return QueryBuilder::table('status', 'mysql')
            ->select('status', 'descricao')
            ->orderBy('"order"', 'ASC')
            ->get();
    }
}