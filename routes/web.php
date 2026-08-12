<?php

/** @var $router App\Core\Router */

use App\Controller\AuthController;
use App\Controller\CarouselController;
use App\Controller\DocController;
use App\Controller\ErrorController;
use App\Controller\FinancController;
use App\Controller\FuncionarioController;
use App\Controller\HelpdeskController;
use App\Controller\HomeController;
use App\Controller\LogController;
use App\Controller\OnlineController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

//Middlewares
$router->aliasMiddleware('auth', AuthMiddleware::class);
$router->aliasMiddleware('role', RoleMiddleware::class);

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
$router->get('/financ/holerite/pdf', [FinancController::class, 'holeritePdf'], ['auth']);

//FuncionarioController
$router->get('/funcionarios/index', [FuncionarioController::class, 'indexView'], ['auth', 'role']);
$router->get('/funcionarios/aniversariantes', [FuncionarioController::class, 'aniversariantesView']);
$router->get('/ti/lista', [FuncionarioController::class, 'listaView'], ['auth']);
$router->get('/ti/lista/download', [FuncionarioController::class, 'listaDownload'], ['auth']);

//HelpdeskController
$router->get('/helpdesk/index', [HelpdeskController::class, 'indexView'], ['auth']);
$router->get('/helpdesk/historico/{id}', [HelpdeskController::class, 'historicoJson'], ['auth']);

//DocController
$router->get('/documentos', [DocController::class, 'indexView'], ['auth']);
$router->get('/documentos/abrir/{id}', [DocController::class, 'abrir'], ['auth']);
$router->get('/documentos/visualizar/{id}', [DocController::class, 'visualizar'], ['auth', 'role']);
$router->get('/documentos/gestao', [DocController::class, 'gestaoView'], ['auth', 'role']);
$router->post('/documentos/upload', [DocController::class, 'upload'], ['auth', 'role']);
$router->post('/documentos/diretorio', [DocController::class, 'diretorio'], ['auth', 'role']);
$router->post('/documentos/subdiretorio', [DocController::class, 'subdiretorio'], ['auth', 'role']);
$router->post('/documentos/excluir-diretorio', [DocController::class, 'excluirDiretorio'], ['auth', 'role']);
$router->post('/documentos/excluir-subdiretorio', [DocController::class, 'excluirSubdiretorio'], ['auth', 'role']);
$router->post('/documentos/permissao/adicionar', [DocController::class, 'adicionarPermissao'], ['auth', 'role']);
$router->post('/documentos/permissao/remover', [DocController::class, 'removerPermissao'], ['auth', 'role']);
$router->post('/documentos/geral', [DocController::class, 'geral'], ['auth', 'role']);
$router->post('/documentos/copiar-permissoes', [DocController::class, 'copiarPermissoes'], ['auth', 'role']);
$router->post('/documentos/nova-versao', [DocController::class, 'novaVersao'], ['auth', 'role']);
$router->post('/documentos/versao', [DocController::class, 'versao'], ['auth', 'role']);
$router->post('/documentos/versao/excluir', [DocController::class, 'excluirVersao'], ['auth', 'role']);
$router->post('/documentos/excluir-documento', [DocController::class, 'excluirDocumento'], ['auth', 'role']);

//LogController
$router->get('/logs', [LogController::class, 'indexView'], ['auth', 'role']);

//CarouselController
$router->get('/carousel/gestao', [CarouselController::class, 'gestaoView'], ['auth', 'role']);
$router->post('/carousel/salvar', [CarouselController::class, 'salvar'], ['auth', 'role']);
$router->post('/carousel/ativo', [CarouselController::class, 'ativo'], ['auth', 'role']);
$router->post('/carousel/ordem', [CarouselController::class, 'ordem'], ['auth', 'role']);
$router->post('/carousel/excluir', [CarouselController::class, 'excluir'], ['auth', 'role']);

//OnlineController
$router->get('/online', [OnlineController::class, 'indexView'], ['auth', 'role']);
$router->post('/online/deslogar', [OnlineController::class, 'deslogar'], ['auth', 'role']);

/*----------------------------------- ROTAS POST -----------------------------------*/

$router->post('/login', [AuthController::class, 'login']);

//HelpdeskController
$router->post('/helpdesk/update', [HelpdeskController::class, 'update'], ['auth']);
$router->post('/helpdesk/novo', [HelpdeskController::class, 'store'], ['auth']);
$router->post('/helpdesk/interacao', [HelpdeskController::class, 'interacao'], ['auth']);

//LogController
$router->post('/logs', [LogController::class, 'store'], ['auth']);