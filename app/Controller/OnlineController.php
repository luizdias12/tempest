<?php

namespace App\Controller;

use App\Core\Alerts\AlertManager;
use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Service\GenericService;
use App\Service\OnlineService;
use Throwable;

class OnlineController extends BaseController
{
    public function indexView(Request $request): void
    {
        try {
            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 20)));

            $busca = trim((string) $request->query('busca', ''));
            $local = trim((string) $request->query('local', ''));

            $result = OnlineService::listarOnline($page, $limit, $busca !== '' ? $busca : null, $local !== '' ? $local : null);

            view('online/index', [
                'usuarios' => $result['data'],
                'meta' => $result['meta'],
                'locais' => GenericService::listarLocais(),
                'busca' => $busca,
                'local' => $local,
                'title' => 'Usuários Online',
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage(), false, $e);
        }
    }

    public function deslogar(Request $request): void
    {
        try {
            $cpf = trim((string) $request->post('cpf', ''));

            if ($cpf === '') {
                AlertManager::add('error', 'Usuário inválido.');
                redirect('/online');
                return;
            }

            OnlineService::forcarLogout($cpf);
            AlertManager::add('success', "Usuário {$cpf} desconectado.");

            redirect('/online' . ($request->query() !== [] ? '?' . http_build_query($request->query()) : ''));
        } catch (Throwable $e) {
            Logger::exception($e);

            AlertManager::add('error', 'Erro ao desconectar o usuário.');
            redirect('/online');
        }
    }
}
