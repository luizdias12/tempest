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
    ): array {
        $query = QueryBuilder::table('usuarios u', 'mysql')
            ->select(
                'f.nome',
                'f.nomesocial',
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
            $query->whereGroup(function ($q) use ($nome) {
                $q->whereLike('f.nome', $nome)
                    ->whereLike('f.nomesocial', $nome, 'OR');
            });
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

    /**
     * Busca contatos ativos por nome e, opcionalmente, setor, para consultas da Vivi.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function buscarPorNomeSetor(string $nome, ?string $setor = null, int $limite = 200): array
    {
        $limite = max(1, min(200, $limite));
        $partes = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $nome) ?: [],
            static fn(string $parte): bool => $parte !== ''
        ));

        if (empty($partes)) {
            return [];
        }

        $query = QueryBuilder::table('usuarios u', 'mysql')
            ->select(
                'f.nome',
                'f.nomesocial',
                'u.ramal',
                'u.email',
                'u.corporativo',
                's.setor AS setor'
            )
            ->join('func f', 'f.cpf', '=', 'u.cpf')
            ->leftJoin('setor s', 's.id', '=', 'u.id_setor')
            ->where('u.ativo', 'S')
            ->whereGroup(function ($q) {
                $q->where('u.ramal', '!=', '')
                    ->orWhere('u.email', '!=', '')
                    ->orWhere('u.corporativo', '!=', '');
            })
            ->orderBy('f.nome', 'ASC')
            ->limit($limite);

        foreach ($partes as $parte) {
            $query->whereGroup(function ($q) use ($parte) {
                $q->whereLike('f.nome', $parte)
                    ->whereLike('f.nomesocial', $parte, 'OR');
            });
        }

        if ($setor !== null && $setor !== '') {
            $query->where('s.setor', $setor);
        }

        return $query->get();
    }
}
