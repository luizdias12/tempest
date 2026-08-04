<?php

namespace App\Core\Auth\Exceptions;

class InvalidSignatureException extends JwtException
{
    public function __construct()
    {
        parent::__construct('Assinatura do token inválida.', 401);
    }
}