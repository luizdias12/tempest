<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Core\DB;
use App\Core\Logger;
use App\Service\LogService;

$limiteMin = null;
$dryRun = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
    }

    if (str_starts_with($arg, '--min=')) {
        $limiteMin = (int) substr($arg, 6);
    }
}

$limiteMin = $limiteMin ?: (int) ($_ENV['SESSION_INATIVIDADE_MIN'] ?? 480);

try {
    $conn = DB::connect('mysql');

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM online
        WHERE status = '1'
        AND dt_login < (NOW() - INTERVAL :min MINUTE)
    ");
    $stmt->bindValue(':min', max(1, $limiteMin), \PDO::PARAM_INT);
    $stmt->execute();
    $total = (int) $stmt->fetchColumn();

    if ($dryRun) {
        echo '[' . date('d-m-Y H:i:s') . "] [DRY-RUN] {$total} sesao(oes) expiravel(eis) (limite: {$limiteMin} min)." . PHP_EOL;
        exit(0);
    }

    if ($total === 0) {
        echo '[' . date('d-m-Y H:i:s') . "] Nenhuma sessao inativa (limite: {$limiteMin} min)." . PHP_EOL;
        exit(0);
    }

    $stmt = $conn->prepare("
        UPDATE online
        SET status = '2', dt_logoff = NOW()
        WHERE status = '1'
        AND dt_login < (NOW() - INTERVAL :min MINUTE)
    ");
    $stmt->bindValue(':min', max(1, $limiteMin), \PDO::PARAM_INT);
    $stmt->execute();
    $expirou = $stmt->rowCount();

    LogService::store([
        'nivel' => 'INFO',
        'tipo' => 'AUTO',
        'modulo' => 'online',
        'acao' => 'expirar_sessoes_inativas',
        'usuario_id' => null,
        'mensagem' => "{$expirou} sesao(oes) inativa(s) ha mais de {$limiteMin} min marcada(s) como encerrada(s)",
    ]);

    echo '[' . date('d-m-Y H:i:s') . "] {$expirou} sesao(oes) expirada(s) (limite: {$limiteMin} min)." . PHP_EOL;
} catch (Throwable $e) {
    Logger::exception($e);
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}