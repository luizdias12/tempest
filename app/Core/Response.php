<?php

namespace App\Core;

class Response
{
    public static function json(array $data, int $status = 200, array $headers = []): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        http_response_code($status);

        header('Content-Type: application/json; charset=utf-8');

        foreach ($headers as $key => $value) {
            header("$key: $value");
        }

        echo $json;
    }

    public static function text(string $content, int $status = 200, string $contentType = 'text/plain; charset=utf-8'): void
    {
        http_response_code($status);
        header("Content-Type: $contentType");
        
        echo $content;
    }

    public static function noContent(): void
    {
        http_response_code(204);
    }
}