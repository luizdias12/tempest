<?php

namespace App\Middleware;

use App\Core\Request;
use App\Service\AuthService;

class AuthMiddleware
{
    public function handle(Request $request): bool
    {
        if (!AuthService::isAuthenticated()) {
            redirect('/login');
            return false;
        }

        return true;
    }
}