<?php

namespace App\Core\Auth\Exceptions;

class InvalidTokenException extends JwtException
{
    public function __construct()
    {
        parent::__construct('Token inválido.', 400);
    }
}