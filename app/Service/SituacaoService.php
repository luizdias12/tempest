<?php

namespace App\Service;

use App\Model\SituacaoModel;
use App\Core\ApiException;

class SituacaoService
{
    public static function all(): ?array
    {
        return SituacaoModel::all();
    }
}