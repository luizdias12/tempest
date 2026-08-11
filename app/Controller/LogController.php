<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Service\AuthService;
use App\Service\LogService;
use Throwable;

class LogController extends BaseController
{
    private const NIVELS_VALIDOS = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];

    public function indexView(Request $request): void
    {
        try {
            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 20)));

            $nivel = trim((string) $request->query('nivel', ''));
            $tipo = trim((string) $request->query('tipo', ''));
            $modulo = trim((string) $request->query('modulo', ''));
            $busca = trim((string) $request->query('busca', ''));
            $data = trim((string) $request->query('data', ''));

            $result = LogService::index($page, $limit, $nivel, $tipo, $modulo, $busca, $data);

            view('logs/index', [
                'logs' => $result['data'],
                'meta' => $result['meta'],
                'nivel' => $nivel,
                'tipo' => $tipo,
                'modulo' => $modulo,
                'busca' => $busca,
                'data' => $data,
                'title' => 'Logs',
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function store(Request $request): void
    {
        try {
            $user = AuthService::getUser() ?? [];

            $nivel = strtoupper(trim((string) $request->post('nivel', 'INFO')));
            if (!in_array($nivel, self::NIVELS_VALIDOS, true)) {
                $nivel = 'INFO';
            }

            $id = LogService::store([
                'nivel' => $nivel,
                'tipo' => trim((string) $request->post('tipo', '')),
                'modulo' => trim((string) $request->post('modulo', '')),
                'acao' => trim((string) $request->post('acao', '')),
                'mensagem' => trim((string) $request->post('mensagem', '')),
                'contexto' => $request->post('contexto', []),
            ]);

            if ($id === null) {
                Logger::error('Erro ao registrar o log.', [
                    'nivel' => $nivel,
                    'tipo' => trim((string) $request->post('tipo', '')),
                    'modulo' => trim((string) $request->post('modulo', '')),
                    'acao' => trim((string) $request->post('acao', '')),
                    'usuario_id' => $user['id'] ?? null,
                    'chapa' => $user['chapa'] ?? null,
                    'usuario_nome' => $user['name'] ?? $user['username'] ?? null,
                    'metodo_http' => $request->method(),
                    'rota' => $request->uri(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'mensagem' => trim((string) $request->post('mensagem', '')),
                    'contexto' => $request->post('contexto', []),
                ]);
            }

            ErrorHandler::handle(204, 'Erro ao registrar o log.');
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }
}
