<?php

namespace App\Controller;

class HomeController
{
    public function indexView(): void
    {
        view('home', [
            'title' => '',
            'data' => 'Bem Vindo!'
        ]);
    }
}