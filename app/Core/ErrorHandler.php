<?php

namespace App\Core;

use App\Controller\ErrorController;
use App\Service\LogService;
use Throwable;

class ErrorHandler
{
    public static function handle(
        int $statusCode = 500,
        string $message = 'Erro interno',
        bool $isApi = false
    ): void {
        http_response_code($statusCode);

        if ($statusCode >= 500) {
            self::registrarLogBanco($statusCode, $message);
        }

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

    private static function registrarLogBanco(int $statusCode, string $message): void
    {
        try {
            LogService::store([
                'nivel' => 'ERROR',
                'tipo' => 'HTTP',
                'modulo' => 'error',
                'acao' => 'handle_' . $statusCode,
                'mensagem' => $message,
                'contexto' => [
                    'status_code' => $statusCode,
                    'uri' => $_SERVER['REQUEST_URI'] ?? null,
                    'metodo_http' => $_SERVER['REQUEST_METHOD'] ?? null,
                ],
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);
        }
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