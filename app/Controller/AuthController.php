<?php

namespace App\Controller;

use App\Core\Alerts\AlertManager;
use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;
use App\Service\LogService;
use App\Service\OnlineService;

class AuthController extends BaseController
{
    public function loginView(Request $request): void
    {
        if (AuthService::isAuthenticated()) {
            redirect('/');
            return;
        }

        $error = $request->query('error', '');
        view(
            'auth/login',
            [
                'error' => $error,
                'title' => 'Login',
            ]
        );
    }

    public function login(Request $request): void
    {
        $username = $request->input('username', '');
        $password = $request->input('password', '');

        if (AuthService::login($username, $password)) {

            AlertManager::add('success', 'Login com sucesso.');
            $user = AuthService::getUser() ?? [];

            LogService::store([
                'nivel' => 'INFO',
                'tipo' => 'LOGIN',
                'modulo' => 'auth',
                'acao' => 'autenticar',
                'usuario_id' => $user['id'] ?? null,
                'chapa' => $user['chapa'] ?? null,
                'usuario_nome' => $user['name'] ?? $user['username'] ?? null,
                'metodo_http' => $request->method(),
                'rota' => $request->uri(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'mensagem' => "usuário autenticado com sucesso ({$username})",
            ]);

            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            OnlineService::registrarLogin((string) ($user['cpf'] ?? ''), session_id(), $ip, localPorIp($ip));

            redirect('/');
        } else {

            LogService::store([
                'nivel' => 'ERROR',
                'tipo' => 'LOGIN',
                'modulo' => 'auth',
                'acao' => 'autenticar',
                'usuario_id' => $user['id'] ?? null,
                'chapa' => $user['chapa'] ?? null,
                'usuario_nome' => $user['name'] ?? $user['username'] ?? null,
                'metodo_http' => $request->method(),
                'rota' => $request->uri(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'mensagem' => "falha na autenticaçao do usuário ({$username})",
            ]);

            AlertManager::add('error', 'Credenciais inválidas.');

            redirect('/login');
        }
    }

    public function logout(Request $request): void
    {
        $cpf = AuthService::getUserCpf();

        AuthService::logout();

        if ($cpf !== null) {
            OnlineService::registrarLogout($cpf);
        }

        redirect('/login');
    }

    public function apiLogin(Request $request): array
    {
        return $this->handle(function () use ($request) {
            $username = $request->input('username', '');
            $password = $request->input('password', '');

            $token = AuthService::loginJwt($username, $password);

            if ($token === null) {
                return $this->error('Credenciais inválidas', 401);
            }

            return $this->success([
                'token' => $token,
                'type' => 'Bearer',
            ]);
        });
    }

    public function me(Request $request): array
    {
        return $this->handle(function () use ($request) {
            $user = $request->getAttribute('user');

            if (!$user) {
                return $this->error('Não autenticado', 401);
            }

            return $this->success($user->all());
        });
    }
}
