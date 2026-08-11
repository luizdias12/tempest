<?php

namespace App\Middleware;

use App\Service\AuthService;
use App\Service\OnlineService;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!AuthService::isAuthenticated()) {
            redirect('/login');
            return false;
        }

        $user = AuthService::getUser() ?? [];
        $cpf = (string) ($user['cpf'] ?? '');
        $sessionId = session_id();

        if ($cpf !== '' && $sessionId !== '') {
            $status = OnlineService::situacaoSessao($sessionId, $cpf);

            if ($status === '2') {
                AuthService::logout();
                redirect('/login');
                return false;
            }

            if ($status === null) {
                OnlineService::registrarLogin($cpf, $sessionId, $_SERVER['REMOTE_ADDR'] ?? '', localPorIp($_SERVER['REMOTE_ADDR'] ?? ''));
            } else {
                OnlineService::heartbeat($sessionId);
            }
        }

        return true;
    }
}
