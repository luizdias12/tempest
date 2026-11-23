<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Service\RelatorioService;
use DateTimeImmutable;
use Throwable;

class RelatorioController extends BaseController
{
    public function indexView(Request $request): void
    {
        view('relatorios/index', [
            'title' => 'Relatorios',
        ]);
    }

    public function helpdeskView(Request $request): void
    {
        try {
            $data = trim((string) $request->query('data', ''));

            if ($data !== '') {
                $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
                if ($dt === false || $dt->format('Y-m-d') !== $data) {
                    $data = '';
                }
            }

            $registros = RelatorioService::resolvidosPorTecnico($data !== '' ? $data : null);

            view('relatorios/helpdesk', [
                'registros' => $registros,
                'data' => $data,
                'totalResolvidos' => array_sum(array_column($registros, 'resolvidos')),
                'totalAbertos' => array_sum(array_column($registros, 'abertos')),
                'title' => 'Relatórios do Helpdesk',
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage(), false, $e);
        }
    }
}
