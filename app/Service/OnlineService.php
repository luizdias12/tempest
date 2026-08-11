<?php

namespace App\Service;

use App\Model\Mysql\OnlineModel;

class OnlineService
{
    public static function registrarLogin(string $cpf, string $sessionId, string $ip, string $local): bool
    {
        return OnlineModel::registrarLogin($cpf, $sessionId, $ip, $local);
    }

    public static function heartbeat(string $sessionId): bool
    {
        return OnlineModel::heartbeat($sessionId);
    }

    public static function registrarLogout(string $cpf): bool
    {
        return OnlineModel::registrarLogout($cpf);
    }

    public static function forcarLogout(string $cpf): bool
    {
        return OnlineModel::registrarLogout($cpf);
    }

    public static function situacaoSessao(string $sessionId, string $cpf): ?string
    {
        return OnlineModel::situacaoSessao($sessionId, $cpf);
    }

    public static function listarOnline(int $page = 1, int $limit = 20, ?string $busca = null, ?string $local = null): array
    {
        return OnlineModel::listarOnline($page, $limit, $busca, $local);
    }
}
