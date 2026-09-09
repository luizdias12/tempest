<?php

namespace App\Model\Mysql;

use App\Core\DB;

class CarouselModel
{
    public static function listarAtivos(): array
    {
        return DB::select("
            SELECT id, filename, ord, dtinicio, dtfim, ativo, link
            FROM carousel
            WHERE ativo = 'S'
              AND (dtinicio IS NULL OR dtinicio <= CURDATE())
              AND (dtfim IS NULL OR dtfim >= CURDATE())
            ORDER BY ord, id
        ", [], 'mysql');
    }

    public static function listarTodos(): array
    {
        return DB::select("
            SELECT id, filename, ord, dtinicio, dtfim, ativo, link
            FROM carousel
            ORDER BY ord, id
        ", [], 'mysql');
    }

    public static function proximaOrdem(): int
    {
        $row = DB::first("
            SELECT MAX(ord) AS maior
            FROM carousel
        ", [], 'mysql');

        return (int) ($row['maior'] ?? 0) + 1;
    }

    public static function inserir(array $dados): ?int
    {
        return DB::insert('carousel', $dados, 'mysql');
    }

    public static function buscar(int $id): ?array
    {
        return DB::first("
            SELECT id, filename, ord, dtinicio, dtfim, ativo, link
            FROM carousel
            WHERE id = :id
        ", ['id' => $id], 'mysql');
    }

    public static function alternarAtivo(int $id, string $ativo): bool
    {
        return DB::update('carousel', 'id', $id, ['ativo' => $ativo], 'mysql');
    }

    public static function trocarOrdem(int $idA, int $idB): bool
    {
        $a = self::buscar($idA);
        $b = self::buscar($idB);

        if ($a === null || $b === null) {
            return false;
        }

        $conn = DB::connect('mysql');
        $stmt = $conn->prepare("UPDATE carousel SET ord = :ord WHERE id = :id");

        $stmt->execute(['ord' => $b['ord'], 'id' => $a['id']]);
        $stmt->execute(['ord' => $a['ord'], 'id' => $b['id']]);

        return true;
    }

    public static function vizinhoPorOrdem(int $ord, string $operador, string $direcao): ?array
    {
        return DB::first("
            SELECT id, filename, ord, dtinicio, dtfim, ativo, link
            FROM carousel
            WHERE ord {$operador} :ord
            ORDER BY ord {$direcao}, id {$direcao}
            LIMIT 1
        ", ['ord' => $ord], 'mysql');
    }

    public static function excluir(int $id): ?array
    {
        $row = self::buscar($id);

        if ($row === null) {
            return null;
        }

        $stmt = DB::connect('mysql')->prepare("DELETE FROM carousel WHERE id = :id");
        $stmt->execute(['id' => $id]);

        return $row;
    }
}