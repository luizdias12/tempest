<?php

namespace App\Core\Auth\Exceptions;

class InvalidIssuerException extends JwtException
{
    public function __construct()
    {
        parent::__construct('Issuer inválido.', 400);
    }
}