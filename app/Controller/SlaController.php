<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Model\Mysql\HelpdeskModel;
use App\Service\AuthService;
use App\Service\LogService;
use App\Service\SlaService;
use Throwable;

class SlaController extends BaseController
{
    public function indexView(Request $request): void
    {
        try {
            if (!self::podeGerenciar()) {
                ErrorHandler::handle(403, 'Acesso não permitido!', false);
                return;
            }

            view('sla/gestao', [
                'title' => 'Gestão de SLA',
                'bases' => SlaService::listarBases(),
                'basesAtivas' => SlaService::listarBasesAtivas(),
                'calendarios' => SlaService::listarCalendarios(),
                'calendariosAtivos' => SlaService::listarCalendariosAtivos(),
                'regras' => SlaService::listarRegras(),
                'grupos' => HelpdeskModel::listarGrupos(),
                'subgrupos' => HelpdeskModel::listarSubgrupos(),
                'statusSla' => SlaService::listarStatus(),
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage(), false, $e);
        }
    }

    public function salvarBase(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return SlaService::salvarBase(
                $this->idOuNull($request, 'id'),
                (string) $request->post('nome', ''),
                (string) $request->post('descricao', ''),
                (string) $request->post('ativo', '') === '1'
            );
        }, 'salvar_sla_base');
    }

    public function excluirBase(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return SlaService::excluirBase((int) $request->post('id', 0));
        }, 'excluir_sla_base');
    }

    public function salvarCalendario(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            $dias = $request->post('dias', []);

            return SlaService::salvarCalendario(
                $this->idOuNull($request, 'id'),
                (string) $request->post('nome', ''),
                is_array($dias) ? $dias : [],
                (string) $request->post('hora_inicio', ''),
                (string) $request->post('hora_fim', ''),
                $this->vazioParaNull((string) $request->post('intervalo_inicio', '')),
                $this->vazioParaNull((string) $request->post('intervalo_fim', '')),
                (string) $request->post('ativo', '') === '1'
            );
        }, 'salvar_sla_calendario');
    }

    public function excluirCalendario(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return SlaService::excluirCalendario((int) $request->post('id', 0));
        }, 'excluir_sla_calendario');
    }

    public function salvarRegra(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            $prazoResp = (int) $request->post('prazo_primeira_resposta_min', 0);
            $prazoResol = (int) $request->post('prazo_resolucao_min', 0);

            return SlaService::salvarRegra(
                $this->idOuNull($request, 'id'),
                (int) $request->post('sla_id', 0),
                $this->idOuNull($request, 'grupo_id'),
                $this->idOuNull($request, 'subgrupo_id'),
                $this->idOuNull($request, 'calendario_id'),
                $prazoResp > 0 ? $prazoResp : null,
                $prazoResol > 0 ? $prazoResol : null,
                max(0, (int) $request->post('ordem', 0)),
                (string) $request->post('ativo', '') === '1'
            );
        }, 'salvar_sla_regra');
    }

    public function excluirRegra(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return SlaService::excluirRegra((int) $request->post('id', 0));
        }, 'excluir_sla_regra');
    }

    public function salvarStatus(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return SlaService::salvarStatus(
                $this->idOuNull($request, 'id'),
                (int) $request->post('sla_id', 0),
                (int) $request->post('status_id', 0),
                (string) $request->post('contabiliza_tempo', '') === '1'
            );
        }, 'salvar_sla_status');
    }

    public function excluirStatus(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return SlaService::excluirStatus((int) $request->post('id', 0));
        }, 'excluir_sla_status');
    }

    private function acaoGestao(Request $request, callable $acao, string $acaoNome): void
    {
        if (!self::podeGerenciar()) {
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
            'modulo' => 'sla',
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

    private function idOuNull(Request $request, string $campo): ?int
    {
        $valor = (int) $request->post($campo, 0);

        return $valor > 0 ? $valor : null;
    }

    private function vazioParaNull(string $valor): ?string
    {
        return trim($valor) !== '' ? trim($valor) : null;
    }

    private static function podeGerenciar(): bool
    {
        return AuthService::hasPermission('gestao de processos') || AuthService::hasPermission('ti');
    }
}