<?php

namespace App\Core;

use Throwable;

class BaseController
{
    protected function success($data = null, array $meta = [], ?string $message = null): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => date('c')
            ], $meta),
            'error' => null
        ];
    }

    protected function error(string $message, int $code = 400, array $details = []): array
    {
        return [
            'success' => false,
            'message' => null,
            'data' => null,
            'meta' => [
                'timestamp' => date('c')
            ],
            'error' => [
                'message' => $message,
                'code' => $code,
                'details' => $details
            ]
        ];
    }

    protected function handle(callable $callback): array
    {
        try {
            return $callback();
        } catch (ApiException $e) {
            Logger::exception($e);
            
            return $this->error(
                $e->getMessage(),
                $e->getCode() ?: 400,
                method_exists($e, 'getDetails') ? $e->getDetails() : []
            );
        } catch (Throwable $e) {
            Logger::exception($e);

            return $this->error('Erro interno do servidor', 500);
        }
    }
}
