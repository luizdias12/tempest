<?php

namespace App\Model\Mysql;

use App\Core\DB;

class ComercialModel
{
    public static function listar(?string $mesRef = null): array
    {
        return DB::select("
            SELECT e.id, e.mesref, e.data, e.comprador, e.feriado, f.nome
            FROM escalacom e
            LEFT JOIN func f ON f.cpf = e.comprador
            WHERE (:mesref IS NULL OR e.mesref = :mesref)
            ORDER BY e.data, e.id
        ", ['mesref' => $mesRef], 'mysql');
    }

    public static function buscar(int $id): ?array
    {
        return DB::first("
            SELECT e.id, e.mesref, e.data, e.comprador, e.feriado, f.nome
            FROM escalacom e
            LEFT JOIN func f ON f.cpf = e.comprador
            WHERE e.id = :id
        ", ['id' => $id], 'mysql');
    }

    public static function plantoesPorData(string $data): array
    {
        return DB::select("
            SELECT e.id, e.data, e.feriado, f.nome
            FROM escalacom e
            LEFT JOIN func f ON f.cpf = e.comprador
            WHERE e.data = :data
            ORDER BY e.id
        ", ['data' => $data], 'mysql');
    }

    public static function plantoesPorPeriodo(string $inicio, string $fim): array
    {
        return DB::select("
            SELECT e.id, e.data, e.feriado, f.nome
            FROM escalacom e
            LEFT JOIN func f ON f.cpf = e.comprador
            WHERE e.data BETWEEN :inicio AND :fim
            ORDER BY e.data, e.id
            LIMIT 40
        ", ['inicio' => $inicio, 'fim' => $fim], 'mysql');
    }

    public static function proximosPlantoes(string $data, int $limite = 2): array
    {
        $limite = max(1, min(10, $limite));

        return DB::select("
            SELECT e.id, e.data, e.feriado, f.nome
            FROM escalacom e
            LEFT JOIN func f ON f.cpf = e.comprador
            WHERE e.data >= :data
            ORDER BY e.data, e.id
            LIMIT {$limite}
        ", ['data' => $data], 'mysql');
    }

    public static function listarCompradores(): array
    {
        return DB::select("
            SELECT f.cpf, f.nome, f.chapa, COALESCE(fi.nome, '') AS filial
            FROM func f
            LEFT JOIN filial fi ON fi.codgfilial = f.codfilial
            WHERE f.cpf IS NOT NULL AND f.cpf <> ''
            ORDER BY f.nome ASC
        ", [], 'mysql');
    }

    public static function inserir(array $dados): ?int
    {
        return DB::insert('escalacom', $dados, 'mysql');
    }

    public static function atualizar(int $id, array $dados): bool
    {
        return DB::update('escalacom', 'id', $id, $dados, 'mysql');
    }

    public static function excluir(int $id): bool
    {
        $stmt = DB::connect('mysql')->prepare("DELETE FROM escalacom WHERE id = :id");

        return $stmt->execute(['id' => $id]);
    }
}