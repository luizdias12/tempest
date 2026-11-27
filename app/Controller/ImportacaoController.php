<?php

namespace App\Controller;

use App\Core\Alerts\AlertManager;
use App\Core\BaseController;
use App\Core\Logger;
use App\Core\Request;
use App\Service\AuthService;
use App\Service\ImportacaoUnimedService;
use App\Service\LogService;
use Throwable;

class ImportacaoController extends BaseController
{
    public function unimedView(Request $request): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['csrf_importacao'])) {
            $_SESSION['csrf_importacao'] = bin2hex(random_bytes(32));
        }

        $resultado = $_SESSION['importacao_unimed_resultado'] ?? null;
        unset($_SESSION['importacao_unimed_resultado']);

        view('importacao/unimed', [
            'csrf' => $_SESSION['csrf_importacao'] ?? '',
            'resultado' => is_array($resultado) ? $resultado : null,
            'camposLayout' => ImportacaoUnimedService::camposLayout(),
            'title' => 'Importação de Arquivos',
        ]);
    }

    public function importarUnimed(Request $request): void
    {
        if (!$this->validarCsrf($request)) {
            AlertManager::add('error', 'Sessão expirada. Recarregue a página e tente novamente.');
            redirect('/importacao/unimed');
            return;
        }

        $mes = (string) $request->post('mes', '');
        $ano = (string) $request->post('ano', '');

        try {
            $resultado = ImportacaoUnimedService::importar($request->file('arquivo') ?? [], $mes, $ano);

            $_SESSION['importacao_unimed_resultado'] = $resultado;

            $mensagem = "Importação concluída: {$resultado['inseridos']} registro(s) inserido(s)";

            if (!empty($resultado['erros'])) {
                $mensagem .= ', ' . count($resultado['erros']) . ' linha(s) com erro.';
                AlertManager::add('warning', $mensagem);
            } else {
                $mensagem .= '.';
                AlertManager::add('success', $mensagem);
            }

            $this->logAcao($request, $mensagem, [
                'mes_comp' => $resultado['mes'],
                'ano_comp' => $resultado['ano'],
                'arquivo' => (string) (($request->file('arquivo') ?? [])['name'] ?? ''),
                'linhas' => $resultado['linhas'],
                'inseridos' => $resultado['inseridos'],
                'erros' => count($resultado['erros']),
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            AlertManager::add('error', $e->getMessage());
        }

        redirect('/importacao/unimed');
    }

    private function validarCsrf(Request $request): bool
    {
        $token = $request->post('_csrf', '');

        if (!is_string($token)) {
            $token = '';
        }

        $sessao = (string) ($_SESSION['csrf_importacao'] ?? '');

        return $token !== '' && $sessao !== '' && hash_equals($sessao, $token);
    }

    private function logAcao(Request $request, string $mensagem, array $contexto = []): void
    {
        $user = AuthService::getUser() ?? [];

        LogService::store([
            'nivel' => 'INFO',
            'tipo' => 'INSERT',
            'modulo' => 'importacao',
            'acao' => 'importar_unimed',
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
