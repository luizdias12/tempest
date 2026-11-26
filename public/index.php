<?php
date_default_timezone_set('America/Sao_Paulo');

$https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
    || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);

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
