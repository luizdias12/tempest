<?php

namespace App\Core\Alerts;

class AlertManager
{
    public static function add(
        string $type,
        string $message
    ): void {
        $_SESSION['alerts'][] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public static function get(): array
    {
        $alerts = $_SESSION['alerts'] ?? [];

        unset($_SESSION['alerts']);

        return $alerts;
    }
}