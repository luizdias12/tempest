<?php

namespace App\Middleware;

use App\Core\ErrorHandler;
use App\Service\AuthService;

class ComercialRoleMiddleware
{
    public function handle(): bool
    {
        if (!AuthService::canManageComercial()) {
            ErrorHandler::handle(403, 'Acesso não permitido!', false);
            return false;
        }

        return true;
    }
}