<?php

namespace App\Service;

use App\Model\Mysql\RelatorioModel;

class RelatorioService
{
    public static function resolvidosPorTecnico(?string $data = null): array
    {
        return RelatorioModel::resolvidosPorTecnico($data);
    }
}
