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
$reindexar = in_array('--reindexar', $args, true);
$apenasGeral = in_array('--apenas-geral', $args, true);
$limite = null;

foreach ($args as $indice => $arg) {
    if (preg_match('/^--limite=(\d+)$/', $arg, $partes)) {
        $limite = (int) $partes[1];
        continue;
    }

    if ($arg === '--limite' && isset($args[$indice + 1]) && ctype_digit($args[$indice + 1])) {
        $limite = (int) $args[$indice + 1];
    }
}

try {
    if ($limpar) {
        $removidos = ViviKnowledgeService::limparDocumentosIntranet();
        echo "Base de documentos da intranet limpa: {$removidos} trecho(s) removido(s)." . PHP_EOL;
        exit(0);
    }

    $inicio = microtime(true);

    $resumo = ViviKnowledgeService::indexarDocumentosIntranet([
        'reindexar' => $reindexar,
        'apenas_gerais' => $apenasGeral,
        'limite' => $limite,
        'onProgress' => static function (int $atual, int $total, array $doc, string $status): void {
            printf(
                "[%4d/%4d] %-8s #%d %s\n",
                $atual,
                $total,
                $status,
                (int) ($doc['id_doc'] ?? 0),
                mb_substr((string) ($doc['titulo'] ?? ''), 0, 90)
            );
        },
    ]);

    $modoBusca = ViviAiService::modeloEmbedding() !== ''
        ? 'embeddings + FULLTEXT'
        : 'FULLTEXT (sem modelo de embeddings)';

    printf(
        "[%s] Documentos: %d | Indexados: %d | Ignorados: %d | Sem texto: %d | Trechos: %d | Com embedding: %d | Removidos: %d | Erros: %d | Busca: %s%s\n",
        date('d-m-Y H:i:s'),
        $resumo['documentos'],
        $resumo['indexados'],
        $resumo['ignorados'],
        $resumo['sem_texto'],
        $resumo['trechos'],
        $resumo['embeddings'],
        $resumo['removidos'],
        count($resumo['erros']),
        $modoBusca,
        $apenasGeral ? ' | somente documentos gerais' : ''
    );

    printf("Tempo: %.1f s\n", microtime(true) - $inicio);

    if (!empty($resumo['erros'])) {
        foreach ($resumo['erros'] as $erro) {
            echo '  ERRO: ' . $erro . PHP_EOL;
        }
    }

    exit(empty($resumo['erros']) ? 0 : 2);
} catch (Throwable $e) {
    Logger::exception($e, ['cli' => 'vivi_indexar_intranet']);
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
