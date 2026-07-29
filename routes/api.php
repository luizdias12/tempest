<?php


/** @var $router App\Core\Router */

use App\Controller\DateTimeController;
use App\Controller\FuncionarioController;
use App\Middleware\CorsMiddleware;

$router->group('/api', function($router) {
    // ROTAS GET

    $router->get('/datetime/formats', [DateTimeController::class , 'formats']);

    $router->get('/funcionarios', [FuncionarioController::class , 'index']);
    $router->get('/funcionarios/chapa/{chapa}', [FuncionarioController::class , 'showByChapa']);
    $router->get('/funcionarios/nome/{nome}', [FuncionarioController::class , 'findByNome']);

    // ROTAS POST
        
}, [
    CorsMiddleware::class
]);