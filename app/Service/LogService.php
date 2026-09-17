<?php

namespace App\Service;

use App\Model\Mysql\LogModel;

class LogService
{
    public static function index(
        int $page = 1,
        int $limit = 20,
        ?string $nivel = null,
        ?string $tipo = null,
        ?string $modulo = null,
        ?string $busca = null,
        ?string $data = null,
        ?string $ip = null
    ): array
    {
        return LogModel::index($page, $limit, $nivel, $tipo, $modulo, $busca, $data, $ip);
    }

    public static function store(array $data): ?int
    {
        $user = AuthService::getUser() ?? [];

        $data['usuario_id'] = $data['usuario_id'] ?? ($user['id'] ?? null);
        $data['chapa'] = $data['chapa'] ?? ($user['chapa'] ?? null);
        $data['usuario_nome'] = $data['usuario_nome'] ?? ($user['name'] ?? $user['username'] ?? null);
        $data['metodo_http'] = $data['metodo_http'] ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $data['rota'] = $data['rota'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $data['ip'] = $data['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        $data['user_agent'] = $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);

        return LogModel::store($data);
    }
}
