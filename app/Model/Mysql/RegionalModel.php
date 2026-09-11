<?php

namespace App\Model\Mysql;

use App\Core\DB;

class RegionalModel
{
    public static function listaRegional(): array
    {
        return DB::select("SELECT gr.regiao, gr.nome, u.email, u.corporativo
            FROM ger_regional gr
            LEFT JOIN usuarios u ON u.cpf = gr.cpf", [], 'mysql');
    }

    public static function consulta(): array
    {
        return DB::select("SELECT gr.regiao, gr.nome AS regional, u.email, u.corporativo,
                fi.codfilial, COALESCE(fi.abreviado, fi.nome) AS filial,
                f1.nome AS gerente, f2.nome AS subgerente, f1.cpf AS cpf_g1, f2.cpf AS cpf_g2
            FROM ger_regional gr
            LEFT JOIN usuarios u ON u.cpf = gr.cpf
            LEFT JOIN regional_filial rf ON rf.cod_regional = gr.regiao
            LEFT JOIN filial fi ON fi.codgfilial = rf.filial
            LEFT JOIN gerentes ge ON ge.codregional = gr.regiao AND ge.codfilial = rf.filial
            LEFT JOIN func f1 ON f1.cpf = ge.g1
            LEFT JOIN func f2 ON f2.cpf = ge.g2
            ORDER BY gr.regiao, fi.codfilial * 1", [], 'mysql');
    }

    public static function byRegional(int $regiao): array
    {
        return DB::select("SELECT fi.codfilial, COALESCE(fi.abreviado, fi.nome) AS filial, gr.regiao, gr.nome AS regional,
                f1.nome AS gerente, f2.nome AS subgerente, f1.cpf AS cpf_g1, f2.cpf AS cpf_g2, gr.cpf AS cpf_gr
            FROM gerentes ge
            INNER JOIN ger_regional gr ON gr.regiao = ge.codregional
            INNER JOIN filial fi ON fi.codgfilial = ge.codfilial
            INNER JOIN regional_filial rf ON rf.cod_regional = gr.regiao AND rf.filial = ge.codfilial
            LEFT JOIN func f1 ON f1.cpf = ge.g1
            LEFT JOIN func f2 ON f2.cpf = ge.g2
            WHERE ge.codregional = :regiao
            ORDER BY fi.codfilial * 1, f2.nome", ['regiao' => $regiao], 'mysql');
    }

    public static function filialByRegional(): array
    {
        return DB::select("SELECT rf.id, rf.cod_regional, fi.codfilial, fi.codgfilial, COALESCE(fi.abreviado, fi.nome) AS filial, gr.nome AS regional
            FROM regional_filial rf
            INNER JOIN filial fi ON fi.codgfilial = rf.filial
            INNER JOIN ger_regional gr ON gr.regiao = rf.cod_regional
            ORDER BY rf.cod_regional, fi.codfilial * 1", [], 'mysql');
    }

    public static function byFilial(int $filial): array
    {
        return DB::select("SELECT ge.id, ge.codfilial, COALESCE(fi.abreviado, fi.nome) AS filial, gr.regiao, gr.nome AS regional,
                ge.g1, f1.nome AS gerente, ge.g2, f2.nome AS subgerente
            FROM gerentes ge
            INNER JOIN ger_regional gr ON gr.regiao = ge.codregional
            INNER JOIN filial fi ON fi.codgfilial = ge.codfilial
            INNER JOIN regional_filial rf ON rf.cod_regional = gr.regiao AND rf.filial = ge.codfilial
            LEFT JOIN func f1 ON f1.cpf = ge.g1
            LEFT JOIN func f2 ON f2.cpf = ge.g2
            WHERE ge.codfilial = :filial", ['filial' => $filial], 'mysql');
    }

    public static function onlyGerente(): array
    {
        return DB::select("SELECT * FROM func WHERE codfuncao IN (SELECT codfuncao FROM funcaofilho WHERE codpai IN (254)) ORDER BY nome", [], 'mysql');
    }

    public static function onlysubGerente(): array
    {
        return DB::select("SELECT * FROM func WHERE codfuncao IN (SELECT codfuncao FROM funcaofilho WHERE codpai IN (307, 308)) ORDER BY nome", [], 'mysql');
    }

    public static function listaGerencia(): array
    {
        return DB::select("SELECT * FROM func WHERE codfuncao IN (SELECT codfuncao FROM funcaofilho WHERE codpai IN (254, 307, 308)) ORDER BY nome", [], 'mysql');
    }

    public static function regionalFilial(): array
    {
        return DB::select("SELECT codfilial, codgfilial, CONCAT(codfilial, ' - ', nome) AS filialS
            FROM filial WHERE exibir = 'S'
            ORDER BY filialS * 1", [], 'mysql');
    }

    public static function usuario(string $cpf): ?array
    {
        return DB::first("SELECT cpf, email, corporativo, ramal FROM usuarios WHERE cpf = :cpf", ['cpf' => $cpf], 'mysql');
    }

    public static function gravaGerente(int $codregional, int $codfilial, ?string $g1, ?string $g2): ?int
    {
        return DB::insert('gerentes', [
            'codregional' => $codregional,
            'codfilial' => $codfilial,
            'g1' => $g1,
            'g2' => $g2,
        ], 'mysql');
    }

    public static function updateRegional(int $id, int $codregional, ?string $g1, ?string $g2): bool
    {
        return DB::update('gerentes', 'id', $id, [
            'codregional' => $codregional,
            'g1' => $g1,
            'g2' => $g2,
        ], 'mysql');
    }

    public static function updatefilialReg(int $filial, int $codRegional): bool
    {
        return DB::update('regional_filial', 'filial', $filial, [
            'cod_regional' => $codRegional,
        ], 'mysql');
    }

    public static function alteraRegional(int $regiao, string $nome): bool
    {
        return DB::update('ger_regional', 'regiao', $regiao, [
            'nome' => $nome,
        ], 'mysql');
    }

    public static function deletaGerente(int $id): bool
    {
        $stmt = DB::connect('mysql')->prepare("DELETE FROM gerentes WHERE id = :id");
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}