<?php

namespace App\Model\Mysql;

use App\Core\DB;
use PDO;
use PDOException;

class OnlineModel
{
    public static function registrarLogin(string $cpf, string $sessionId, string $ip, string $local): bool
    {
        $stmt = DB::connect('mysql')->prepare("
            INSERT INTO online (cpf, dt_login, status, ip, sessionid, local)
            VALUES (:cpf, NOW(), '1', :ip, :sid, :local)
            ON DUPLICATE KEY UPDATE
                dt_login = NOW(),
                status = '1',
                ip = :ip2,
                sessionid = :sid2,
                local = :local2
        ");

        return self::executarComTentativa($stmt, [
            'cpf' => $cpf,
            'ip' => $ip,
            'sid' => $sessionId,
            'local' => $local,
            'ip2' => $ip,
            'sid2' => $sessionId,
            'local2' => $local,
        ]);
    }

    public static function heartbeat(string $sessionId): bool
    {
        $stmt = DB::connect('mysql')->prepare("
            UPDATE online
            SET dt_login = NOW()
            WHERE sessionid = :sid
            AND status = '1'
            AND dt_login < NOW() - INTERVAL 60 SECOND
        ");

        return self::executarComTentativa($stmt, ['sid' => $sessionId]);
    }

    public static function registrarLogout(string $cpf): bool
    {
        $stmt = DB::connect('mysql')->prepare("
            UPDATE online
            SET status = '2', dt_logoff = NOW()
            WHERE cpf = :cpf
            AND status = '1'
        ");

        return self::executarComTentativa($stmt, ['cpf' => $cpf]);
    }

    public static function situacaoSessao(string $sessionId, string $cpf): ?string
    {
        $row = DB::first("
            SELECT status
            FROM online
            WHERE sessionid = :sid
            AND cpf = :cpf
            LIMIT 1
        ", ['sid' => $sessionId, 'cpf' => $cpf], 'mysql');

        return $row['status'] ?? null;
    }

    public static function listarOnline(int $page = 1, int $limit = 20, ?string $busca = null, ?string $local = null): array
    {
        $query = DB::select("
            SELECT o.cpf,
                   o.dt_login,
                   o.ip,
                   o.sessionid,
                   o.local,
                   COALESCE(f.nome, fe.nome, 'Nao Identificado') AS nome,
                   fu.nome AS funcao
            FROM online o
            LEFT JOIN func f ON f.cpf = o.cpf
            LEFT JOIN func_externo fe ON fe.cpf = o.cpf
            LEFT JOIN funcao fu ON fu.codigo = f.codfuncao
            WHERE o.status = '1'
        ", [], 'mysql');

        $query = array_filter($query, static function (array $row) use ($busca, $local) {
            if (!empty($busca)) {
                $busca = mb_strtolower($busca);
                $nome = mb_strtolower($row['nome'] ?? '');
                if (strpos($nome, $busca) === false && strpos($row['cpf'], $busca) === false) {
                    return false;
                }
            }

            if (!empty($local) && ($row['local'] ?? '') !== $local) {
                return false;
            }

            return true;
        });

        usort($query, static fn(array $a, array $b): int => strcmp($b['dt_login'] ?? '', $a['dt_login'] ?? ''));

        $total = count($query);
        $totalPages = max(1, (int) ceil($total / $limit));
        $page = min(max(1, $page), $totalPages);

        return [
            'data' => array_slice($query, ($page - 1) * $limit, $limit),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $totalPages,
            ],
        ];
    }

    private static function executarComTentativa(\PDOStatement $stmt, array $params, int $tentativas = 3): bool
    {
        $ultima = null;

        for ($i = 0; $i < $tentativas; $i++) {
            try {
                $stmt->execute($params);
                return true;
            } catch (PDOException $e) {
                $ultima = $e;

                if ($e->getCode() !== '40001') {
                    throw $e;
                }

                usleep(100000 * ($i + 1));
            }
        }

        throw $ultima;
    }
}
