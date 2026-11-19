<?php

namespace App\Model\Oracle;

use App\Core\DB;
use App\Core\QueryBuilder;

class FilialModel extends DB
{
    public static function all(): ?array
    {
        return QueryBuilder::table('gfilial')
            ->select('codfilial', 'vilnomefilial')
            ->orderBy('codfilial', 'ASC')
            ->get();
    }

    public static function todas(): ?array
    {
        return QueryBuilder::table('gfilial')
            ->select('codfilial', 'vilnomefilial')
            ->orderBy('vilnomefilial', 'ASC')
            ->get();
    }
}