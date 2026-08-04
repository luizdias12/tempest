<?php

namespace App\Middleware;

use App\Core\Auth\Exceptions\JwtException;
use App\Core\Facades\JWT;
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

        try {
            $payload = JWT::decode($token);
            $request->setAttribute('user', $payload);
        } catch (JwtException $e) {
            Response::json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 401);

            return false;
        }

        return true;
    }
}