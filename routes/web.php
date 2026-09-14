<?php

/** @var $router App\Core\Router */

use App\Controller\AuthController;
use App\Controller\CarouselController;
use App\Controller\ContatoController;
use App\Controller\DocController;
use App\Controller\ErrorController;
use App\Controller\FinancController;
use App\Controller\FuncionarioController;
use App\Controller\HelpdeskController;
use App\Controller\HomeController;
use App\Controller\LogController;
use App\Controller\OnlineController;
use App\Controller\RegionalController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GestaoRoleMiddleware;
use App\Middleware\RoleMiddleware;

//Middlewares
$router->aliasMiddleware('auth', AuthMiddleware::class);
$router->aliasMiddleware('gestaoRole', GestaoRoleMiddleware::class);
$router->aliasMiddleware('role', RoleMiddleware::class);

/*----------------------------------- ROTAS PÚBLICAS -----------------------------------*/

//HomeController
$router->get('/', [HomeController::class, 'indexView']);

//ContatoController
$router->get('/contatos', [ContatoController::class, 'indexView']);

//ErrorController
$router->get('/error', [ErrorController::class, 'indexView']);

//AuthController
$router->get('/login', [AuthController::class, 'loginView']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/login', [AuthController::class, 'login']);

//Cadastro e redefinição de senha (públicos por design)
$router->get('/login/cadastro', [AuthController::class, 'cadastroView']);
$router->post('/login/cadastro', [AuthController::class, 'cadastro']);
$router->get('/login/redefinir', [AuthController::class, 'redefinirView']);
$router->post('/login/redefinir', [AuthController::class, 'redefinir']);

//FuncionarioController (público por design)
$router->get('/funcionarios/aniversariantes', [FuncionarioController::class, 'aniversariantesView']);

//Rotas de teste
$router->get('/ti/sessao', [FuncionarioController::class, 'sessaoView']);

/*----------------------------------- GRUPO /financ (auth) -----------------------------------*/

$router->group('/financ', function ($router) {
    $router->get('/holerite', [FinancController::class, 'holeriteView']);
    $router->get('/holerite/pdf', [FinancController::class, 'holeritePdf']);
}, ['auth']);

/*----------------------------------- GRUPO /funcionarios (auth) -----------------------------------*/

$router->group('/funcionarios', function ($router) {
    $router->get('/index', [FuncionarioController::class, 'indexView'], ['role']);
    $router->get('/admissoes', [FuncionarioController::class, 'admissoesView']);
    $router->get('/admissoes/json', [FuncionarioController::class, 'admissoesJson']);
}, ['auth']);

/*----------------------------------- GRUPO /ti (auth) -----------------------------------*/

$router->group('/ti', function ($router) {
    $router->get('/lista', [FuncionarioController::class, 'listaView']);
    $router->get('/lista/download', [FuncionarioController::class, 'listaDownload']);
}, ['auth']);

/*----------------------------------- GRUPO /helpdesk (auth) -----------------------------------*/

$router->group('/helpdesk', function ($router) {
    $router->get('/index', [HelpdeskController::class, 'indexView']);
    $router->get('/historico/{id}', [HelpdeskController::class, 'historicoJson']);
    $router->post('/update', [HelpdeskController::class, 'update']);
    $router->post('/novo', [HelpdeskController::class, 'store']);
    $router->post('/interacao', [HelpdeskController::class, 'interacao']);
    $router->post('/importar-emails', [HelpdeskController::class, 'importarEmails'], ['role']);
}, ['auth']);

/*----------------------------------- GRUPO /documentos (auth) -----------------------------------*/

$router->group('/documentos', function ($router) {
    $router->get('/', [DocController::class, 'indexView']);
    $router->get('/abrir/{id}', [DocController::class, 'abrir']);
    $router->get('/visualizar/{id}', [DocController::class, 'visualizar']);

    //Ações de gestão (auth + gestaoRole)
    $router->get('/gestao', [DocController::class, 'gestaoView'], ['gestaoRole']);
    $router->post('/upload', [DocController::class, 'upload'], ['gestaoRole']);
    $router->post('/diretorio', [DocController::class, 'diretorio'], ['gestaoRole']);
    $router->post('/subdiretorio', [DocController::class, 'subdiretorio'], ['gestaoRole']);
    $router->post('/excluir-diretorio', [DocController::class, 'excluirDiretorio'], ['gestaoRole']);
    $router->post('/excluir-subdiretorio', [DocController::class, 'excluirSubdiretorio'], ['gestaoRole']);
    $router->post('/permissao/adicionar', [DocController::class, 'adicionarPermissao'], ['gestaoRole']);
    $router->post('/permissao/remover', [DocController::class, 'removerPermissao'], ['gestaoRole']);
    $router->post('/geral', [DocController::class, 'geral'], ['gestaoRole']);
    $router->post('/copiar-permissoes', [DocController::class, 'copiarPermissoes'], ['gestaoRole']);
    $router->post('/nova-versao', [DocController::class, 'novaVersao'], ['gestaoRole']);
    $router->post('/versao', [DocController::class, 'versao'], ['gestaoRole']);
    $router->post('/versao/excluir', [DocController::class, 'excluirVersao'], ['gestaoRole']);
    $router->post('/excluir-documento', [DocController::class, 'excluirDocumento'], ['gestaoRole']);
}, ['auth']);

/*----------------------------------- GRUPO /logs (auth) -----------------------------------*/

$router->group('/logs', function ($router) {
    $router->get('/', [LogController::class, 'indexView'], ['role']);
    $router->post('/', [LogController::class, 'store']);
}, ['auth']);

/*----------------------------------- GRUPO /carousel (auth) -----------------------------------*/

$router->group('/carousel', function ($router) {
    $router->get('/gestao', [CarouselController::class, 'gestaoView']);
    $router->post('/salvar', [CarouselController::class, 'salvar']);
    $router->post('/ativo', [CarouselController::class, 'ativo']);
    $router->post('/ordem', [CarouselController::class, 'ordem']);
    $router->post('/excluir', [CarouselController::class, 'excluir']);
}, ['auth']);

/*----------------------------------- GRUPO /online (auth) -----------------------------------*/

$router->group('/online', function ($router) {
    $router->get('/', [OnlineController::class, 'indexView'], ['role']);
    $router->post('/deslogar', [OnlineController::class, 'deslogar'], ['role']);
}, ['auth']);

/*----------------------------------- GRUPO /regional (auth) -----------------------------------*/

$router->group('/regional', function ($router) {
    $router->get('/index', [RegionalController::class, 'indexView']);
    $router->get('/consulta', [RegionalController::class, 'consulta']);
    $router->get('/listaRegional', [RegionalController::class, 'listaRegional']);
    $router->get('/byRegional/{regiao}', [RegionalController::class, 'byRegional']);
    $router->get('/filialByRegional', [RegionalController::class, 'filialByRegional']);
    $router->get('/byFilial/{filial}', [RegionalController::class, 'byFilial']);
    $router->get('/onlyGerente', [RegionalController::class, 'onlyGerente']);
    $router->get('/onlysubGerente', [RegionalController::class, 'onlysubGerente']);
    $router->get('/listaGerencia', [RegionalController::class, 'listaGerencia']);
    $router->get('/regionalFilial', [RegionalController::class, 'regionalFilial']);
    $router->get('/usuario/{cpf}', [RegionalController::class, 'usuario']);

    //Ações de escrita (auth + role)
    $router->post('/gravaGerente', [RegionalController::class, 'gravaGerente'], ['role']);
    $router->put('/updateRegional/{id}', [RegionalController::class, 'updateRegional'], ['role']);
    $router->put('/updatefilialReg/{filial}', [RegionalController::class, 'updatefilialReg'], ['role']);
    $router->put('/alteraRegional/{regiao}', [RegionalController::class, 'alteraRegional'], ['role']);
    $router->post('/criaRegional', [RegionalController::class, 'criaRegional'], ['role']);
    $router->delete('/excluiRegional/{regiao}', [RegionalController::class, 'excluiRegional'], ['role']);
    $router->post('/vinculaFilial', [RegionalController::class, 'vinculaFilial'], ['role']);
    $router->delete('/desvinculaFilial/{id}', [RegionalController::class, 'desvinculaFilial'], ['role']);
    $router->delete('/deletaGerente/{id}', [RegionalController::class, 'deletaGerente'], ['role']);
}, ['auth']);