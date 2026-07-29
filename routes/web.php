<?php

/** @var $router App\Core\Router */

use App\Controller\AuthController;
use App\Controller\ErrorController;
use App\Controller\FuncionarioController;
use App\Controller\HomeController;
use App\Middleware\ApiMiddleware;
use App\Middleware\AuthMiddleware;

//Middlewares
$router->aliasMiddleware('auth', AuthMiddleware::class);
$router->aliasMiddleware('api', ApiMiddleware::class);

// ROTAS GET

//HomeController
$router->get('/', [HomeController::class, 'indexView']);

//ErrorController
$router->get('/error', [ErrorController::class, 'indexView']);

$router->get('/login', [AuthController::class, 'loginView']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/funcionarios/index', [FuncionarioController::class, 'indexView'], ['auth']);
$router->get('/ti/lista', [FuncionarioController::class, 'listaView'], ['auth']);
$router->get('/ti/lista/download', [FuncionarioController::class, 'listaDownload'], ['auth']);

// ROTAS POST

$router->post('/login', [AuthController::class, 'login']);