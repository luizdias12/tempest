<?php

namespace App\Service;

use App\Model\Mysql\StatusModel;

class StatusService
{
    public static function all(): array|null
    {
        return StatusModel::all();
    }
}