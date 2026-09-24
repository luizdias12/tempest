<?php

namespace App\Controller;

use App\Core\Request;

class ErrorController
{
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
}