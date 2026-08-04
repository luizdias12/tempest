<?php

namespace App\Core\Auth\Exceptions;

class InvalidAudienceException extends JwtException
{
    public function __construct()
    {
        parent::__construct('Audience inválida.', 400);
    }
}