<?php

namespace App\Model\Mysql;

use App\Core\DB;
use App\Core\QueryBuilder;

class LogModel
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
        $query = QueryBuilder::table('logs', 'mysql');

        if (!empty($nivel)) {
            $query->where('nivel', $nivel);
        }

        if (!empty($tipo)) {
            $query->whereILike('tipo', $tipo);
        }

        if (!empty($modulo)) {
            $query->whereILike('modulo', $modulo);
        }

        if (!empty($busca)) {
            $query->whereGroup(static function ($q) use ($busca) {
                $q->whereILike('mensagem', $busca)
                    ->whereILike('rota', $busca, 'OR')
                    ->whereILike('usuario_nome', $busca, 'OR');
            });
        }

        if (!empty($data)) {
            $query->whereLike('created_at', $data);
        }

        if (!empty($ip)) {
            $query->where('ip', $ip);
        }

        return $query->orderBy('id', 'DESC')->paginate($page, $limit);
    }

    public static function store(array $data): ?int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');

        if (isset($data['contexto']) && is_array($data['contexto'])) {
            $data['contexto'] = json_encode($data['contexto'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return DB::insert('logs', $data, 'mysql');
    }
}