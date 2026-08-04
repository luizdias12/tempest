<?php

/** @var $router App\Core\Router */

use App\Controller\AuthController;
use App\Controller\ErrorController;
use App\Controller\FinancController;
use App\Controller\FuncionarioController;
use App\Controller\HelpdeskController;
use App\Controller\HomeController;
use App\Middleware\AuthMiddleware;

//Middlewares
$router->aliasMiddleware('auth', AuthMiddleware::class);

/*----------------------------------- ROTAS GET -----------------------------------*/

//HomeController
$router->get('/', [HomeController::class, 'indexView']);

//ErrorController
$router->get('/error', [ErrorController::class, 'indexView']);

//AuthController
$router->get('/login', [AuthController::class, 'loginView']);
$router->get('/logout', [AuthController::class, 'logout']);

//FinancController
$router->get('/financ/holerite', [FinancController::class, 'holeriteView'], ['auth']);

//FuncionarioController
$router->get('/funcionarios/index', [FuncionarioController::class, 'indexView'], ['auth']);
$router->get('/ti/lista', [FuncionarioController::class, 'listaView'], ['auth']);
$router->get('/ti/lista/download', [FuncionarioController::class, 'listaDownload'], ['auth']);

//HelpdeskController
$router->get('/helpdesk/index', [HelpdeskController::class, 'indexView'], ['auth']);

/*----------------------------------- ROTAS POST -----------------------------------*/

$router->post('/login', [AuthController::class, 'login']);