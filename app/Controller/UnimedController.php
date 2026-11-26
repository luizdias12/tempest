<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\Logger;
use App\Core\Request;
use App\Service\AuthService;
use App\Service\ExtratoUnimedService;
use RuntimeException;
use Throwable;

class UnimedController extends BaseController
{
    public function indexView(Request $request): void
    {
        view('unimed/index', [
            'competencias' => ExtratoUnimedService::competenciasDisponiveis(),
            'titular' => (string) (AuthService::getUserName() ?? ''),
            'title' => 'Extrato Unimed',
        ]);
    }

    public function consulta(Request $request): array
    {
        $mes = (string) $request->query('mes', '');
        $ano = (string) $request->query('ano', '');

        try {
            return $this->success(ExtratoUnimedService::consultar($mes, $ano));
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (Throwable $e) {
            Logger::exception($e);

            return $this->error('Erro ao consultar o extrato.', 500);
        }
    }
}
