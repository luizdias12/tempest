<?php

namespace App\Middleware;

use App\Core\ErrorHandler;
use App\Service\AuthService;

class RoleMiddleware
{
    public function handle(): bool
    {
        if (!AuthService::haspermission('ti')) {
            ErrorHandler::handle(403, 'Acesso não permitido!', false);
            return false;
        }

        return true;
    }
}