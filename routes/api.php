<?php


/** @var $router App\Core\Router */

use App\Controller\AuthController;
use App\Controller\DateTimeController;
use App\Controller\FuncionarioController;
use App\Controller\FinancController;
use App\Middleware\ApiMiddleware;
use App\Middleware\CorsMiddleware;

//Middlewares
$router->aliasMiddleware('auth.api', ApiMiddleware::class);
$router->aliasMiddleware('cors', CorsMiddleware::class);

$router->group('/api', function($router) {

// ROTAS GET

$router->get('/datetime/formats', [DateTimeController::class , 'formats']);

$router->get('/funcionarios', [FuncionarioController::class , 'index']);
$router->get('/funcionarios/chapa/{chapa}', [FuncionarioController::class , 'findByChapa']);
$router->get('/funcionarios/nome/{nome}', [FuncionarioController::class , 'findByNome']);

$router->get('/financ/holerite/{chapa}/{mescomp}/{anocomp}/{periodo}', [FinancController::class, 'totaisHolerite'], ['auth.api']);

// ROTAS POST

$router->post('/auth/login', [AuthController::class, 'apiLogin']);
$router->get('/auth/me', [AuthController::class, 'me'], ['auth.api']);
        
}, ['cors']);