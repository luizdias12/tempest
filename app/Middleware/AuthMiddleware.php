<?php

namespace App\Middleware;

use App\Service\AuthService;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!AuthService::isAuthenticated()) {
            redirect('/login');
            return false;
        }

        return true;
    }
}