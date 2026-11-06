<?php

namespace App\Service;

use App\Model\Oracle\SituacaoModel;

class SituacaoService
{
    public static function all(): ?array
    {
        return SituacaoModel::all();
    }
}