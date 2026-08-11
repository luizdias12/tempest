<?php

namespace App\Controller;

use App\Core\Alerts\AlertManager;
use Throwable;
use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Service\AuthService;
use App\Service\FinancService;

use DateTime;

class FinancController extends BaseController
{
    public function totaisHolerite(Request $request, string $chapa, int $mescomp, int $anocomp, int $periodo): array
    {
        return $this->handle(
            fn() => $this->success(
                FinancService::totaisHolerite($chapa, $mescomp, $anocomp, $periodo),
                [],
                'Dados encontrados'
            )
        );
    }

    public function holeritePdf(Request $request): void
    {
        try {
            $user = AuthService::getUser();
            $chapa = $user['chapa'] ?? '';

            if (empty($chapa)) {
                ErrorHandler::handle(400, 'Usuário sem chapa vinculada.');
                exit;
            }

            $mescomp = (int) $request->query('mescomp', date('m'));
            $anocomp = (int) $request->query('anocomp', date('Y'));
            $periodo = (int) $request->query('periodo', 3);

            $dataPagamento = DateTime::createFromFormat(
                'Y-n-j',
                "$anocomp-$mescomp-1"
            );

            if (!$dataPagamento) {
                ErrorHandler::handle(400, 'Competência inválida.');
                return;
            }

            $dataPagamento->modify('+1 month');

            $dataReferencia = $dataPagamento->format('Ymd');
            $libera = FinancService::holeriteLiberado($dataReferencia, 31);

            if (!$libera) {
                ErrorHandler::handle(403, 'Competência não liberada.');
                return;
            }

            $totaisHolerite = FinancService::totaisHolerite($chapa, $mescomp, $anocomp, $periodo);

            if (empty($totaisHolerite['proventos'])) {
                ErrorHandler::handle(404, 'Nenhum dado encontrado para este período.');
                return;
            }

            $funcionario = [
                'nome' => $user['name'] ?? '',
                'chapa' => $chapa,
                'codfuncao' => $user['codfuncao'] ?? '',
                'funcao' => $user['funcao'] ?? '',
            ];

            view('financ/holerite_pdf', [
                'totaisHolerite' => $totaisHolerite,
                'chapa'          => $chapa,
                'mescomp'        => $mescomp,
                'anocomp'        => $anocomp,
                'periodo'        => $periodo,
                'funcionario'    => $funcionario,
                'title'          => 'Holerite_' . ($periodo == 2 ? '13' :str_pad($mescomp, 2, '0', STR_PAD_LEFT)) . '_' . $anocomp,
            ], 'layouts/print');
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function holeriteView(Request $request): void
    {
        try {
            $user = AuthService::getUser();
            $chapa = $user['chapa'] ?? '';

            if (empty($chapa)) {
                ErrorHandler::handle(400, 'Usuário sem chapa vinculada.');
                exit;
            }

            $mescomp = (int) $request->query('mescomp', date('m'));
            $anocomp = (int) $request->query('anocomp', date('Y'));
            $periodo = (int) $request->query('periodo', 3);

            $dataPagamento = DateTime::createFromFormat(
                'Y-n-j',
                "$anocomp-$mescomp-1"
            );

            if (!$dataPagamento) {
                ErrorHandler::handle(400, 'Competência inválida.');
                return;
            }

            $dataPagamento->modify('+1 month');

            $dataReferencia = $dataPagamento->format('Ymd');
            $libera = FinancService::holeriteLiberado($dataReferencia, 31);

            $funcionario = [
                'nome' => $user['name'] ?? '',
                'chapa' => $chapa,
                'codfuncao' => $user['codfuncao'] ?? '',
                'funcao' => $user['funcao'] ?? '',
            ];

            if (!$libera) {
                AlertManager::add('warning', 'Competência não liberada.');

                view('financ/holerite', [
                    'totaisHolerite' => [],
                    'chapa'          => $chapa,
                    'mescomp'        => $mescomp,
                    'anocomp'        => $anocomp,
                    'periodo'        => $periodo,
                    'funcionario'    => $funcionario,
                    'title'          => 'Holerite'
                ]);

                return;
            }

            $totaisHolerite = FinancService::totaisHolerite($chapa, $mescomp, $anocomp, $periodo);

            if (empty($totaisHolerite['proventos'])) {
                AlertManager::add('error', 'Nenhum dado encontrado para este período.');
            }

            view('financ/holerite', [
                'totaisHolerite'   => $totaisHolerite,
                'chapa'            => $chapa,
                'mescomp'          => $mescomp,
                'anocomp'          => $anocomp,
                'periodo'          => $periodo,
                'funcionario'      => $funcionario,
                'title'            => 'Holerite',
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }
}
