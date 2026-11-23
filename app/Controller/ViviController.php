<?php

namespace App\Controller;

use App\Core\ApiException;
use App\Core\BaseController;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;
use App\Service\ViviService;
use Throwable;

class ViviController extends BaseController
{
    private const CABECALHOS = [
        'Cache-Control' => 'no-store',
        'X-Content-Type-Options' => 'nosniff',
    ];

    public function chat(Request $request): void
    {
        try {
            $tipo = (string) $request->header('Content-Type', '');

            if (stripos($tipo, 'application/json') === false) {
                Response::json($this->error('Envie a mensagem em JSON.', 415), 415, self::CABECALHOS);
                return;
            }

            $mensagem = (string) $request->input('message', '');
            $historico = $request->input('history', []);
            $usuario = AuthService::isAuthenticated() ? AuthService::getUser() : null;

            $resposta = ViviService::responder(
                $mensagem,
                is_array($historico) ? $historico : [],
                $usuario
            );

            Response::json($this->success(['reply' => $resposta]), 200, self::CABECALHOS);
        } catch (ApiException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;

            if ($status === 429 || $status >= 500) {
                Logger::warning('Vivi: ' . $e->getMessage(), ['status' => $status]);
            }

            Response::json(
                $this->error($e->getMessage(), $status, $e->getDetails()),
                $status,
                self::CABECALHOS
            );
        } catch (Throwable $e) {
            Logger::exception($e, ['servico' => 'ViviController']);

            Response::json(
                $this->error('Erro interno ao falar com a Vivi.', 500),
                500,
                self::CABECALHOS
            );
        }
    }

    public function feedback(Request $request): void
    {
        try {
            $tipo = (string) $request->header('Content-Type', '');

            if (stripos($tipo, 'application/json') === false) {
                Response::json($this->error('Envie o feedback em JSON.', 415), 415, self::CABECALHOS);
                return;
            }

            $usuario = AuthService::isAuthenticated() ? AuthService::getUser() : null;

            ViviService::registrarFeedback(
                (string) $request->input('rating', ''),
                (string) $request->input('message', ''),
                (string) $request->input('reply', ''),
                $usuario
            );

            Response::json($this->success(['registrado' => true]), 200, self::CABECALHOS);
        } catch (ApiException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;

            Response::json(
                $this->error($e->getMessage(), $status, $e->getDetails()),
                $status,
                self::CABECALHOS
            );
        } catch (Throwable $e) {
            Logger::exception($e, ['servico' => 'ViviController', 'etapa' => 'feedback']);

            Response::json(
                $this->error('Não foi possível registrar o feedback.', 500),
                500,
                self::CABECALHOS
            );
        }
    }

    public function status(Request $request): void
    {
        Response::json($this->success(ViviService::status()), 200, self::CABECALHOS);
    }
}
