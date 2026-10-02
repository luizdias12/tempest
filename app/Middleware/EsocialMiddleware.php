<?php

namespace App\Middleware;

use App\Core\ErrorHandler;
use App\Service\AuthService;

class EsocialMiddleware
{
    public function handle(): bool
    {
        if (!AuthService::canManageEsocial()) {
            ErrorHandler::handle(403, 'Acesso não permitido!', false);
            return false;
        }

        return true;
    }
}