<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Service\EsocialService;
use Throwable;

class EsocialController extends BaseController
{
    public function indexView(Request $request): void
    {
        view('esocial/index', [
            'title' => 'Eventos Esocial',
        ]);
    }

    public function tabelasView(Request $request): void
    {
        
        $dados = EsocialService::getEventosTabela();

        if ($dados === null) {
            return;
        }

        view('esocial/geral', [
            'title' => 'Eventos de Tabelas',
            'dados' => $dados,
            'tipoevento' => 1
        ]);
    }
    
    public function periodicosView(Request $request): void
    {
        $mescomp = (int) $request->query('mescomp', date('m'));
        $anocomp = (int) $request->query('anocomp', date('Y'));

        $dados = EsocialService::getEventosPeriodicos($mescomp, $anocomp);

        if ($dados === null) {
            return;
        }

        view('esocial/geral', [
            'title'     => 'Eventos Periódicos',
            'dados'     => $dados,
            'mescomp'   => $mescomp,
            'anocomp'   => $anocomp,
            'tipoevento' => 2
        ]);
    }

    public function naoPeriodicosView(Request $request): void
    {

        $dados = EsocialService::getEventosNaoPeriodicos();

        if ($dados === null) {
            return;
        }

        view('esocial/geral', [
            'title'     => 'Eventos Não Periódicos',
            'dados'     => $dados,
            'tipoevento' => 3
        ]);
    }

    public function trabalhistasView(Request $request): void
    {

        $dados = EsocialService::getEventosProcTrabalhista();

        if ($dados === null) {
            return;
        }

        view('esocial/geral', [
            'title'     => 'Eventos Processo Trabalhista',
            'dados'     => $dados,
            'tipoevento' => 4
        ]);
    }
}
