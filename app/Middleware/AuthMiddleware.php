<?php

namespace App\Middleware;

use App\Core\Response;
use App\Service\AuthService;
use App\Service\OnlineService;

class AuthMiddleware
{
    public function handle(): bool
    {
        $ehJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

        if (!AuthService::isAuthenticated()) {
            if ($ehJson) {
                Response::json(['error' => ['message' => 'Não autenticado.']], 401);
            } else {
                redirect('/login');
            }

            return false;
        }

        $user = AuthService::getUser() ?? [];
        $cpf = (string) ($user['cpf'] ?? '');
        $sessionId = session_id();

        if ($cpf !== '' && $sessionId !== '') {
            $status = OnlineService::situacaoSessao($sessionId, $cpf);

            if ($status === '2') {
                AuthService::logout();
                $this->interromper($ehJson);
                return false;
            }

            if ($status === null) {
                OnlineService::registrarLogin($cpf, $sessionId, $_SERVER['REMOTE_ADDR'] ?? '', localPorIp($_SERVER['REMOTE_ADDR'] ?? ''));
                return true;
            }

            $limiteMin = (int) ($_ENV['SESSION_INATIVIDADE_MIN'] ?? 480);

            if (OnlineService::encerrarSeInativa($sessionId, $cpf, $limiteMin)) {
                AuthService::logout();
                $this->interromper($ehJson, 'Sessão expirada por inatividade. Faça login novamente.');
                return false;
            }

            self::registrarAtividade($sessionId);
        }

        return true;
    }

    private function interromper(bool $ehJson, string $mensagem = ''): void
    {
        if ($ehJson) {
            Response::json(['error' => ['message' => $mensagem !== '' ? $mensagem : 'Não autenticado.']], 401);
            return;
        }

        $query = $mensagem !== '' ? '?error=' . urlencode($mensagem) : '';
        redirect('/login' . $query);
    }

    private static function registrarAtividade(string $sessionId): void
    {
        // Só conta como atividade real: navegação de página (Accept text/html),
        // ação explícita (POST) ou sinal de interação enviado pelo cliente.
        // Pollings de fundo (notificações a cada 15s, stream do chat) NÃO renovam
        // o relógio de inatividade.
        $ehNavegacao = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false;
        $ehAcao = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $usuarioAtivo = ($_SERVER['HTTP_X_USUARIO_ATIVO'] ?? '') === '1';

        if ($ehAcao || $ehNavegacao || $usuarioAtivo) {
            OnlineService::heartbeat($sessionId);
        }
    }
}