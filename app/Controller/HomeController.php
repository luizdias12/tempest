<?php

namespace App\Controller;

class HomeController
{
    public function indexView(): void
    {
        view('home', [
            'title' => 'Tempest - API da base RM (Oracle)',
            'data' => 'Ola, seja bem-vindo ao servidor Tempest, uma API para a base de dados do sistema RM.'
        ]);
    }
}