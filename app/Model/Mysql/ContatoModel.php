<?php

namespace App\Model\Mysql;

use App\Core\QueryBuilder;

class ContatoModel
{
    public static function listar(
        int $page,
        int $limit,
        ?string $nome,
        ?string $email,
        ?string $ramal,
        ?string $setor,
        ?string $filial
    ): array
    {
        $query = QueryBuilder::table('usuarios u', 'mysql')
            ->select(
                'f.nome',
                'u.email',
                'u.ramal',
                'u.corporativo',
                's.setor AS setor',
                'fi.nome AS filial',
                'fp.funcaopai AS funcao',
                'f.codsecao',
                'u.usuario',
                'u.cpf'
            )
            ->join('func f', 'f.cpf', '=', 'u.cpf')
            ->leftJoin('setor s', 's.id', '=', 'u.id_setor')
            ->leftJoin('filial fi', 'fi.codgfilial', '=', 'u.filial_cad')
            ->leftJoin('funcao fn', 'fn.codigo', '=', 'f.codfuncao')
            ->leftJoin('funcaofilho ff', 'ff.codfuncao', '=', 'fn.codigo')
            ->leftJoin('funcaopai fp', 'fp.codpai', '=', 'ff.codpai')
            ->where('u.ativo', 'S')
            ->whereGroup(function ($query) {
                $query->where('u.ramal', '!=', '')
                    ->orWhere('u.email', '!=', '')
                    ->orWhere('u.corporativo', '!=', '');
            });

        if ($nome !== null) {
            $query->whereLike('f.nome', $nome);
        }

        if ($email !== null) {
            $query->whereLike('u.email', $email);
        }

        if ($ramal !== null) {
            $query->whereLike('u.ramal', $ramal);
        }

        if ($setor !== null) {
            $query->where('s.id', $setor);
        }

        if ($filial !== null) {
            $query->where('u.filial_cad', $filial);
        }

        $query->orderBy('f.nome', 'ASC');

        return $query->paginate($page, $limit);
    }

    public static function listarSetores(): array
    {
        return QueryBuilder::table('setor', 'mysql')
            ->select('id', 'setor')
            ->where('exibe', 'S')
            ->orderBy('setor', 'ASC')
            ->get();
    }
}
