<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Core\DB;
use App\Core\Logger;
use App\Model\Consinco\FuncaoPaiModel;
use App\Model\Oracle\FuncionarioModel;
use App\Model\Oracle\FuncaoModel;
use App\Service\LogService;

function inputStr(?string $valor): string
{
    return trim((string) $valor);
}

function inputDate(?string $valor): string
{
    $v = trim((string) $valor);
    return $v === '' ? '1900-01-01' : $v;
}

function insertBatch(\PDO $conn, string $table, array $linhas): int
{
    if (empty($linhas)) {
        return 0;
    }

    $inseridas = 0;
    $chunks = array_chunk($linhas, 500);

    foreach ($chunks as $chunk) {
        $colunas = array_keys($chunk[0]);
        $colSql = '`' . implode('`, `', $colunas) . '`';
        $ph = '(' . implode(', ', array_fill(0, count($colunas), '?')) . ')';
        $sql = "INSERT IGNORE INTO `{$table}` ({$colSql}) VALUES " . implode(', ', array_fill(0, count($chunk), $ph));

        $stmt = $conn->prepare($sql);
        $i = 1;
        foreach ($chunk as $linha) {
            foreach ($colunas as $col) {
                $val = $linha[$col] ?? null;
                $stmt->bindValue($i++, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        $inseridas += $stmt->rowCount();
    }

    return $inseridas;
}

try {
    $conn = DB::connect('mysql');

    echo '[' . date('d-m-Y H:i:s') . "] Lendo fontes..." . PHP_EOL;

    $funcs = FuncionarioModel::listAllFuncs();
    $funcoes = FuncaoModel::listAllFuncoes();
    $pais = FuncaoPaiModel::listarFuncoes();
    $filhas = FuncaoPaiModel::listarFuncoesFilhas();

    echo '[' . date('d-m-Y H:i:s') . '] Funcionarios: ' . count($funcs) . ' | Funcoes: ' . count($funcoes) . ' | FuncoesPai: ' . count($pais) . ' | Relacoes filho: ' . count($filhas) . PHP_EOL;

    $conn->exec('SET SESSION sql_mode = ""');

    foreach (['funcaofilho', 'funcaopai', 'funcao', 'func'] as $tabela) {
        $conn->exec("TRUNCATE TABLE `{$tabela}`");
    }

    $conn->beginTransaction();

    try {
        $linhasFunc = [];
        foreach ($funcs as $r) {
            $linhasFunc[] = [
                'chapa' => inputStr($r['chapa']),
                'nome' => inputStr($r['nome']),
                'codfuncao' => inputStr($r['codfuncao']),
                'codhorario' => inputStr($r['codhorario']),
                'codsecao' => inputStr($r['codsecao']),
                'codpessoa' => inputStr($r['codpessoa']),
                'cpf' => inputStr($r['cpf']),
                'codfilial' => inputStr($r['codfilial']),
                'dtnascimento' => inputDate($r['dtnascimento']),
                'dataadmissao' => inputDate($r['dataadmissao']),
                'codsituacao' => inputStr($r['codsituacao']),
                'nomesocial' => inputStr($r['nomesocial']),
            ];
        }

        $linhasFuncao = [];
        foreach ($funcoes as $r) {
            $linhasFuncao[] = [
                'codigo' => inputStr($r['codigo']),
                'nome' => inputStr($r['nome']),
            ];
        }

        $linhasPai = [];
        foreach ($pais as $r) {
            $linhasPai[] = [
                'codpai' => (int) inputStr($r['codigo']),
                'funcaopai' => inputStr($r['nome']),
            ];
        }

        $linhasFilha = [];
        foreach ($filhas as $r) {
            $linhasFilha[] = [
                'codpai' => inputStr($r['codfuncaopai']),
                'codfuncao' => inputStr($r['codfuncao']),
            ];
        }

        $nFunc = insertBatch($conn, 'func', $linhasFunc);
        $nFuncao = insertBatch($conn, 'funcao', $linhasFuncao);
        $nPai = insertBatch($conn, 'funcaopai', $linhasPai);
        $nFilha = insertBatch($conn, 'funcaofilho', $linhasFilha);

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollBack();
        throw $e;
    }

    $msg = sprintf(
        'Importacao RH concluida | func: %d/%d | funcao: %d/%d | funcaopai: %d/%d | funcaofilho: %d/%d',
        $nFunc, count($funcs),
        $nFuncao, count($funcoes),
        $nPai, count($pais),
        $nFilha, count($filhas)
    );

    try {
        LogService::store([
            'nivel' => 'INFO',
            'tipo' => 'INSERT',
            'modulo' => 'rh',
            'acao' => 'importar_rh',
            'mensagem' => $msg,
            'contexto' => [
                'func' => ['fonte' => count($funcs), 'gravadas' => $nFunc],
                'funcao' => ['fonte' => count($funcoes), 'gravadas' => $nFuncao],
                'funcaopai' => ['fonte' => count($pais), 'gravadas' => $nPai],
                'funcaofilho' => ['fonte' => count($filhas), 'gravadas' => $nFilha],
            ]
        ]);
    } catch (Throwable $e) {
        fwrite(STDERR, 'AVISO: falha ao registrar log: ' . $e->getMessage() . PHP_EOL);
    }

    echo '[' . date('d-m-Y H:i:s') . "] {$msg}" . PHP_EOL;
} catch (Throwable $e) {
    Logger::exception($e);
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}