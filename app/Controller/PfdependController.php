<?php

namespace App\Controller;

use App\Core\Alerts\AlertManager;
use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Service\AuthService;
use App\Service\LogService;
use App\Service\PfdependService;
use RuntimeException;
use Throwable;

class PfdependController extends BaseController
{
    public function indexView(Request $request): void
    {
        try {
            $previa = PfdependService::previa();

            view('funcionarios/dependentes', [
                'previa' => $previa,
                'title'  => 'Salario Familia - folha'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage(), false, $e);
        }
    }

    public function atualizar(Request $request): void
    {
        try {
            if ((string) $request->post('confirmacao', '') !== 'SIM') {
                throw new RuntimeException('Confirmação não enviada. Atualize a página e use o botão da tela.');
            }

            $resultado = PfdependService::atualizar();
            $mensagem = self::mensagem($resultado);

            $this->logAcao($request, 'atualizar_pfdepend', $mensagem, [
                'competencia' => $resultado['competencia']['rotulo'],
                'referencia'  => $resultado['competencia']['referencia'],
                'contadores'  => $resultado['contadores'],
                'alterados'   => $resultado['alterados'],
            ]);

            AlertManager::add('success', $mensagem);
        } catch (Throwable $e) {
            Logger::exception($e);

            AlertManager::add('error', $e->getMessage());
        }

        redirect('/funcionarios/dependentes');
    }

    private static function mensagem(array $resultado): string
    {
        return sprintf(
            'Competência %s: %d dependente(s) com INCSALFAM zerada(s) e %d marcado(s) com CARTAOVACINA/FREQESCOLAR.',
            $resultado['competencia']['rotulo'],
            $resultado['alterados']['incsalfam'],
            $resultado['alterados']['cartaovacina']
        );
    }

    private function logAcao(Request $request, string $acao, string $mensagem, array $contexto = []): void
    {
        $user = AuthService::getUser() ?? [];

        LogService::store([
            'nivel'        => 'INFO',
            'tipo'         => 'UPDATE',
            'modulo'       => 'funcionarios',
            'acao'         => $acao,
            'usuario_id'   => $user['id'] ?? null,
            'chapa'        => $user['chapa'] ?? null,
            'usuario_nome' => $user['name'] ?? $user['username'] ?? null,
            'metodo_http'  => $request->method(),
            'rota'         => $request->uri(),
            'ip'           => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'mensagem'     => $mensagem,
            'contexto'     => $contexto,
        ]);
    }
}
