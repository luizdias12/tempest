<?php

namespace App\Core\Facades;

use App\Core\Auth\JwtConfig;
use App\Core\Auth\JwtManager;
use App\Core\Auth\JwtValidator;

class JWT
{
    protected static ?JwtManager $manager = null;

    protected static function manager(): JwtManager
    {
        if (static::$manager === null) {
            $secret = $_ENV['JWT_SECRET'] ?? '';
            $config = new JwtConfig(
                secret: $secret,
                issuer: $_ENV['JWT_ISSUER'] ?? '',
                audience: $_ENV['JWT_AUDIENCE'] ?? '',
                ttl: (int) ($_ENV['JWT_TTL'] ?? 3600),
                algorithm: $_ENV['JWT_ALGORITHM'] ?? 'HS256',
            );
            $validator = new JwtValidator($config);
            static::$manager = new JwtManager($config, $validator);
        }

        return static::$manager;
    }

    public static function encode(array $claims): string
    {
        return static::manager()->encode($claims);
    }

    public static function decode(string $token): \App\Core\Auth\JwtPayload
    {
        return static::manager()->decode($token);
    }

    public static function builder(): \App\Core\Auth\JwtBuilder
    {
        return static::manager()->builder();
    }

    public static function setManager(JwtManager $manager): void
    {
        static::$manager = $manager;
    }
}
