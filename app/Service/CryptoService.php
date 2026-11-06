<?php

namespace App\Service;

class CryptoService
{
    private const PREFIXO = 'EP1:';

    public static function criptografar(string $texto): string
    {
        if ($texto === '') {
            return '';
        }

        $iv = random_bytes(12);
        $tag = '';
        $cifrado = openssl_encrypt($texto, 'aes-256-gcm', self::chave(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($cifrado === false) {
            throw new \RuntimeException('Falha ao criptografar a mensagem.', 500);
        }

        return self::PREFIXO . base64_encode($iv . $tag . $cifrado);
    }

    public static function descriptografar(?string $texto): ?string
    {
        if ($texto === null || $texto === '' || !str_starts_with($texto, self::PREFIXO)) {
            return $texto;
        }

        $dados = base64_decode(substr($texto, strlen(self::PREFIXO)), true);

        if ($dados === false || strlen($dados) < 29) {
            return '';
        }

        $iv = substr($dados, 0, 12);
        $tag = substr($dados, 12, 16);
        $cifrado = substr($dados, 28);

        $claro = openssl_decrypt($cifrado, 'aes-256-gcm', self::chave(), OPENSSL_RAW_DATA, $iv, $tag);

        return $claro === false ? '' : $claro;
    }

    private static function chave(): string
    {
        static $chave = null;

        if ($chave !== null) {
            return $chave;
        }

        $raw = self::env('CHAT_KEY', '');

        if ($raw === '') {
            throw new \RuntimeException('CHAT_KEY não configurada.', 500);
        }

        $decodificada = base64_decode($raw, true);

        if ($decodificada !== false && strlen($decodificada) === 32) {
            return $chave = $decodificada;
        }

        return $chave = hash('sha256', $raw, true);
    }

    private static function env(string $key, string $default = ''): string
    {
        return getenv($key) ?: ($_ENV[$key] ?? $default);
    }
}