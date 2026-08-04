<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Service\HelpdeskService;
use Throwable;

class HelpdeskController extends BaseController
{
    public function indexView(Request $request): void
    {
        try {
            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 10)));
            $id = $request->input('id', $request->query('id', ''));
            $emitente = $request->input('emitente', $request->query('emitente', ''));
            $status = $request->input('status', $request->query('status', ''));
            $local = $request->input('local', $request->query('local', ''));

            $result = HelpdeskService::chamadosAbertos($page, $limit, $id ?: null, $emitente ?: null, $status ?: null, $local ?: null);
            view('helpdesk/index', [
                'chamados' => $result['data'],
                'meta' => $result['meta'],
                'id' => $id,
                'emitente' => $emitente,
                'status' => $status,
                'local' => $local,
                'title' => 'Helpdesk'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }
}
