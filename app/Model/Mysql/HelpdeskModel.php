<?php

namespace App\Model\Mysql;

use App\Core\DB;
use App\Core\QueryBuilder;

class HelpdeskModel
{
    public static function chamadosAbertos(
        int $page,
        int $limit,
        ?int $id = null,
        ?string $emitente = null,
        ?string $status = null,
        ?string $local = null
        ): array
    {
        $chamadosAbertos = QueryBuilder::table('helpdesk h', 'mysql')
        ->select('h.id', 'h.chapa', 'f.nome', 'h.dt_abertura',
        'h.dt_solucao', 'h.status', 'st.descricao as status_desc',
        'h.cab_problema', 'h.desc_problema', 'g.descricao as grupo',
        'sg.descricao as subgrupo', 'o.local', 'f2.nome as responsavel',
        'fu.nome as funcao', 'u.ramal as ramal', 'u.email as email',
        's.desc_sla as sla', 'h.cpf_ab'
        )
        ->join('func f', 'f.cpf', '=', 'h.cpf_ab')
        ->leftJoin('func f2', 'f2.cpf', '=', 'h.id_resp')
        ->join('funcao fu', 'fu.codigo', '=', 'f.codfuncao')
        ->join('grupo g', 'g.id_grupo', '=', 'h.idgrupo')
        ->join('sla s', 's.id_sla', '=', 'h.sla')
        ->leftJoin('usuarios u', 'u.cpf', '=', 'h.cpf_ab')
        ->joinOn('subgrupo sg', function($join) {
            $join->on('sg.idgrupo', '=', 'g.id_grupo')
            ->on('sg.id', '=', 'h.idsubgrupo');
        })
        ->join('status st', 'st.status', '=', 'h.status')
        ->leftJoinOn('online o', function($join) use ($local) {
            $join->on('o.cpf', '=', 'f.cpf');
            if (!empty($local)) {
                $join->onValue('o.local', '=', $local);
            }
        })
        ->orderBy('h.id', 'DESC')
        ->when(empty($status), fn($q) => $q->whereNotIn('h.status', ['R','C']))
        ->when(!empty($id), fn($q) => $q->where('h.id', $id))
        ->when(!empty($emitente), fn($q) => $q->where('f.nome', 'LIKE', "%$emitente%"))
        ->when(!empty($status), fn($q) => $q->where('h.status', $status));

        return $chamadosAbertos->paginate($page, $limit);
    }
}