<?php

namespace App\Model;

use App\Core\DB;
use PDO;
use PDOException;

class Classe extends DB
{

    public static function index(): array
    {
        return self::findWhere('table_name', []);
    }

    public static function show(string $id): ?array
    {
        return self::findWhere('table_name', ['id' => $id]);
    }

    public static function create(array $data): ?int
    {
        return self::insert('table_name', $data);
    }

    public static function delete($id)
    {
        return self::deleteWhere('table_name', ['id' => $id]);
    }

    public static function getRandomId(): ?int
    {
        $sql = "
            SELECT id FROM table_name
            WHERE id >= (
                SELECT FLOOR(RAND() * (SELECT MAX(id) FROM table_name))
            )
            ORDER BY id
            LIMIT 1
        ";

        $row = self::first($sql);

        return $row ? (int) $row['id'] : null;
    }

    public static function paginate(int $page = 1, int $limit = 10): array
    {
        return parent::paginateTable(
            'table_name',
            $page,
            $limit,
            [],
            [
                'orderBy' => 'orderField',
                'direction' => 'ASC'
            ]
        );
    }
}