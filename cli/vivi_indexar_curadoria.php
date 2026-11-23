<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Service\ViviAiService;
use App\Service\ViviKnowledgeService;

$args = array_slice($argv, 1);
$limpar = in_array('--limpar', $args, true);
$reindexar = in_array('--reindexar', $args, true);
$pasta = dirname(__DIR__) . '/resources/vivi';

try {
    if ($limpar) {
        $removidos = ViviKnowledgeService::limparCuradoria();
        echo "Curadoria removida: {$removidos} trecho(s)." . PHP_EOL;
        exit(0);
    }

    $resumo = ViviKnowledgeService::indexarCuradoria($pasta, [
        'reindexar' => $reindexar,
        'onProgress' => static function (int $atual, int $total, string $arquivo, string $status): void {
            printf("[%d/%d] %s (%s)\n", $atual, $total, basename($arquivo), $status);
        },
    ]);

    $modoBusca = ViviAiService::modeloEmbedding() !== ''
        ? 'embeddings + FULLTEXT'
        : 'FULLTEXT (sem modelo de embeddings)';

    printf(
        "[%s] Arquivos: %d | Trechos: %d | Ignorados: %d | Com embedding: %d | Removidos: %d | Erros: %d | Busca: %s\n",
        date('d-m-Y H:i:s'),
        $resumo['arquivos'],
        $resumo['trechos'],
        $resumo['ignorados'],
        $resumo['embeddings'],
        $resumo['removidos'],
        count($resumo['erros']),
        $modoBusca
    );

    foreach ($resumo['erros'] as $erro) {
        echo '  ERRO: ' . $erro . PHP_EOL;
    }

    exit(empty($resumo['erros']) ? 0 : 2);
} catch (Throwable $e) {
    fwrite(STDERR, 'Falha ao indexar a curadoria: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
