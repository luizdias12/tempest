<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Model\Mysql\ContatoModel;
use App\Model\Mysql\FilialModel;
use App\Service\ContatoService;
use Throwable;

class ContatoController extends BaseController
{
    public function indexView(Request $request): void
    {
        try {
            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 25)));
            $nome = trim((string) $request->query('nome', ''));
            $email = trim((string) $request->query('email', ''));
            $ramal = trim((string) $request->query('ramal', ''));
            $setor = trim((string) $request->query('setor', ''));
            $filial = trim((string) $request->query('filial', ''));

            $resultado = ContatoService::listar(
                $page,
                $limit,
                $nome !== '' ? $nome : null,
                $email !== '' ? $email : null,
                $ramal !== '' ? $ramal : null,
                $setor !== '' ? $setor : null,
                $filial !== '' ? $filial : null
            );

            view('contatos/index', [
                    'data' => $resultado['data'],
                    'meta' => $resultado['meta'],
                'filtroNome' => $nome,
                'filtroEmail' => $email,
                'filtroRamal' => $ramal,
                'filtroSetor' => $setor,
                'filtroFilial' => $filial,
                'setores' => ContatoModel::listarSetores(),
                'filiais' => FilialModel::all(),
                'title' => 'Lista de Contatos'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);
            ErrorHandler::handle(500, $e->getMessage(), false, $e);
        }
    }
}
