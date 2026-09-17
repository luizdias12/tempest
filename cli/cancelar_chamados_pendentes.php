<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Core\DB;
use App\Core\Logger;
use App\Service\GenericService;
use App\Service\HelpdeskService;
use App\Service\HelpHistoricoService;
use App\Service\LogService;

try {
    $pendentes = HelpdeskService::chamadosPendentesUsuario() ?? [];
    $cancelados = 0;

    foreach ($pendentes as $pendente) {
        $dataPend = $pendente['data_status'] ?? $pendente['data_hist'] ?? '';

        if (empty($dataPend) || businessHoursBetween($dataPend) < 48) {
            continue;
        }

        if (HelpdeskService::obterStatus($pendente['id']) !== 'PU') {
            continue;
        }

        $conn = DB::connect('mysql');
        $conn->beginTransaction();

        try {
            $stmt = $conn->prepare("
                UPDATE helpdesk
                SET status = 'C', dt_solucao = NOW()
                WHERE id = :id AND status = 'PU'
            ");
            $stmt->execute(['id' => $pendente['id']]);

            if ($stmt->rowCount() === 0) {
                $conn->rollBack();
                continue;
            }

            HelpdeskService::upsertHelpStatus($pendente['id'], 'C');
            HelpdeskService::registrarCancelamento($pendente['id'], 1, '0', 'CLI', 'A');
            HelpHistoricoService::registrarInteracao(
                $pendente['id'],
                '[AUTOMATICO] ' . GenericService::obterTextoCancelamento(1),
                '0',
                'C'
            );
            LogService::store([
                'nivel' => 'INFO',
                'tipo' => 'UPDATE',
                'modulo' => 'helpdesk',
                'acao' => 'cancelar_chamado_automatico',
                'mensagem' => "chamado {$pendente['id']} cancelado por metodo automatico",
                'contexto' => [
                    'id' => $pendente['id'],
                    'acao' => 'automatica'
                ]
            ]);

            $conn->commit();
            $cancelados++;
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    echo '[' . date('d-m-Y H:i:s') . "] {$cancelados} chamado(s) cancelado(s)." . PHP_EOL;
} catch (Throwable $e) {
    Logger::exception($e);
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}