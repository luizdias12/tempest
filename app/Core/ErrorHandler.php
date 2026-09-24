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
        bool $isApi = false,
        ?Throwable $exception = null
    ): void {
        http_response_code($statusCode);

        $currentUri = $_SERVER['REQUEST_URI'] ?? '/';
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';

        if ($statusCode >= 500) {
            self::registrarLogBanco($statusCode, $message, $exception);
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

        (new ErrorController())->indexView(
            $statusCode, 
            $message, 
            $currentUri,
            $referer
        );
    }

    private static function registrarLogBanco(int $statusCode, string $message, ?Throwable $exception = null): void
    {
        try {
            $contexto = [
                'status_code' => $statusCode,
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
                'metodo_http' => $_SERVER['REQUEST_METHOD'] ?? null,
                'referer' => $_SERVER['HTTP_REFERER'] ?? null,
                'origem' => self::origemChamada($exception),
            ];

            if ($exception !== null) {
                $contexto['exception'] = get_class($exception);
                $contexto['exception_code'] = $exception->getCode();
                $contexto['exception_file'] = $exception->getFile();
                $contexto['exception_line'] = $exception->getLine();
                $contexto['trace'] = mb_substr($exception->getTraceAsString(), 0, 6000);
            }

            $entrada = self::sanitizaEntrada(array_merge($_GET ?? [], ['_post' => ($_POST ?? [])]));
            $contexto['entrada'] = mb_substr((string) json_encode($entrada, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 4000);

            LogService::store([
                'nivel' => 'ERROR',
                'tipo' => 'HTTP',
                'modulo' => 'error',
                'acao' => 'handle_' . $statusCode,
                'mensagem' => $message,
                'contexto' => $contexto,
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);
        }
    }

    private static function origemChamada(?Throwable $exception): ?string
    {
        if ($exception !== null) {
            return $exception->getFile() . ':' . $exception->getLine();
        }

        $frame = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2] ?? null;

        return $frame !== null
            ? ($frame['file'] ?? '') . ':' . ($frame['line'] ?? '')
            : null;
    }

    private static function sanitizaEntrada(array $entrada): array
    {
        $resultado = [];

        foreach ($entrada as $chave => $valor) {
            if (is_array($valor)) {
                $resultado[$chave] = self::sanitizaEntrada($valor);
                continue;
            }

            if (preg_match('/senha|password|token|authorization|cookie|api_?key|secret/i', (string) $chave)) {
                $resultado[$chave] = '******';
                continue;
            }

            $resultado[$chave] = is_scalar($valor) ? (string) $valor : '';
        }

        return $resultado;
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