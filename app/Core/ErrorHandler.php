<?php

namespace App\Core;

use App\Controller\ErrorController;

class ErrorHandler
{
    public static function handle(
        int $statusCode = 500,
        string $message = 'Erro interno',
        bool $isApi = false
    ): void {
        http_response_code($statusCode);

        if ($isApi) {
            Response::json([
                'success' => false,
                'data' => null,
                'meta' => [],
                'error' => [
                    'code' => $statusCode,
                    'message' => $message
                ]
            ], $statusCode);

            return;
        }

        (new ErrorController())->indexView($statusCode, $message);
    }

    public static function notFound(
        string $message = 'Rota não encontrada',
        bool $isApi = false
    ): void {
        self::handle(404, $message, $isApi);
    }

    public static function serverError(
        string $message = 'Erro interno do servidor',
        bool $isApi = false
    ): void {
        self::handle(500, $message, $isApi);
    }

    public static function forbidden(
        string $message = 'Acesso negado',
        bool $isApi = false
    ): void {
        self::handle(403, $message, $isApi);
    }

    public static function unauthorized(
        string $message = 'Não autenticado',
        bool $isApi = false
    ): void {
        self::handle(401, $message, $isApi);
    }

    public static function badRequest(
        string $message = 'Requisição inválida',
        bool $isApi = false
    ): void {
        self::handle(400, $message, $isApi);
    }
}