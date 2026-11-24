<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Core\DB;
use App\Core\Logger;

$args = array_slice($argv, 1);
$aplicar = in_array('--aplicar', $args, true);
$dias = 7;
$lote = 5000;

foreach ($args as $arg) {
    if (preg_match('/^--dias=(\d+)$/', $arg, $m)) {
        $dias = (int) $m[1];
    } elseif (preg_match('/^--lote=(\d+)$/', $arg, $m)) {
        $lote = (int) $m[1];
    } elseif ($arg !== '--aplicar') {
        fwrite(STDERR, "Uso: php cli/chat_reter_eventos.php [--dias=7] [--lote=5000] [--aplicar]\n");
        exit(1);
    }
}

$dias = max(1, $dias);
$lote = max(100, $lote);

try {
    $conn = DB::connect('mysql');

    $eventosAntigos = (int) (DB::first(
        "SELECT COUNT(*) AS total FROM chat_eventos WHERE criado_em < (NOW() - INTERVAL {$dias} DAY)",
        [],
        'mysql'
    )['total'] ?? 0);

    $digitandoEventos = (int) (DB::first(
        "SELECT COUNT(*) AS total FROM chat_eventos WHERE evento = 'digitando'",
        [],
        'mysql'
    )['total'] ?? 0);

    $digitandoLinhas = (int) (DB::first(
        'SELECT COUNT(*) AS total FROM chat_digitando WHERE atualizado_em < (NOW() - INTERVAL 1 MINUTE)',
        [],
        'mysql'
    )['total'] ?? 0);

    printf(
        "[%s] Retenção de eventos: manter os últimos %d dia(s).\n",
        date('d-m-Y H:i:s'),
        $dias
    );
    printf(
        "  Eventos antigos: %d | Eventos 'digitando' legados: %d | Cache 'digitando' expirado: %d\n",
        $eventosAntigos,
        $digitandoEventos,
        $digitandoLinhas
    );

    if (!$aplicar) {
        echo '  MODO SIMULAÇÃO: nada foi apagado. Use --aplicar para executar.' . PHP_EOL;
        exit(0);
    }

    $totalEventos = 0;
    $totalLegados = 0;
    $totalDigitando = 0;

    // "Digitando" não é mais consumido como evento; limpa tudo de uma vez.
    $stmt = $conn->prepare("DELETE FROM chat_eventos WHERE evento = 'digitando' LIMIT {$lote}");

    do {
        $stmt->execute();
        $n = $stmt->rowCount();
        $totalLegados += $n;
    } while ($n === $lote);

    // Eventos antigos em lotes para não segurar lock por muito tempo.
    $stmt = $conn->prepare("DELETE FROM chat_eventos WHERE criado_em < (NOW() - INTERVAL {$dias} DAY) LIMIT {$lote}");

    do {
        $stmt->execute();
        $n = $stmt->rowCount();
        $totalEventos += $n;
    } while ($n === $lote);

    $stmt = $conn->prepare('DELETE FROM chat_digitando WHERE atualizado_em < (NOW() - INTERVAL 1 MINUTE)');
    $stmt->execute();
    $totalDigitando = $stmt->rowCount();

    printf(
        "  Removidos: %d evento(s) antigo(s), %d 'digitando' legado(s), %d cache(s) expirado(s).\n",
        $totalEventos,
        $totalLegados,
        $totalDigitando
    );

    exit(0);
} catch (Throwable $e) {
    Logger::exception($e, ['cli' => 'chat_reter_eventos']);
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
