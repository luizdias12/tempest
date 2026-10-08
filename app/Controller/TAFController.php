<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Service\TAFService;
use Throwable;

class TAFController extends BaseController
{
    public function indexView(Request $request): void
    {
        view('taf/index', [
            'title' => 'Eventos Esocial - TAF',
            'taf' => 'TAF'
        ]);
    }
}