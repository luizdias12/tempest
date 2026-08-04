<?php

namespace App\Core\Auth;

use App\Core\Auth\Exceptions\InvalidSignatureException;
use App\Core\Auth\Exceptions\InvalidTokenException;

class JwtManager
{
    public function __construct(
        protected JwtConfig $config,
        protected JwtValidator $validator
    ) {
    }

    public function encode(array $claims): string
    {
        $now = time();

        $claims['iat'] ??= $now;
        $claims['exp'] ??= $now + $this->config->ttl;

        if ($this->config->issuer !== '') {
            $claims['iss'] ??= $this->config->issuer;
        }

        if ($this->config->audience !== '') {
            $claims['aud'] ??= $this->config->audience;
        }

        $header = [
            'typ' => 'JWT',
            'alg' => $this->config->algorithm,
        ];

        $header = $this->base64UrlEncode(
            json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $payload = $this->base64UrlEncode(
            json_encode($claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $signature = $this->sign("$header.$payload");

        return "$header.$payload.$signature";
    }

    public function decode(string $token): JwtPayload
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new InvalidTokenException();
        }

        [$header, $payload, $signature] = $parts;

        $headerArray = json_decode(
            $this->base64UrlDecode($header),
            true
        );

        $payloadArray = json_decode(
            $this->base64UrlDecode($payload),
            true
        );

        if (!is_array($headerArray) || !is_array($payloadArray)) {
            throw new InvalidTokenException();
        }

        if (!$this->verify($header, $payload, $signature)) {
            throw new InvalidSignatureException();
        }

        $this->validator->validate($payloadArray);

        return new JwtPayload($payloadArray);
    }

    public function builder(): JwtBuilder
    {
        return new JwtBuilder($this);
    }

    protected function sign(string $data): string
    {
        return $this->base64UrlEncode(
            hash_hmac(
                $this->resolveAlgorithm(),
                $data,
                $this->config->secret,
                true
            )
        );
    }

    protected function resolveAlgorithm(): string
    {
        return match ($this->config->algorithm) {
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
            default => 'sha256',
        };
    }

    protected function verify(
        string $header,
        string $payload,
        string $signature
    ): bool {

        $expected = $this->sign("$header.$payload");

        return hash_equals($expected, $signature);
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }

    protected function base64UrlDecode(string $data): string
    {
        $padding = strlen($data) % 4;

        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(
            strtr($data, '-_', '+/')
        );
    }
}