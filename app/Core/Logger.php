<?php

namespace App\Core;

use Throwable;

class Logger
{
    private static string $dir = __DIR__ . '/../../logs';

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function exception(Throwable $e, array $context = []): void
    {
        self::error(
            $e->getMessage(),
            array_merge($context, [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ])
        );
    }

    private static function write(string $level, string $message, array $context): void
    {
        $dir = self::$dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $dir . '/' . date('Y-m-d') . '.log';
        $json = json_encode(
            $context,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
        );
        $line = sprintf(
            '[%s] [%s]: %s %s%s',
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $json ?: '',
            PHP_EOL
        );

        $matches = @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        if ($matches === false) {
            @error_log(trim($line));

            if (defined('STDERR')) {
                @fwrite(STDERR, $line);
            }
        }
    }
}
