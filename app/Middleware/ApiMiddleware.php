<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class ApiMiddleware
{
    public function handle(Request $request): bool
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            Response::json([
                'success' => false,
                'message' => 'Token não informado.'
            ], 401);

            return false;
        }

        // Validar JWT ou token de acesso aqui.

        return true;
    }
}