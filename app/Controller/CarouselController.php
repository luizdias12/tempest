<?php

namespace App\Controller;

use App\Core\Alerts\AlertManager;
use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;
use App\Service\CarouselService;
use App\Service\LogService;
use Throwable;

class CarouselController extends BaseController
{
    public function gestaoView(Request $request): void
    {
        try {
            view('carousel/gestao', [
                'slides' => CarouselService::listarTodos(),
                'title' => 'Gestão do Home'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function salvar(Request $request): void
    {
        try {
            if (!AuthService::hasPermission('ti')) {
                AlertManager::add('error', 'Acesso não permitido.');
                redirect('/carousel/gestao');
                return;
            }

            $mensagem = CarouselService::salvarNovo(
                $request->file('slideArquivo') ?? [],
                (string) $request->post('dtinicio', ''),
                (string) $request->post('dtfim', ''),
                (string) $request->post('ativo', 'N'),
                (string) $request->post('link', '')
            );

            $this->logAcao($request, 'criar_slide', $mensagem, $request->post());

            AlertManager::add('success', $mensagem);
        } catch (Throwable $e) {
            Logger::exception($e);

            AlertManager::add('error', $e->getMessage());
        }

        redirect('/carousel/gestao');
    }

    public function ativo(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return CarouselService::alternarAtivo(
                (int) $request->post('id', 0),
                (string) $request->post('ativo', 'N')
            );
        }, 'alternar_slide', true);
    }

    public function ordem(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            $id = (int) $request->post('id', 0);
            $direcao = (string) $request->post('direcao', 'subir');

            return $direcao === 'subir'
                ? CarouselService::subir($id)
                : CarouselService::descer($id);
        }, 'ordenar_slide', true);
    }

    public function excluir(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return CarouselService::excluir((int) $request->post('id', 0));
        }, 'excluir_slide');
    }

    private function acaoGestao(Request $request, callable $acao, string $acaoNome, bool $json = false): void
    {
        try {
            if (!AuthService::hasPermission('ti')) {
                if ($json) {
                    Response::json(['success' => false, 'message' => 'Acesso não permitido.'], 403);
                    return;
                }

                AlertManager::add('error', 'Acesso não permitido.');
                redirect('/carousel/gestao');
                return;
            }

            $mensagem = $acao();

            $this->logAcao($request, $acaoNome, $mensagem, $request->post());

            if ($json) {
                Response::json(['success' => true, 'message' => $mensagem]);
                return;
            }

            AlertManager::add('success', $mensagem);
        } catch (Throwable $e) {
            Logger::exception($e);

            if ($json) {
                Response::json(['success' => false, 'message' => $e->getMessage()], 400);
                return;
            }

            AlertManager::add('error', $e->getMessage());
        }

        redirect('/carousel/gestao');
    }

    private function logAcao(Request $request, string $acao, string $mensagem, array $contexto = []): void
    {
        $user = AuthService::getUser() ?? [];

        LogService::store([
            'nivel' => 'INFO',
            'tipo' => 'INSERT',
            'modulo' => 'carousel',
            'acao' => $acao,
            'usuario_id' => $user['id'] ?? null,
            'chapa' => $user['chapa'] ?? null,
            'usuario_nome' => $user['name'] ?? $user['username'] ?? null,
            'metodo_http' => $request->method(),
            'rota' => $request->uri(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'mensagem' => $mensagem,
            'contexto' => $contexto,
        ]);
    }
}