<?php

namespace App\Core\Auth;

use App\Core\Auth\Exceptions\InvalidSignatureException;
use App\Core\Auth\Exceptions\InvalidTokenException;
use App\Core\Auth\Exceptions\TokenExpiredException;

class Jwt
{
    protected string $secret;
    protected string $algo = 'sha256';

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function encode(array $payload): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        $header = $this->base64UrlEncode(json_encode($header));
        $payload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            $this->algo,
            "{$header}.{$payload}",
            $this->secret,
            true
        );

        $signature = $this->base64UrlEncode($signature);

        return "{$header}.{$payload}.{$signature}";
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if(count($parts) !== 3) {
            throw new InvalidTokenException();
        }

        [$header, $payload, $signature] = $parts;

        $expected = $this->base64UrlEncode(
            hash_hmac(
                $this->algo,
                "{$header}.{$payload}",
                $this->secret,
                true
            )
        );

        if(!hash_equals($expected, $signature)) {
            throw new InvalidSignatureException();
        }

        $payload = json_decode(
            $this->base64UrlDecode($payload),
            true
        );

        if (
            isset($payload['exp']) &&
            time() >= $payload['exp']
        ) {
            throw new TokenExpiredException();
        }

        return $payload;
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;

        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }
}