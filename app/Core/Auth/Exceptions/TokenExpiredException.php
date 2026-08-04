<?php

namespace App\Core\Auth\Exceptions;

class TokenExpiredException extends JwtException
{
    public function __construct()
    {
        parent::__construct('O token expirou.', 401);
    }
}