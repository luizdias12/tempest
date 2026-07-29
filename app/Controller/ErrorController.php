<?php

namespace App\Controller;

class ErrorController
{
    public function indexView($statusCode, $message): void
    {
        view('error', [
            'errorCode' => $statusCode,
            'errorMessage' => $message
        ]);
    }
}