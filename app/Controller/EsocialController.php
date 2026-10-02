<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Service\EsocialService;
use Throwable;

class EsocialController extends BaseController
{
    public function indexView(Request $request): void
    {
        try {

            $esocialData = EsocialService::getEsocialData();
            $fullData = EsocialService::getFullData();

            view('esocial/index', [
                'data' => $esocialData,
                'fullData' => $fullData,
                'title' => 'Dados do eSocial'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);
            ErrorHandler::handle(500, $e->getMessage(), false, $e);
        }
    }
}
