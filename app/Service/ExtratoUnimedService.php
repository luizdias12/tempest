<?php

namespace App\Service;

use App\Model\Mysql\ExtratoUnimedModel;
use RuntimeException;

class ExtratoUnimedService
{
    public static function competenciasDisponiveis(): array
    {
        $lista = [];

        foreach (ExtratoUnimedModel::competencias() as $row) {
            $mes = (string) ($row['mes_comp'] ?? '');
            $ano = (string) ($row['ano_comp'] ?? '');

            if (!preg_match('/^(0[1-9]|1[0-2])$/', $mes) || !preg_match('/^(19|20)\d{2}$/', $ano)) {
                continue;
            }

            $lista[] = ['mes' => $mes, 'ano' => $ano];
        }

        return $lista;
    }

    /**
     * Consulta o extrato de cooparticipação do titular logado na competência informada.
     *
     * @return array{titular:string, registros:array, total:float}
     */
    public static function consultar(string $mes, string $ano): array
    {
        if (!preg_match('/^(0[1-9]|1[0-2])$/', $mes)) {
            throw new RuntimeException('Mês de competência inválido.');
        }

        if (!preg_match('/^(19|20)\d{2}$/', $ano)) {
            throw new RuntimeException('Ano de competência inválido.');
        }

        $titular = (string) (AuthService::getUserName() ?? '');
        $titularNormalizado = self::normalizar($titular);

        if ($titularNormalizado === '') {
            return ['titular' => '', 'registros' => [], 'total' => 0.0];
        }

        $registros = [];
        $total = 0.0;

        foreach (ExtratoUnimedModel::porCompetencia($mes, $ano) as $row) {
            if (self::normalizar((string) ($row['nome_titular'] ?? '')) !== $titularNormalizado) {
                continue;
            }

            $valor = (float) ($row['valor'] ?? 0);
            $total += $valor;

            $registros[] = [
                'nome_cliente' => (string) ($row['nome_cliente'] ?? ''),
                'nome_prestador' => (string) ($row['nome_prestador'] ?? ''),
                'data_atendimento' => (string) ($row['data_atendimento'] ?? ''),
                'tipo_procedimento' => (string) ($row['tipo_procedimento'] ?? ''),
                'quantidade' => (string) ($row['quantidade'] ?? ''),
                'valor' => round($valor, 2),
            ];
        }

        return [
            'titular' => $titular,
            'registros' => $registros,
            'total' => round($total, 2),
        ];
    }

    private static function normalizar(string $valor): string
    {
        $valor = strtoupper(removeAccents($valor));

        return trim((string) preg_replace('/\s+/', ' ', $valor));
    }
}
