<?php

namespace App\Service;

use App\Model\Mysql\ImportacaoUnimedModel;
use DateTime;
use RuntimeException;

class ImportacaoUnimedService
{
    private const LIMITE_BYTES = 5 * 1024 * 1024;

    /** Ordem dos campos no layout 176 da Unimed (separador ";"). */
    private const CAMPOS_LAYOUT = [
        0 => 'Código do Cliente',
        1 => 'CPF do cliente',
        2 => 'Código Número Auxiliar',
        3 => 'Nome Cliente',
        4 => 'Cod. Prestador/Unimed',
        5 => 'Nome Prestador/Unimed',
        6 => 'Data atendimento',
        7 => 'Início Internação',
        8 => 'Fim Internação',
        9 => 'Cód. Proced/Tipo da Produção/Filme',
        10 => 'Tipo de Procedimento',
        11 => 'Quantidade',
        12 => 'Valor',
        13 => 'Nome Titular',
        14 => 'Código Matrícula',
        15 => 'Data de emissão do título',
    ];

    public static function camposLayout(): array
    {
        return self::CAMPOS_LAYOUT;
    }

    /**
     * Importa o arquivo de extrato de cooparticipação da Unimed (layout 176).
     *
     * @param array $arquivo Item de $_FILES
     * @return array Resumo da importação
     */
    public static function importar(array $arquivo, string $mes, string $ano): array
    {
        self::validarCompetencia($mes, $ano);
        self::validarArquivo($arquivo);

        $jaExistem = ImportacaoUnimedModel::contarCompetencia($mes, $ano);

        $registros = [];
        $erros = [];
        $linhas = 0;

        $handle = fopen((string) $arquivo['tmp_name'], 'r');

        if ($handle === false) {
            throw new RuntimeException('Não foi possível ler o arquivo enviado.');
        }

        try {
            while (($linha = fgets($handle)) !== false) {
                $linhas++;
                $linha = trim(self::paraUtf8($linha));

                if ($linha === '') {
                    continue;
                }

                try {
                    $registros[] = [
                        'linha' => $linhas,
                        'dados' => self::interpretarLinha($linha, $mes, $ano),
                    ];
                } catch (RuntimeException $e) {
                    $erros[] = ['linha' => $linhas, 'motivo' => $e->getMessage()];
                }
            }
        } finally {
            fclose($handle);
        }

        $resultado = ImportacaoUnimedModel::inserirLote($registros);

        return [
            'mes' => $mes,
            'ano' => $ano,
            'linhas' => $linhas,
            'validas' => count($registros),
            'inseridos' => $resultado['inseridos'],
            'ignorados' => $resultado['ignorados'],
            'ja_existiam' => $jaExistem,
            'erros' => array_merge($erros, $resultado['erros']),
        ];
    }

    private static function validarCompetencia(string $mes, string $ano): void
    {
        if (!preg_match('/^(0[1-9]|1[0-2])$/', $mes)) {
            throw new RuntimeException('Informe o mês de competência.');
        }

        if (!preg_match('/^(19|20)\d{2}$/', $ano)) {
            throw new RuntimeException('Informe o ano de competência.');
        }
    }

    private static function validarArquivo(array $arquivo): void
    {
        $error = (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Selecione o arquivo para importação.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Falha no envio do arquivo. Tente novamente.');
        }

        $tmp = (string) ($arquivo['tmp_name'] ?? '');

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Arquivo enviado inválido.');
        }

        $tamanho = filesize($tmp);

        if ($tamanho === false || $tamanho === 0) {
            throw new RuntimeException('O arquivo está vazio.');
        }

        if ($tamanho > self::LIMITE_BYTES) {
            throw new RuntimeException('O arquivo excede o limite de 5 MB.');
        }

        $extensao = strtolower((string) pathinfo((string) ($arquivo['name'] ?? ''), PATHINFO_EXTENSION));

