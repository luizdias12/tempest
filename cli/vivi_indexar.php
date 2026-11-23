<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Core\Logger;
use App\Service\ViviAiService;
use App\Service\ViviKnowledgeService;

$args = array_slice($argv, 1);
$limpar = in_array('--limpar', $args, true);
$publico = in_array('--publico', $args, true);
$reindexar = in_array('--reindexar', $args, true);
$caminhoInformado = '';

foreach ($args as $arg) {
    if (!str_starts_with($arg, '--')) {
        $caminhoInformado = $arg;
        break;
    }
}

if (!$limpar && $caminhoInformado === '') {
    fwrite(STDERR, "Uso: php cli/vivi_indexar.php <arquivo-ou-pasta .md/.txt/.pdf/.docx> [--publico] [--reindexar]\n");
    fwrite(STDERR, "     php cli/vivi_indexar.php --limpar\n");
    exit(1);
}

try {
    if ($limpar) {
        $removidos = ViviKnowledgeService::limpar();
        echo "Base de conhecimento limpa: {$removidos} trecho(s) removido(s)." . PHP_EOL;

        if ($caminhoInformado === '') {
            exit(0);
        }
    }

    $caminho = str_starts_with($caminhoInformado, '/')
        ? $caminhoInformado
        : basePath($caminhoInformado);

    $resumo = ViviKnowledgeService::indexar($caminho, [
        'publico' => $publico,
        'reindexar' => $reindexar,
    ]);

    $modoBusca = ViviAiService::modeloEmbedding() !== ''
        ? 'embeddings + FULLTEXT'
        : 'FULLTEXT (sem modelo de embeddings)';

    printf(
        "[%s] Arquivos: %d | Trechos: %d | Ignorados: %d | Com embedding: %d | Erros: %d | Busca: %s%s\n",
        date('d-m-Y H:i:s'),
        $resumo['arquivos'],
        $resumo['trechos'],
        $resumo['ignorados'],
        $resumo['embeddings'],
        count($resumo['erros']),
        $modoBusca,
        $publico ? ' | público: sim' : ''
    );

    foreach ($resumo['erros'] as $erro) {
        echo '  ERRO: ' . $erro . PHP_EOL;
    }

    if (
        ViviAiService::modeloEmbedding() !== ''
        && $resumo['trechos'] > 0
        && $resumo['embeddings'] === 0
    ) {
        echo '  AVISO: nenhum embedding foi gerado; a busca usará FULLTEXT.' . PHP_EOL;
    }

    exit(empty($resumo['erros']) ? 0 : 2);
} catch (Throwable $e) {
    Logger::exception($e, ['cli' => 'vivi_indexar']);
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
