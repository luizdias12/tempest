<?php

namespace App\Core\Auth;

class JwtPayload
{
    protected array $claims = [];

    public function __construct(array $claims)
    {
        $this->claims = $claims;
    }

    public function claim(string $name): mixed
    {
        return $this->claims[$name] ?? null;
    }

    public function subject(): mixed
    {
        return $this->claim('sub');
    }

    public function all(): array
    {
        return $this->claims;
    }

    public function has(string $claim): bool
    {
        return isset($this->claims[$claim]);
    }
}