        if (!in_array($extensao, ['txt', 'csv'], true)) {
            throw new RuntimeException('Envie um arquivo .txt ou .csv.');
        }
    }

    private static function paraUtf8(string $linha): string
    {
        if (mb_check_encoding($linha, 'UTF-8')) {
            return $linha;
        }

        return (string) mb_convert_encoding($linha, 'UTF-8', 'Windows-1252');
    }

    private static function interpretarLinha(string $linha, string $mes, string $ano): array
    {
        $campos = explode(';', $linha);

        if (count($campos) < 16) {
            throw new RuntimeException('Campos insuficientes (esperado 16, encontrado ' . count($campos) . ').');
        }

        $cpf = (string) preg_replace('/\D/', '', $campos[1]);

        if (strlen($cpf) !== 11) {
            throw new RuntimeException('CPF do cliente inválido.');
        }

        $dataAtendimento = self::converterData($campos[6], 'Data de atendimento');

        if ($dataAtendimento === null) {
            throw new RuntimeException('Data de atendimento não informada.');
        }

        $procedimento = trim($campos[9]);

        if ($procedimento === '') {
            throw new RuntimeException('Código do procedimento não informado.');
        }

        $quantidadeBruta = trim($campos[11]);

        if ($quantidadeBruta === '' || !preg_match('/^\d+$/', $quantidadeBruta)) {
            throw new RuntimeException('Quantidade inválida: ' . $quantidadeBruta . '.');
        }

        return [
            'codigo_cliente' => self::limitar(trim($campos[0]), 17),
            'cpf_cliente' => $cpf,
            'codigo_numero_auxliar' => self::limitar(trim($campos[2]), 10),
            'nome_cliente' => self::limitar(trim($campos[3]), 100),
            'cod_prestador' => self::limitar(trim($campos[4]), 11),
            'nome_prestador' => self::limitar(trim($campos[5]), 40),
            'data_atendimento' => $dataAtendimento,
            'data_inicio_internacao' => self::converterData($campos[7], 'Data de início da internação'),
            'data_final_internacao' => self::converterData($campos[8], 'Data final da internação'),
            'cod_procedimento' => self::limitar($procedimento, 11),
            'tipo_procedimento' => self::limitar(trim($campos[10]), 100),
            'quantidade' => (string) (int) $quantidadeBruta,
            'valor' => self::converterValor($campos[12]),
            'nome_titular' => self::limitar(trim($campos[13]), 100),
            'cod_matricula' => self::limitar(trim($campos[14]), 6),
            'data_emissao_titulo' => self::converterData($campos[15], 'Data de emissão do título'),
            'mes_comp' => $mes,
            'ano_comp' => $ano,
        ];
    }

    private static function converterData(string $valor, string $rotulo): ?string
    {
        $valor = trim($valor ?? '');

        if ($valor === '' || $valor === '00000000' || $valor === '0000000000') {
            return '0000-00-00';
        }

        $formatos = ['!d/m/Y', '!dmY'];

        foreach ($formatos as $formato) {
            $data = DateTime::createFromFormat($formato, $valor);

            if ($data !== false) {
                $esperado = $formato === '!dmY' ? 'dmY' : 'd/m/Y';

                if ($data->format($esperado) === $valor) {
                    return $data->format('Y-m-d');
                }
            }
        }

        throw new RuntimeException($rotulo . ' inválida: ' . $valor . '.');
    }

    private static function converterValor(string $valor): string
    {
        $valor = trim($valor);

        if ($valor === '') {
            throw new RuntimeException('Valor não informado.');
        }

        $negativo = str_contains($valor, '-');
        $limpo = (string) preg_replace('/[^0-9.,]/', '', $valor);

        if ($limpo === '') {
            throw new RuntimeException('Valor inválido: ' . $valor . '.');
        }

        if (str_contains($limpo, ',') && str_contains($limpo, '.')) {
            $limpo = str_replace('.', '', $limpo);
            $limpo = str_replace(',', '.', $limpo);
        } elseif (str_contains($limpo, ',')) {
            $limpo = str_replace(',', '.', $limpo);
        }

        if (!is_numeric($limpo)) {
            throw new RuntimeException('Valor inválido: ' . $valor . '.');
        }

        $formatado = number_format((float) $limpo, 2, '.', '');

        if ($negativo && (float) $formatado != 0.0) {
            $formatado = '-' . $formatado;
        }

        if (strlen($formatado) > 11) {
            throw new RuntimeException('Valor excede o tamanho permitido: ' . $valor . '.');
        }

        return $formatado;
    }

    private static function limitar(string $valor, int $max): string
    {
        return mb_substr($valor, 0, $max);
    }
}
