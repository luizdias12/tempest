<?php

namespace App\Controller;

use App\Service\AuthService;

class HomeController
{
    public function indexView(): void
    {
        $user = AuthService::getUserName();
        view('home', [
            'title' => 'Home',
            'data' => 'Bem Vindo! ' . '<br>' . htmlspecialchars(initcap($user) ?? 'Visitante'),
        ]);
    }
}