<?php

namespace App\Core;

use Exception;
use Throwable;

class ApiException extends Exception
{
    protected array $details;

    public function __construct(
        string $message, 
        int $code = 400, 
        array $details = [], 
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}