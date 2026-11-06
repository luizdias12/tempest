<?php

namespace App\Model\Mysql;

use App\Core\DB;

class MessageModel
{
    public static function aleatoria(): ?array
    {
        return DB::first(
            'SELECT id, mensagem FROM messages ORDER BY RAND() LIMIT 1',
            [],
            'mysql'
        );
    }
}