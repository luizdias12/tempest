<?php

namespace App\Service;

use App\Model\Mysql\RegionalModel;

class RegionalService
{
    public static function listaRegional(): array
    {
        return RegionalModel::listaRegional();
    }

    public static function consulta(): array
    {
        return RegionalModel::consulta();
    }

    public static function byRegional(int $regiao): array
    {
        return RegionalModel::byRegional($regiao);
    }

    public static function filialByRegional(): array
    {
        return RegionalModel::filialByRegional();
    }

    public static function byFilial(int $filial): array
    {
        return RegionalModel::byFilial($filial);
    }

    public static function onlyGerente(): array
    {
        return RegionalModel::onlyGerente();
    }

    public static function onlysubGerente(): array
    {
        return RegionalModel::onlysubGerente();
    }

    public static function listaGerencia(): array
    {
        return RegionalModel::listaGerencia();
    }

    public static function regionalFilial(): array
    {
        return RegionalModel::regionalFilial();
    }

    public static function usuario(string $cpf): ?array
    {
        return RegionalModel::usuario($cpf);
    }

    public static function gravaGerente(int $codregional, int $codfilial, ?string $g1, ?string $g2): ?int
    {
        return RegionalModel::gravaGerente($codregional, $codfilial, $g1, $g2);
    }

    public static function updateRegional(int $id, int $codregional, ?string $g1, ?string $g2): bool
    {
        return RegionalModel::updateRegional($id, $codregional, $g1, $g2);
    }

    public static function updatefilialReg(int $filial, int $codRegional): bool
    {
        return RegionalModel::updatefilialReg($filial, $codRegional);
    }

    public static function alteraRegional(int $regiao, string $nome): bool
    {
        return RegionalModel::alteraRegional($regiao, $nome);
    }

    public static function deletaGerente(int $id): bool
    {
        return RegionalModel::deletaGerente($id);
    }

    public static function regionalExiste(int $regiao): bool
    {
        return RegionalModel::regionalExiste($regiao);
    }

    public static function gravaRegional(int $regiao, string $nome, ?string $cpf): ?int
    {
        return RegionalModel::gravaRegional($regiao, $nome, $cpf);
    }

    public static function deletaRegional(int $regiao): bool
    {
        return RegionalModel::deletaRegional($regiao);
    }

    public static function filialVinculada(int $codRegional, string $filial): bool
    {
        return RegionalModel::filialVinculada($codRegional, $filial);
    }

    public static function gravaFilial(int $codRegional, string $filial): ?int
    {
        return RegionalModel::gravaFilial($codRegional, $filial);
    }

    public static function deletaFilial(int $id): bool
    {
        return RegionalModel::deletaFilial($id);
    }

    public static function deletaGerentesFilial(int $codRegional, string $codfilial): bool
    {
        return RegionalModel::deletaGerentesFilial($codRegional, $codfilial);
    }
}