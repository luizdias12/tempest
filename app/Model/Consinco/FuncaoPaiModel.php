<?php

namespace App\Model\Consinco;

use App\Core\QueryBuilder;

class FuncaoPaiModel
{
    public static function codfuncaoPai(string $codfuncao): ?int
    {
        $row = QueryBuilder::table('vilrhfuncaofilho f', 'consinco')
            ->select('p.codfuncaopai')
            ->join('vilrhfuncaopai p', 'p.codfuncaopai', '=', 'f.codfuncaopai')
            ->where('f.codfuncao', $codfuncao)
            ->first();

        if (empty($row['codfuncaopai'])) {
            return null;
        }

        return (int) $row['codfuncaopai'];
    }

    public static function listarFuncoes(): array
    {
        return QueryBuilder::table('vilrhfuncaopai', 'consinco')
            ->select('codfuncaopai AS codigo', 'funcaopai AS nome')
            ->orderBy('funcaopai')
            ->get();
    }

    public static function listarFuncoesFilhas(): array
    {
        return QueryBuilder::table('vilrhfuncaofilho f', 'consinco')
            ->select('f.codfuncaopai AS codfuncaopai', 'f.codfuncao AS codfuncao')
            ->orderBy('f.codfuncaopai')
            ->get();
    }
}
