<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Core\DB;

try {
    $pdo = DB::connect('mysql');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS helpdesk_email_importado (
            message_id VARCHAR(200) NOT NULL PRIMARY KEY,
            help_id INT NOT NULL,
            importado_em DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo 'Tabela helpdesk_email_importado criada/verificada com sucesso.' . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
