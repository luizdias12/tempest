<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\CarouselService;

class HomeController
{
    public function indexView(): void
    {
        $user = AuthService::getUserName();

        view('home', [
            'title' => 'Home',
            'data' => 'Olá, ' . htmlspecialchars(initcap($user ?? 'Visitante')),
            'slides' => CarouselService::slidesAtivos(),
        ]);
    }
}