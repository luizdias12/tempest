<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Service\ViviAiService;
use App\Service\ViviKnowledgeService;

$args = array_slice($argv, 1);
$pergunta = '';
$publico = false;
$pai = null;
$limite = 5;

foreach ($args as $arg) {
    if ($arg === '--publico') {
        $publico = true;
        continue;
    }

    if (str_starts_with($arg, '--pai=')) {
        $pai = (int) substr($arg, 6);
        continue;
    }

    if (str_starts_with($arg, '--limite=')) {
        $limite = max(1, min(20, (int) substr($arg, 9)));
        continue;
    }

    if (!str_starts_with($arg, '--') && $pergunta === '') {
        $pergunta = $arg;
    }
}

if (trim($pergunta) === '') {
    fwrite(STDERR, "Uso: php cli/vivi_verificar_busca.php \"pergunta\" [--publico] [--pai=NN] [--limite=5]\n");
    fwrite(STDERR, "  --publico    simula visitante não autenticado\n");
    fwrite(STDERR, "  --pai=NN     simula usuário autenticado com essa função pai\n");
    exit(1);
}

try {
    $resultados = ViviKnowledgeService::recuperar($pergunta, $publico, $limite, $pai);
} catch (Throwable $e) {
    fwrite(STDERR, 'Falha na busca: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$modoBusca = ViviAiService::modeloEmbedding() !== '' ? 'embeddings + FULLTEXT' : 'FULLTEXT';

echo 'Pergunta: ' . $pergunta . PHP_EOL;
echo 'Filtro: ', $publico ? 'somente público' : 'autenticado', PHP_EOL;
echo 'Função pai: ', $pai === null ? '(nenhuma)' : (string) $pai, PHP_EOL;
echo 'Busca: ', $modoBusca, ' | Resultados: ', count($resultados), PHP_EOL, PHP_EOL;

foreach ($resultados as $indice => $trecho) {
    printf(
        "%d) %s | score=%.2f\n",
        $indice + 1,
        (string) ($trecho['fonte'] ?? ''),
        (float) ($trecho['score'] ?? 0)
    );
    echo '   Título: ', trim((string) ($trecho['titulo'] ?? '')), PHP_EOL;
    echo '   ', mb_substr((string) preg_replace('/\s+/', ' ', (string) ($trecho['conteudo'] ?? '')), 0, 300), PHP_EOL, PHP_EOL;
}

exit(0);
