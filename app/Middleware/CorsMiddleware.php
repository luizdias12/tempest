<?php

namespace App\Middleware;

use App\Core\Request;

class CorsMiddleware
{
    public function handle(Request $request): bool
    {
        header('Access-Control-Allow-Origin: http://192.168.101.16:8082');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // importante para preflight
        if ($request->method() === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        return true;
    }
}