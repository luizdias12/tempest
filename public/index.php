<?php
date_default_timezone_set('America/Sao_Paulo');

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$env = $_ENV['APP_ENV'] ?? 'production';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($env === 'maintenance' && $requestUri !== '/manutencao') {
    redirect('/manutencao');
}

if ($env === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else if ($env === 'production' && $requestUri === '/manutencao'){
    ini_set('display_errors', 0);
    error_reporting(0);
    redirect('/');
}

use App\Core\Router;

$router = new Router();

require basePath('routes/web.php');
require basePath('routes/api.php');

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
