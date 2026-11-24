<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Core\DB;

$aplicar = in_array('--aplicar', array_slice($argv, 1), true);

$origem = basePath('public/files/chat');
$destino = basePath('storage/chat');

$rows = DB::select(
    "SELECT id, anexo FROM chat_mensagens WHERE anexo LIKE '/files/chat/%' ORDER BY id",
    [],
    'mysql'
);

if (!$rows) {
    echo 'Nada a migrar: nenhuma mensagem aponta para public/files/chat.' . PHP_EOL;
    exit(0);
}

if ($aplicar && !is_dir($destino) && !mkdir($destino, 0775, true)) {
    fwrite(STDERR, "Não foi possível criar {$destino}" . PHP_EOL);
    exit(1);
}

$mover = 0;
$atualizar = 0;
$ignorar = 0;

foreach ($rows as $row) {
    $nome = basename((string) $row['anexo']);
    $id = (int) $row['id'];

    if (!preg_match('/^[a-f0-9]{22}\.[a-z0-9]{1,10}$/i', $nome)) {
        fwrite(STDERR, "[msg {$id}] nome fora do padrão, ignorado: {$nome}" . PHP_EOL);
        $ignorar++;
        continue;
    }

    $arquivoOrigem = $origem . DIRECTORY_SEPARATOR . $nome;
    $arquivoDestino = $destino . DIRECTORY_SEPARATOR . $nome;
    $novoValor = 'chat/' . $nome;

    if (!is_file($arquivoOrigem)) {
        if (is_file($arquivoDestino)) {
            echo "[msg {$id}] arquivo já migrado; atualizando referência para {$novoValor}" . PHP_EOL;

            if ($aplicar) {
                DB::update('chat_mensagens', 'id', $id, ['anexo' => $novoValor], 'mysql');
            }

            $atualizar++;
            continue;
        }

        fwrite(STDERR, "[msg {$id}] arquivo não encontrado em public nem em storage, ignorado: {$nome}" . PHP_EOL);
        $ignorar++;
        continue;
    }

    echo "[msg {$id}] mover {$nome} e atualizar referência para {$novoValor}" . PHP_EOL;
    $mover++;

    if (!$aplicar) {
        continue;
    }

    if (!@rename($arquivoOrigem, $arquivoDestino)) {
        fwrite(STDERR, "[msg {$id}] falha ao mover {$nome}; referência mantida" . PHP_EOL);
        $ignorar++;
        $mover--;
        continue;
    }

    DB::update('chat_mensagens', 'id', $id, ['anexo' => $novoValor], 'mysql');
    $atualizar++;
}

echo PHP_EOL . ($aplicar ? 'Migração aplicada.' : 'Simulação (use --aplicar para executar).') . PHP_EOL;
echo "Arquivos a mover: {$mover} | referências atualizadas: {$atualizar} | ignorados: {$ignorar}" . PHP_EOL;
