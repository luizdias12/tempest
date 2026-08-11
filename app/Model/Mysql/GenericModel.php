<?php

namespace App\Model\Mysql;

use App\Core\DB;
use App\Core\QueryBuilder;

class GenericModel
{
    public static function listarLocais(): array
    {
        $locais = DB::select("SELECT DISTINCT IFNULL(`local`, 'Nao Identificado') AS `local` FROM `online`
                                ORDER BY `local`", [], 'mysql');
        return $locais;
    }

    public static function obterTemaUsuario(string $cpf): array|null
    {
        $tema = QueryBuilder::table('themes', 'mysql')
            ->select('bg_color', 'text_color')
            ->where('cpf', $cpf)
            ->first();
        return $tema;
    }

    public static function listaMotivosCancelamento(): array
    {
        $motivos = QueryBuilder::table('motivocanc', 'mysql')
            ->select('id', 'motivo', 'texto')
            ->get();
        return $motivos;
    }

    public static function obterTextoCancelamento(int $idMotivo): string
    {
        $texto = QueryBuilder::table('motivocanc', 'mysql')
        ->select('texto')
        ->where('id', $idMotivo)
        ->first();
        return $texto['texto'] ?? 'Chamado Cancelado';
    }

    public static function buscaFuncExterno(string $nome): array|null
    {
        $externo = QueryBuilder::table('func_externo', 'mysql')
        ->select('chapa', 'nome', 'cpf')
        ->whereRaw('UPPER(nome) = :nome', [
                'nome' => mb_strtoupper($nome, 'UTF-8')
            ])
        ->first();
        return $externo ?? [];
    }

    public static function buscaFuncExternoPorCpf(string $cpf): array|null
    {
        $externo = QueryBuilder::table('func_externo', 'mysql')
        ->select('chapa', 'nome', 'cpf')
        ->where('cpf', $cpf)
        ->first();
        return $externo ?? [];
    }
}