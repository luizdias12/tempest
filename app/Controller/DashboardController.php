<?php

namespace App\Controller;

use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Service\DashboardService;
use Throwable;

class DashboardController
{
    public function indexView(Request $request): void
    {
        $this->render('dashboards/index', [
            'title' => 'Dashboards',
        ]);
    }

    public function helpdeskView(Request $request): void
    {
        $dias = DashboardService::normalizarPeriodo((int) $request->query('dias', DashboardService::PERIODO_PADRAO));

        $dados = $this->coletar(fn() => DashboardService::helpdesk($dias));

        if ($dados === null) {
            return;
        }

        $this->render('dashboards/helpdesk', [
            'title' => 'Dashboard de Helpdesk',
            'dados' => $dados,
            'periodos' => DashboardService::PERIODOS,
            'dias' => $dias,
        ]);
    }

    private function coletar(callable $fn): ?array
    {
        try {
            return $fn();
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, 'Não foi possível carregar o dashboard.', false);
            return null;
        }
    }

    private function render(string $view, array $params): void
    {
        view($view, $params);
    }
}
