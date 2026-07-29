<?php

namespace App\Controller;

use App\Core\Request;
use App\Service\AuthService;

class AuthController
{
    public function loginView(Request $request): void
    {
        if (AuthService::isAuthenticated()) {
            redirect('/funcionarios/index');
            return;
        }

        $error = $request->query('error', '');
        view('auth/login', ['error' => $error]);
    }

    public function listaView(Request $request): void
    {
        if (AuthService::isAuthenticated()) {
            redirect('/ti/lista');
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
            redirect('/funcionarios/index');
        } else {
            redirect('/login?error=1');
        }
    }

    public function logout(Request $request): void
    {
        AuthService::logout();
        redirect('/login');
    }
}