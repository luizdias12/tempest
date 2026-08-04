<?php

namespace App\Core\Auth;

class JwtConfig
{
    public function __construct(
        public readonly string $secret,
        public readonly string $issuer = '',
        public readonly string $audience = '',
        public readonly int $ttl = 3600,
        public readonly string $algorithm = 'HS256'
    ) {}
}
