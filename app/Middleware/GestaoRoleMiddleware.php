<?php

namespace App\Middleware;

use App\Core\ErrorHandler;
use App\Service\AuthService;

class GestaoRoleMiddleware
{
    public function handle(): bool
    {
        if (
            !AuthService::hasPermission('gestao de processos') &&
            !AuthService::hasPermission('ti')
            ) {
            ErrorHandler::handle(403, 'Acesso não permitido!', false);
            return false;
        }

        return true;
    }
}