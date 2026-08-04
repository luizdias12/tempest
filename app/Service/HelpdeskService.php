<?php

namespace App\Service;

use App\Model\Mysql\HelpdeskModel;

class HelpdeskService
{
    public static function chamadosAbertos(
        int $page = 1,
        int $limit = 10,
        ?string $id = null,
        ?string $emitente = null,
        ?string $status = null,
        ?string $local = null
        ): array|null
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        
        return HelpdeskModel::chamadosAbertos($page, $limit, $id, $emitente, $status, $local);
    }
}