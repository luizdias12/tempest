<?php

namespace App\Service;

use App\Model\FilialModel;

class FilialService
{
    public static function all(): ?array
    {
        return FilialModel::all();
    }
}