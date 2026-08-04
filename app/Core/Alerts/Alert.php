<?php

namespace App\Core\Alerts;

class Alert
{
    public function __construct(
        public string $type,
        public string $message
    ) {}
}