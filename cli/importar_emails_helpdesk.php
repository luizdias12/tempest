<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Core\Logger;
use App\Service\EmailHelpdeskService;

try {
    $resumo = EmailHelpdeskService::importarEmails();

    $msg = sprintf(
        '[%s] Total: %d | Importados: %d | Respostas: %d | Ignorados: %d | Erros: %d',
        date('d-m-Y H:i:s'),
        $resumo['total'] ?? 0,
        $resumo['importados'],
        $resumo['respostas'],
        $resumo['ignorados'],
        count($resumo['erros'])
    );

    echo $msg . PHP_EOL;

    foreach ($resumo['erros'] as $e) {
        echo '  ERRO: ' . $e['subject'] . ' - ' . $e['erro'] . PHP_EOL;
    }
} catch (Throwable $e) {
    Logger::exception($e);
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
