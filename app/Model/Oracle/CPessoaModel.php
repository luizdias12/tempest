<?php

namespace App\Model\Oracle;

use App\Core\QueryBuilder;

class CPessoaModel
{
    public static function pessoaTeste(): array|null
    {
        $p = QueryBuilder::table('VILREDEPERCVERBA', 'consinco')
        ->select('*');
        return $p->get();
    }
}