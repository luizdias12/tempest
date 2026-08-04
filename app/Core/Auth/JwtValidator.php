<?php

namespace App\Core\Auth;

use App\Core\Auth\Exceptions\InvalidAudienceException;
use App\Core\Auth\Exceptions\InvalidIssuerException;
use App\Core\Auth\Exceptions\TokenExpiredException;
use App\Core\Auth\Exceptions\TokenNotYetValidException;

class JwtValidator
{
    public function __construct(
        protected JwtConfig $config
    ) {
    }

    /**
     * Valida todas as claims.
     */
    public function validate(array $payload): void
    {
        $this->validateExpiration($payload);
        $this->validateNotBefore($payload);
        $this->validateIssuedAt($payload);
        $this->validateIssuer($payload);
        $this->validateAudience($payload);
    }

    /**
     * exp
     */
    protected function validateExpiration(array $payload): void
    {
        if (
            isset($payload['exp']) &&
            time() >= $payload['exp']
        ) {
            throw new TokenExpiredException();
        }
    }

    /**
     * nbf
     */
    protected function validateNotBefore(array $payload): void
    {
        if (
            isset($payload['nbf']) &&
            time() < $payload['nbf']
        ) {
            throw new TokenNotYetValidException();
        }
    }

    /**
     * iat
     */
    protected function validateIssuedAt(array $payload): void
    {
        if (
            isset($payload['iat']) &&
            $payload['iat'] > time()
        ) {
            throw new TokenNotYetValidException();
        }
    }

    /**
     * iss
     */
    protected function validateIssuer(array $payload): void
    {
        if (
            $this->config->issuer !== '' &&
            isset($payload['iss']) &&
            $payload['iss'] !== $this->config->issuer
        ) {
            throw new InvalidIssuerException();
        }
    }

    /**
     * aud
     */
    protected function validateAudience(array $payload): void
    {
        if (
            $this->config->audience !== '' &&
            isset($payload['aud']) &&
            $payload['aud'] !== $this->config->audience
        ) {
            throw new InvalidAudienceException();
        }
    }
}