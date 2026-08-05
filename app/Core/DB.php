<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class DB
{
    private static ?PDO $conn = null;
    private static array $connections = [];

    // ------- Connection -------

    private static function env(string $key, string $default = ''): string
    {
        return getenv($key) ?: ($_ENV[$key] ?? $default);
    }

    public static function connect(string $name = 'oracle'): PDO
    {
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        if ($name === 'mysql') {
            $host   = self::env('MYSQL_HOST');
            $port   = self::env('MYSQL_PORT', '3306');
            $db     = self::env('MYSQL_DBNAME');
            $user   = self::env('MYSQL_USER');
            $pass   = self::env('MYSQL_PASSWORD');

            try {
                self::$connections[$name] = new PDO(
                    "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                throw new Exception('Erro ao conectar ao MySQL: ' . $e->getMessage());
            }
        } else {
            // conexão Oracle (default)
            $host      = self::env('DB_HOST');
            $port      = self::env('DB_PORT', '1521');
            $service   = self::env('DB_SERVICENAME');
            $user      = self::env('DB_USER');
            $pass      = self::env('DB_PASSWORD');
            $charset   = self::env('DB_CHARSET', 'AL32UTF8');

            try {
                self::$connections[$name] = new PDO(
                    "oci:dbname=//{$host}:{$port}/{$service};charset={$charset}",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_CASE => PDO::CASE_LOWER,
                    ]
                );
            } catch (PDOException $e) {
                throw new Exception('Erro ao conectar ao Oracle: ' . $e->getMessage());
            }
        }

        return self::$connections[$name];
    }

    // ------- Validation -------

    private static function validateIdentifier(string $name, string $label = 'Identificador'): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new Exception("{$label} inválido: {$name}");
        }
    }

    private static function validateTable(string $table): void
    {
        self::validateIdentifier($table, 'Tabela');
    }

    // ------- Query builders -------

    private static function buildWhereClause(array $conditions, string $prefix = 'where'): array
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $fields = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            self::validateIdentifier($column, 'Coluna');
            $param = ":{$prefix}_{$column}";
            $fields[] = "{$column} = {$param}";
            $params["{$prefix}_{$column}"] = $value;
        }

        $clause = ' WHERE ' . implode(' AND ', $fields);
        return [$clause, $params];
    }

    private static function buildSetClause(array $data, string $prefix = 'set'): array
    {
        $fields = [];
        $params = [];

        foreach ($data as $column => $value) {
            self::validateIdentifier($column, 'Coluna');
            $param = ":{$prefix}_{$column}";
            $fields[] = "{$column} = {$param}";
            $params["{$prefix}_{$column}"] = $value;
        }

        return [implode(', ', $fields), $params];
    }

    // ------- CRUD -------

    public static function select(string $sql, array $params = [], string $connection = 'oracle'): array
    {
        $stmt = self::connect($connection)->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function first(string $sql, array $params = [], string $connection = 'oracle'): ?array
    {
        $stmt = self::connect($connection)->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public static function insert(string $table, array $data, string $connection = 'oracle'): ?int
    {
        if (empty($data)) {
            return null;
        }

        self::validateTable($table);

        foreach (array_keys($data) as $column) {
            self::validateIdentifier($column, 'Coluna');
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $conn = self::connect($connection);
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);

        return (int) $conn->lastInsertId();
    }

    public static function update(string $table, string $keyColumn, $keyValue, array $data, string $connection = 'oracle'): bool
    {
        if (empty($data)) {
            return false;
        }

        self::validateTable($table);
        self::validateIdentifier($keyColumn, 'Coluna chave');

        [$setClause, $params] = self::buildSetClause($data);
        $params['keyfield'] = $keyValue;

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$keyColumn} = :keyfield";
        $stmt = self::connect($connection)->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public static function updateWhere(string $table, array $data, array $conditions): bool
    {
        if (empty($data) || empty($conditions)) {
            return false;
        }

        self::validateTable($table);

        [$setClause, $setParams] = self::buildSetClause($data, 'set');
        [$whereClause, $whereParams] = self::buildWhereClause($conditions, 'where');

        $sql = "UPDATE {$table} SET {$setClause}{$whereClause}";
        $params = array_merge($setParams, $whereParams);

        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public static function deleteWhere(string $table, array $conditions): bool
    {
        if (empty($conditions)) {
            return false;
        }

        self::validateTable($table);
        [$whereClause, $params] = self::buildWhereClause($conditions, 'where');

        $sql = "DELETE FROM {$table}{$whereClause}";
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public static function findWhere(string $table, array $conditions = []): ?array
    {
        self::validateTable($table);
        [$whereClause, $params] = self::buildWhereClause($conditions, 'where');

        $sql = "SELECT * FROM {$table}{$whereClause}";
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: null;
    }

    public static function findOneWhere(string $table, array $conditions = []): ?array
    {
        self::validateTable($table);
        [$whereClause, $params] = self::buildWhereClause($conditions, 'where');

        $sql = "SELECT * FROM {$table}{$whereClause} LIMIT 1";
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    public static function countWhere(string $table, array $conditions = []): int
    {
        self::validateTable($table);
        [$whereClause, $params] = self::buildWhereClause($conditions, 'where');

        $sql = "SELECT COUNT(*) as total FROM {$table}{$whereClause}";
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(): string
    {
        return self::connect()->lastInsertId();
    }

    // ------- Transactions -------

    public static function beginTransaction(): bool
    {
        return self::connect()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::connect()->commit();
    }

    public static function rollBack(): bool
    {
        return self::connect()->rollBack();
    }

    // ------- Pagination -------

    public static function paginateTable(
        string $table,
        int $page = 1,
        int $limit = 10,
        array $conditions = [],
        array $options = [],
        string $connection = 'oracle'
    ): array {
        self::validateTable($table);

        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        [$whereClause, $params] = self::buildWhereClause($conditions, 'where');

        $orderClause = self::buildOrderClause($options);

        $countSql = "SELECT COUNT(*) as total FROM {$table}{$whereClause}";
        $countConn = self::connect($connection);
        $countStmt = $countConn->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        if ($connection === 'mysql') {
            $sql = "SELECT * FROM {$table}{$whereClause}{$orderClause} LIMIT :limit OFFSET :offset";
        } else {
            $sql = "SELECT * FROM {$table}{$whereClause}{$orderClause} OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
        }
        $stmt = self::connect($connection)->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll();

        return [
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => (int) ceil($total / $limit)
            ]
        ];
    }

    private static function buildOrderClause(array $options): string
    {
        if (empty($options['orderBy'])) {
            return '';
        }

        $orderBy = $options['orderBy'];

        if (is_array($orderBy)) {
            $orders = [];
            foreach ($orderBy as $order) {
                $column = $order['column'] ?? null;
                $direction = strtoupper($order['direction'] ?? 'ASC');

                if (!$column) {
                    throw new Exception('OrderBy inválido: coluna não definida');
                }

                self::validateIdentifier($column, 'OrderBy');

                if (!in_array($direction, ['ASC', 'DESC'])) {
                    $direction = 'ASC';
                }

                $orders[] = "{$column} {$direction}";
            }

            return !empty($orders) ? ' ORDER BY ' . implode(', ', $orders) : '';
        }

        self::validateIdentifier($orderBy, 'OrderBy');
        $direction = strtoupper($options['direction'] ?? 'ASC');
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        return " ORDER BY {$orderBy} {$direction}";
    }
}
