<?php

namespace App\Middleware;

use App\Core\ErrorHandler;
use App\Service\AuthService;

class RHMiddleware
{
    public function handle(): bool
    {
        if (!AuthService::canManageCarousel()) {
            ErrorHandler::handle(403, 'Acesso não permitido!', false);
            return false;
        }

        return true;
    }
}