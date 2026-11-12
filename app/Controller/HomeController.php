<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\CarouselService;

class HomeController
{
    public function indexView(): void
    {
        $user = AuthService::getUser();
        $nome = trim($user['nomesocial'] ?? '') ?: $user['name'];

        $msgDia = $_SESSION['msg_dia'] ?? null;
        unset($_SESSION['msg_dia']);

        view('home', [
            'title' => 'Home',
            'usuario' => 'Olá, ' . htmlspecialchars(initcap($nome ?? 'Visitante')),
            'slides' => CarouselService::slidesAtivos(),
            'msgDia' => $msgDia,
        ]);
    }
}