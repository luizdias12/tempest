<?php

namespace App\Controller;

use App\Core\Request;

class ErrorController
{
    /**
     * indexView
     *
     * @param mixed $statusCode
     * @param mixed $message
     * @param mixed $currentUri
     * @param mixed $referer
     * @return void
     */
    public function indexView($statusCode, $message, $currentUri, $referer): void
    {
        view('error', [
            'errorCode' => $statusCode,
            'errorMessage' => $message,
            'currentUri' => $currentUri,
            'referer' => $referer,
            'title' => 'Erro HTTP',
        ]);
    }
    public function manutencaoView(): void
    {
        simpleView('manutencao', [
            'title' => 'Pagina em Manutenção',
        ]);
    }
}