<?php

namespace App\Controller;

use App\Core\Alerts\AlertManager;
use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;

class AuthController extends BaseController
{
    public function loginView(Request $request): void
    {
        if (AuthService::isAuthenticated()) {
            redirect('/');
            return;
        }

        $error = $request->query('error', '');
        view('auth/login', ['error' => $error]);
    }

    public function login(Request $request): void
    {
        $username = $request->input('username', '');
        $password = $request->input('password', '');

        if (AuthService::login($username, $password)) {

            AlertManager::add('success', 'Login com sucesso.');

            redirect('/');

        } else {

            AlertManager::add('error', 'Credenciais inválidas.');

            redirect('/login');

        }
    }

    public function logout(Request $request): void
    {
        AuthService::logout();
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