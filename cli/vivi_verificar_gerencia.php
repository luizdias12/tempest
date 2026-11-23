<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Service\ViviDadosService;

$args = array_slice($argv, 1);
$pergunta = '';
$anterior = null;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--anterior=')) {
        $anterior = substr($arg, 11);
        continue;
    }

    if (!str_starts_with($arg, '--') && $pergunta === '') {
        $pergunta = $arg;
    }
}

if (trim($pergunta) === '') {
    fwrite(STDERR, "Uso: php cli/vivi_verificar_gerencia.php \"pergunta\" [--anterior=\"pergunta anterior\"]\n");
    fwrite(STDERR, "  --anterior   simula uma conversa em que a pergunta atual é um acompanhamento\n");
    exit(1);
}

$historico = $anterior === null ? [] : [['role' => 'user', 'content' => $anterior]];

try {
    $fatos = ViviDadosService::fatosGerencia($pergunta, $historico);
} catch (Throwable $e) {
    fwrite(STDERR, 'Falha na consulta: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo 'Pergunta: ' . $pergunta . PHP_EOL;

if ($anterior !== null) {
    echo 'Pergunta anterior: ' . $anterior . PHP_EOL;
}

echo PHP_EOL;

if ($fatos === null) {
    echo 'Nenhuma consulta de gerência foi disparada para esta mensagem.' . PHP_EOL;
    exit(0);
}

echo $fatos . PHP_EOL;
exit(0);
