<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Model\Mysql\HelpdeskEmailModel;

try {
    HelpdeskEmailModel::criarTabelaSeNecessario();
    echo 'Tabela helpdesk_email_importado criada/atualizada com sucesso.' . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}