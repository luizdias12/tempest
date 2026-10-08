<?php

namespace App\Service;

use App\Model\Taf\TAFModel;

class TAFService
{
    public static function get2230(string $competencia): array|null
    {
        return TAFModel::get2230($competencia);
    }
}