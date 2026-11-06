<?php

namespace App\Service;

use App\Model\Oracle\FilialModel;

class FilialService
{
    public static function all(): ?array
    {
        return FilialModel::all();
    }
}