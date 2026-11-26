<?php

namespace App\Model\Mysql;

use App\Core\DB;
use Throwable;

class ImportacaoUnimedModel
{
    private const TABELA = 'tbl_extrato_unimed';

    public static function contarCompetencia(string $mes, string $ano): int
    {
        $total = DB::first(
            'SELECT COUNT(*) AS total FROM ' . self::TABELA . ' WHERE mes_comp = ? AND ano_comp = ?',
            [$mes, $ano],
            'mysql'
        );

        return (int) ($total['total'] ?? 0);
    }

    /**
     * Insere os registros válidos em transação e devolve o resultado por linha.
     *
     * @param array<int, array{linha:int, dados:array<string, mixed>}> $registros
     * @return array{inseridos:int, ignorados:int, erros:array<int, array{linha:int, motivo:string}>}
     */
    public static function inserirLote(array $registros): array
    {
        $resultado = ['inseridos' => 0, 'ignorados' => 0, 'erros' => []];

        if (empty($registros)) {
            return $resultado;
        }

        $pdo = DB::connect('mysql');

        $sql = 'INSERT IGNORE INTO ' . self::TABELA . ' (
            codigo_cliente, cpf_cliente, codigo_numero_auxliar, nome_cliente,
            cod_prestador, nome_prestador, data_atendimento, data_inicio_internacao,
            data_final_internacao, cod_procedimento, tipo_procedimento, quantidade,
            valor, nome_titular, cod_matricula, data_emissao_titulo, mes_comp, ano_comp
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $stmt = $pdo->prepare($sql);
        $pdo->beginTransaction();

        try {
            foreach ($registros as $registro) {
                $d = $registro['dados'];

                try {
                    $stmt->execute([
                        $d['codigo_cliente'],
                        $d['cpf_cliente'],
                        $d['codigo_numero_auxliar'],
                        $d['nome_cliente'],
                        $d['cod_prestador'],
                        $d['nome_prestador'],
                        $d['data_atendimento'],
                        $d['data_inicio_internacao'],
                        $d['data_final_internacao'],
                        $d['cod_procedimento'],
                        $d['tipo_procedimento'],
                        $d['quantidade'],
                        $d['valor'],
                        $d['nome_titular'],
                        $d['cod_matricula'],
                        $d['data_emissao_titulo'],
                        $d['mes_comp'],
                        $d['ano_comp'],
                    ]);
                } catch (Throwable $e) {
                    $resultado['erros'][] = [
                        'linha' => $registro['linha'],
                        'motivo' => 'Falha ao gravar no banco: ' . $e->getMessage(),
                    ];
                    continue;
                }

                if ($stmt->rowCount() > 0) {
                    $resultado['inseridos']++;
                } else {
                    $resultado['ignorados']++;
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $resultado;
    }
}
