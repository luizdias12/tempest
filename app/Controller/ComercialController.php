<?php

namespace App\Controller;

use App\Core\Alerts\AlertManager;
use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;
use App\Service\ComercialService;
use App\Service\DateTimeService;
use App\Service\FuncionarioService;
use App\Service\GenericService;
use App\Service\LogService;
use Throwable;

class ComercialController extends BaseController
{
    public function indexView(Request $request): void
    {
        $mesRef = DateTimeService::mesAtual(true);

        try {
            view('comercial/index', [
                'title' => 'Escala Comercial',
                'escalas' => GenericService::escalaComercial($mesRef),
                'ferias' => FuncionarioService::feriasComercial(),
                'descricaoMes' => DateTimeService::stringMes($mesRef),
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage(), false, $e);
        }
    }

    public function gestaoView(Request $request): void
    {
        try {
            if (!AuthService::canManageComercial()) {
                ErrorHandler::handle(403, 'Acesso não permitido!', false);
                return;
            }

            $mesRef = preg_replace('/\D/', '', (string) $request->query('mesref', ''));
            $mesRef = $mesRef !== '' ? str_pad($mesRef, 2, '0', STR_PAD_LEFT) : DateTimeService::mesAtual(true);

            view('comercial/gestao', [
                'title' => 'Gestão da Escala Comercial',
                'escalas' => ComercialService::listar($mesRef),
                'compradores' => FuncionarioService::listaCompradores(),
                'mesref' => $mesRef,
                'meses' => self::meses(),
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage(), false, $e);
        }
    }

    public function salvar(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            $id = (int) $request->post('id', 0);

            return ComercialService::salvar(
                $id > 0 ? $id : null,
                (string) $request->post('data', ''),
                (string) $request->post('comprador', ''),
                (string) $request->post('feriado', '')
            );
        }, 'salvar_plantao');
    }

    public function excluir(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return ComercialService::excluir((int) $request->post('id', 0));
        }, 'excluir_plantao');
    }

    private function acaoGestao(Request $request, callable $acao, string $acaoNome): void
    {
        if (!AuthService::canManageComercial()) {
            Response::json(['success' => false, 'message' => 'Acesso não permitido!'], 403);
            return;
        }

        try {
            $mensagem = $acao();

            $this->logAcao($request, $acaoNome, $mensagem, $request->post());

            Response::json($this->success(null, ['timestamp' => date('c')], $mensagem));
        } catch (Throwable $e) {
            Logger::exception($e);

            Response::json($this->error($e->getMessage(), 400), 400);
        }
    }

    private function logAcao(Request $request, string $acao, string $mensagem, array $contexto = []): void
    {
        $user = AuthService::getUser() ?? [];

        LogService::store([
            'nivel' => 'INFO',
            'tipo' => 'INSERT',
            'modulo' => 'comercial',
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

    private static function meses(): array
    {
        $meses = [];

        foreach (range(1, 12) as $mes) {
            $chave = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
            $meses[] = [
                'codigo' => $chave,
                'nome' => DateTimeService::stringMes($chave),
            ];
        }

        return $meses;
    }
}
