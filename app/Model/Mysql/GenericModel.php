<?php

namespace App\Model\Mysql;

use App\Core\DB;
use App\Core\QueryBuilder;

class GenericModel
{
    public static function listarLocais()
    {
        $locais = DB::select("SELECT DISTINCT IFNULL(`local`, 'Nao Identificado') AS `local` FROM `online`
                                ORDER BY `local`", [], 'mysql');
        return $locais;
    }

    public static function obterTemaUsuario(string $cpf)
    {
        $tema = QueryBuilder::table('themes', 'mysql')
            ->select('bg_color', 'text_color')
            ->where('cpf', $cpf)
            ->first();
        return $tema;
    }
}