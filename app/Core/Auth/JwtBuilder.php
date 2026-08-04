<?php

namespace App\Core\Auth;

use App\Core\Auth\JwtManager;

class JwtBuilder
{
    protected array $claims = [];

    protected JwtManager $manager;

    public function __construct(JwtManager $manager)
    {
        $this->manager = $manager;
    }

    public function subject($id): self
    {
        $this->claims['sub'] = $id;

        return $this;
    }

    public function issuer(string $issuer): self
    {
        $this->claims['iss'] = $issuer;

        return $this;
    }

    public function audience(string $audience): self
    {
        $this->claims['aud'] = $audience;

        return $this;
    }

    public function claim(string $key, mixed $value): self
    {
        $this->claims[$key] = $value;

        return $this;
    }

    public function expiresIn(int $seconds): self
    {
        $this->claims['exp'] = time() + $seconds;

        return $this;
    }

    public function encode(): string
    {
        return $this->manager->encode($this->claims);
    }
}