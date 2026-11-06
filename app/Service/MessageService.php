<?php

namespace App\Service;

use App\Model\Mysql\MessageModel;

class MessageService
{
    public static function aleatoria(): ?array
    {
        return MessageModel::aleatoria();
    }
}