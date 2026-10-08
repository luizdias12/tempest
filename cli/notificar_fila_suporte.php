<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Core\Logger;
use App\Service\HelpdeskService;

$horasUteis = 8;
$limite = 50;
$dryRun = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
    }

    if (str_starts_with($arg, '--horas=')) {
        $horasUteis = max(1, (int) substr($arg, 8));
    }

    if (str_starts_with($arg, '--limite=')) {
        $limite = max(1, (int) substr($arg, 9));
    }
}

try {
    $resumo = HelpdeskService::notificarFilaSuporte($horasUteis, $limite, !$dryRun);

    if ($dryRun) {
        echo '[' . date('d-m-Y H:i:s') . "] [DRY-RUN] {$resumo['chamados']} chamado(s) elegivel(is), "
            . "{$resumo['ignorados']} abaixo do limite de {$horasUteis}h uteis. Nenhum e-mail enviado." . PHP_EOL;
        exit(0);
    }

    echo '[' . date('d-m-Y H:i:s') . "] {$resumo['emails_ok']} e-mail(s) enviado(s), {$resumo['emails_erro']} falha(s) — "
        . "{$resumo['chamados']} chamado(s) na fila ({$resumo['ignorados']} abaixo do limite de {$horasUteis}h uteis)." . PHP_EOL;
} catch (Throwable $e) {
    Logger::exception($e);
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
