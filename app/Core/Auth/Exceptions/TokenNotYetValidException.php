<?php

namespace App\Core\Auth\Exceptions;

class TokenNotYetValidException extends JwtException
{
    public function __construct()
    {
        parent::__construct('Token ainda não é válido.', 400);
    }
}