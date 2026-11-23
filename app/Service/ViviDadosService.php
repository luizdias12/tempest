<?php

namespace App\Service;

use App\Core\Logger;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Consulta dados estruturados da intranet (lojas, gerência e plantão comercial)
 * para responder perguntas que dependem de informação atual, sem indexá-las na
 * base do Vivi.
 */
class ViviDadosService
{
    private const MESES = [
        'janeiro' => 1,
        'fevereiro' => 2,
        'março' => 3,
        'marco' => 3,
        'abril' => 4,
        'maio' => 5,
        'junho' => 6,
        'julho' => 7,
        'agosto' => 8,
        'setembro' => 9,
        'outubro' => 10,
        'novembro' => 11,
        'dezembro' => 12,
    ];

    private const DIAS_SEMANA = [
        1 => 'segunda-feira',
        2 => 'terça-feira',
        3 => 'quarta-feira',
        4 => 'quinta-feira',
        5 => 'sexta-feira',
        6 => 'sábado',
        7 => 'domingo',
    ];

    /**
     * @param array<int, array{role: string, content: string}> $historico
     */
    public static function fatosGerencia(string $mensagem, array $historico = []): ?string
    {
        $termo = self::extrairTermoLoja($mensagem);

        if ($termo === null && mb_strlen($mensagem) <= 60) {
            foreach (array_reverse($historico) as $item) {
                if (($item['role'] ?? '') !== 'user') {
                    continue;
                }

                $termo = self::extrairTermoLoja((string) ($item['content'] ?? ''));

                if ($termo !== null) {
                    break;
                }
            }
        }

        if ($termo === null) {
            return null;
        }

        try {
            $lojas = self::buscarLojas($termo);
        } catch (Throwable $e) {
            Logger::exception($e, ['servico' => 'ViviDadosService', 'termo' => $termo]);

            return null;
        }

        if (empty($lojas)) {
            return "DADOS ATUAIS DO SISTEMA (consulta direta à base de filiais):\n"
                . "Nenhuma loja encontrada para o termo \"{$termo}\". "
                . 'Peça ao usuário o nome ou o código exato da loja antes de responder.';
        }

        $blocos = [];

        foreach (array_slice($lojas, 0, 5) as $loja) {
            $blocos[] = self::formatarLoja($loja);
        }

        $texto = "DADOS ATUAIS DO SISTEMA (consulta direta à base de filiais e gerentes, use como fonte prioritária):\n"
            . implode("\n", $blocos);

        if (count($lojas) > 5) {
            $texto .= "\nHá mais lojas com esse termo. Se não for a loja certa, peça ao usuário o código exato.";
        }

        return $texto;
    }

    /**
     * Escala de plantão comercial (tabela escalacom). É informação pública:
     * a mesma listagem fica disponível em /comercial sem autenticação.
     *
     * @param array<int, array{role: string, content: string}> $historico
     */
    public static function fatosPlantao(string $mensagem, array $historico = []): ?string
    {
        $temIntencao = preg_match('/\b(plant[aã]o|plant[oõ]es|escala\s+comercial)\b/iu', $mensagem) === 1;
        $plural = preg_match('/\b(plant[oõ]es|escala\s+comercial)\b/iu', $mensagem) === 1;
        $periodo = self::periodoPlantao($mensagem, $plural ? 'mes' : 'dia');

        if (!$temIntencao && (!$periodo['explicito'] || !self::historicoFalaDePlantao($historico))) {
            return null;
        }

        if ($temIntencao && !$periodo['explicito']
            && preg_match('/\b(como\s+funciona|o\s+que\s+[eé]|regras?|procedimento|pol[ií]tica|funcionamento)\b/iu', $mensagem)) {
            return null;
        }

        try {
            $registros = $periodo['tipo'] === 'dia'
                ? ComercialService::plantoesPorData($periodo['inicio'])
                : ComercialService::plantoesPorPeriodo($periodo['inicio'], $periodo['fim']);
        } catch (Throwable $e) {
            Logger::exception($e, ['servico' => 'ViviDadosService', 'etapa' => 'plantao']);

            return null;
        }

        $hoje = self::hoje()->format('Y-m-d');
        $cabecalho = 'DADOS ATUAIS DO SISTEMA (escala de plantão comercial; informação pública exibida em /comercial):';

        if ($periodo['tipo'] === 'dia') {
            $linhas = [$cabecalho];
            $quando = trim((string) ($periodo['contexto'] ?? ''));
            $semanaNoContexto = in_array($quando, self::DIAS_SEMANA, true);
            $dataFmt = $quando !== ''
                ? $quando . ($semanaNoContexto ? ', ' : ' ') . self::formatarData($periodo['inicio'], !$semanaNoContexto)
                : self::formatarData($periodo['inicio']);

            if (empty($registros)) {
                $linhas[] = "Plantão de {$dataFmt}: não há registro.";
            } elseif (count($registros) === 1) {
                $linhas[] = "Plantão de {$dataFmt}: " . self::formatarPlantao($registros[0]);
            } else {
                $linhas[] = "Plantões de {$dataFmt}:";

                foreach ($registros as $registro) {
                    $linhas[] = '- ' . self::formatarPlantao($registro);
                }
            }

            if (empty($registros) && $periodo['inicio'] >= $hoje) {
                $linhas = array_merge($linhas, self::linhasProximosPlantoes($periodo['inicio']));
            }

            return implode("\n", $linhas);
        }

        $linhas = [
            $cabecalho,
            'Plantões de ' . ($periodo['rotulo'] ?? self::formatarData($periodo['inicio'])) . ':',
        ];

        if (empty($registros)) {
            $linhas[] = '- Nenhum plantão cadastrado no período.';

            if ($periodo['fim'] >= $hoje) {
                $linhas = array_merge($linhas, self::linhasProximosPlantoes($periodo['fim']));
            }
        } else {
            foreach ($registros as $registro) {
                $linhas[] = '- ' . self::formatarData((string) $registro['data']) . ': ' . self::formatarPlantao($registro);
            }

            if (count($registros) >= 40) {
                $linhas[] = 'Listagem limitada a 40 registros; refine o período se necessário.';
            }
        }

        return implode("\n", $linhas);
    }

    /**
     * @return array{tipo: string, inicio: string, fim: string, explicito: bool, rotulo?: string, contexto?: string}
     */
    private static function periodoPlantao(string $mensagem, string $padrao): array
    {
        $hoje = self::hoje();
        $texto = mb_strtolower($mensagem);

        if (preg_match('/\b(20\d{2})-(\d{2})-(\d{2})\b/u', $texto, $m)
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return self::diaPlantao($hoje->setDate((int) $m[1], (int) $m[2], (int) $m[3]));
        }

        if (preg_match('/\b(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?\b/u', $texto, $m)) {
            $ano = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : (int) $hoje->format('Y');

            if ($ano < 100) {
                $ano += 2000;
            }

            if (checkdate((int) $m[2], (int) $m[1], $ano)) {
                return self::diaPlantao($hoje->setDate($ano, (int) $m[2], (int) $m[1]));
            }
        }

        if (preg_match('/\bdepois\s+de\s+amanh[aã]\b/u', $texto)) {
            return self::diaPlantao($hoje->modify('+2 days'), 'depois de amanhã');
        }

        if (preg_match('/\bamanh[aã]\b/u', $texto)) {
            return self::diaPlantao($hoje->modify('+1 day'), 'amanhã');
        }

        if (preg_match('/\bhoje\b/u', $texto)) {
            return self::diaPlantao($hoje, 'hoje');
        }

        if (preg_match('/\bontem\b/u', $texto)) {
            return self::diaPlantao($hoje->modify('-1 day'), 'ontem');
        }

        $segunda = $hoje->modify('-' . ((int) $hoje->format('N') - 1) . ' days');

        if (preg_match('/\b(?:semana\s+passada|[uú]ltima\s+semana)\b/u', $texto)) {
            return self::periodoPlantaoSemana($segunda->modify('-7 days'));
        }

        if (preg_match('/\b(?:pr[oó]xima\s+semana|semana\s+que\s+vem)\b/u', $texto)) {
            return self::periodoPlantaoSemana($segunda->modify('+7 days'));
        }

        if (preg_match('/\b(?:esta|essa)\s+semana\b/u', $texto) || preg_match('/\bsemana\b/u', $texto)) {
            return self::periodoPlantaoSemana($segunda);
        }

        if (preg_match('/\b(?:m[eê]s\s+passado|[uú]ltimo\s+m[eê]s)\b/u', $texto)) {
            return self::periodoPlantaoMes($hoje->modify('first day of last month'));
        }

        if (preg_match('/\b(?:pr[oó]ximo\s+m[eê]s|m[eê]s\s+que\s+vem)\b/u', $texto)) {
            return self::periodoPlantaoMes($hoje->modify('first day of next month'));
        }

        if (preg_match('/\b(?:este|deste|nesse|do)\s+m[eê]s\b/u', $texto) || preg_match('/\bm[eê]s\s+atual\b/u', $texto)) {
            return self::periodoPlantaoMes($hoje->modify('first day of this month'));
        }

        $nomesMeses = implode('|', array_map(
            static fn (string $mes): string => preg_quote($mes, '/'),
            array_keys(self::MESES)
        ));

        if (preg_match('/\b(' . $nomesMeses . ')\b/u', $texto, $m)) {
            $ano = preg_match('/\b(20\d{2})\b/u', $texto, $a) ? (int) $a[1] : (int) $hoje->format('Y');

            return self::periodoPlantaoMes($hoje->setDate($ano, self::MESES[$m[1]], 1));
        }

        if (preg_match('/\b(segunda|ter[cç]a|quarta|quinta|sexta|s[aá]bado|domingo)(?:-feira)?\b/u', $texto, $m)) {
            $mapa = ['segunda' => 1, 'terca' => 2, 'quarta' => 3, 'quinta' => 4, 'sexta' => 5, 'sabado' => 6, 'domingo' => 7];
            $alvo = $mapa[str_replace(['ç', 'á'], ['c', 'a'], $m[1])] ?? 1;
            $delta = $alvo - (int) $hoje->format('N');

            if (preg_match('/\b(?:passad[oa]|[uú]ltim[oa])\b/u', $texto)) {
                if ($delta >= 0) {
                    $delta -= 7;
                }
            } elseif (preg_match('/\b(?:pr[oó]xim[oa]|que\s+vem)\b/u', $texto)) {
                if ($delta <= 0) {
                    $delta += 7;
                }
            } elseif ($delta < 0) {
                $delta += 7;
            }

            return self::diaPlantao(
                $hoje->modify(($delta >= 0 ? '+' : '') . $delta . ' days'),
                self::DIAS_SEMANA[$alvo] ?? $m[1]
            );
        }

        return $padrao === 'mes'
            ? self::periodoPlantaoMes($hoje->modify('first day of this month'), false)
            : self::diaPlantao($hoje, 'hoje', false);
    }

    private static function diaPlantao(DateTimeImmutable $dia, string $contexto = '', bool $explicito = true): array
    {
        return [
            'tipo' => 'dia',
            'inicio' => $dia->format('Y-m-d'),
            'fim' => $dia->format('Y-m-d'),
            'contexto' => $contexto,
            'explicito' => $explicito,
        ];
    }

    private static function periodoPlantaoSemana(DateTimeImmutable $segunda): array
    {
        $fim = $segunda->modify('+6 days');

        return [
            'tipo' => 'periodo',
            'inicio' => $segunda->format('Y-m-d'),
            'fim' => $fim->format('Y-m-d'),
            'rotulo' => 'semana de ' . $segunda->format('d/m/Y') . ' a ' . $fim->format('d/m/Y'),
            'explicito' => true,
        ];
    }

    private static function periodoPlantaoMes(DateTimeImmutable $inicio, bool $explicito = true): array
    {
        $nome = DateTimeService::stringMes($inicio->format('m'));

        return [
            'tipo' => 'periodo',
            'inicio' => $inicio->format('Y-m-d'),
            'fim' => $inicio->modify('last day of this month')->format('Y-m-d'),
            'rotulo' => mb_strtolower($nome) . '/' . $inicio->format('Y'),
            'explicito' => $explicito,
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function linhasProximosPlantoes(string $aPartirDe): array
    {
        try {
            $proximos = ComercialService::proximosPlantoes($aPartirDe, 2);
        } catch (Throwable $e) {
            Logger::exception($e, ['servico' => 'ViviDadosService', 'etapa' => 'proximos_plantoes']);

            return [];
        }

        if (empty($proximos)) {
            return [];
        }

        $linhas = ['Próximos plantões cadastrados:'];

        foreach ($proximos as $registro) {
            $linhas[] = '- ' . self::formatarData((string) $registro['data']) . ': ' . self::formatarPlantao($registro);
        }

        return $linhas;
    }

    /**
     * @param array<string, mixed> $registro
     */
    private static function formatarPlantao(array $registro): string
    {
        $nome = trim((string) ($registro['nome'] ?? ''));
        $feriado = trim((string) ($registro['feriado'] ?? ''));

        return ($nome !== '' ? $nome : 'comprador não cadastrado')
            . ($feriado !== '' ? ' — feriado: ' . $feriado : '');
    }

    private static function formatarData(string $data, bool $comSemana = true): string
    {
        $dia = DateTimeImmutable::createFromFormat('!Y-m-d', $data, self::fuso());

        if ($dia === false) {
            return $data;
        }

        $semana = $comSemana ? (self::DIAS_SEMANA[(int) $dia->format('N')] ?? '') : '';

        return $dia->format('d/m/Y') . ($semana !== '' ? ' (' . $semana . ')' : '');
    }

    /**
     * @param array<int, array{role: string, content: string}> $historico
     */
    private static function historicoFalaDePlantao(array $historico): bool
    {
        foreach (array_reverse($historico) as $item) {
            if (($item['role'] ?? '') === 'user'
                && preg_match('/\b(plant[aã]o|plant[oõ]es|escala\s+comercial)\b/iu', (string) ($item['content'] ?? ''))) {
                return true;
            }
        }

        return false;
    }

    private static function hoje(): DateTimeImmutable
    {
        return new DateTimeImmutable('today', self::fuso());
    }

    private static function fuso(): DateTimeZone
    {
        return new DateTimeZone('America/Sao_Paulo');
    }

    private static function extrairTermoLoja(string $mensagem): ?string
    {
        if (!preg_match('/\b(gerente|subgerente|sub\s*gerente|respons[aá]vel)\b/iu', $mensagem)) {
            return null;
        }

        $texto = preg_replace('/[?!.,;]+/u', ' ', $mensagem) ?? $mensagem;
        $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);

        if (!preg_match('/\b(?:loja|filial|unidade|supermercado|hipermercado)\s+(?:n[º°o]?\s*)?(?:de\s+|da\s+|do\s+)?(.+)$/iu', $texto, $m)
            && !preg_match('/\b(?:gerente|subgerente|sub\s*gerente)\s+(?:da|do|de)\s+(.+)$/iu', $texto, $m)) {
            return null;
        }

        $termo = $m[1];
        $termo = preg_replace('/\b(quem|qual|quais|como|onde|quando|por\s+favor)\b.*$/iu', '', $termo) ?? $termo;
        $termo = preg_replace('/\s+e\s+(?:o|a|os|as)\s+.*$/iu', '', $termo) ?? $termo;
        $termo = preg_replace('/^(?:loja|filial|unidade|supermercado|hipermercado|de|da|do|das|dos)\s+/iu', '', $termo) ?? $termo;
        $termo = preg_replace('/[^\p{L}\p{N}\s.\-\/]/u', ' ', $termo) ?? $termo;
        $termo = trim(preg_replace('/\s+/u', ' ', $termo) ?? $termo, " \t\n\r\0\x0B\"'");

        if ($termo === '') {
            return null;
        }

        return mb_substr($termo, 0, 60);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buscarLojas(string $termo): array
    {
        if (preg_match('/^(\d{1,4})\b/', $termo, $m)) {
            $porCodigo = RegionalService::buscarGerenciaPorLoja($m[1]);

            if (!empty($porCodigo)) {
                return $porCodigo;
            }
        }

        $lojas = RegionalService::buscarGerenciaPorLoja($termo);

        if (!empty($lojas)) {
            return $lojas;
        }

        $semGenericos = trim(preg_replace('/\b(loja|filial|unidade|supermercado|hipermercado|shopping|mercado)\b/iu', ' ', $termo) ?? '');
        $semGenericos = trim(preg_replace('/\s+/u', ' ', $semGenericos) ?? $semGenericos);

        if ($semGenericos !== '' && $semGenericos !== $termo) {
            $lojas = RegionalService::buscarGerenciaPorLoja($semGenericos);

            if (!empty($lojas)) {
                return $lojas;
            }
        }

        $encontradas = [];
        $unica = null;

        foreach (preg_split('/\s+/u', $semGenericos !== '' ? $semGenericos : $termo) ?: [] as $palavra) {
            if (mb_strlen($palavra) < 4) {
                continue;
            }

            $resultados = RegionalService::buscarGerenciaPorLoja($palavra);

            if (count($resultados) === 1 && $unica === null) {
                $unica = $resultados[0];
            }

            foreach ($resultados as $loja) {
                $chave = (string) ($loja['codgfilial'] ?? '') . '|' . (string) ($loja['codfilial'] ?? '');
                $encontradas[$chave] = $encontradas[$chave] ?? $loja;
            }
        }

        return $unica !== null ? [$unica] : array_values($encontradas);
    }

    /**
     * @param array<string, mixed> $loja
     */
    private static function formatarLoja(array $loja): string
    {
        $codigo = trim((string) ($loja['codfilial'] ?? ''));
        $nome = trim((string) ($loja['loja'] ?? ''));
        $regional = trim((string) ($loja['regional'] ?? ''));

        $titulo = 'Loja' . ($codigo !== '' ? ' ' . $codigo : '') . ($nome !== '' ? ' - ' . $nome : '');

        if ($regional !== '') {
            $titulo .= ' (regional ' . $regional . ')';
        }

        $linhas = [$titulo];

        $gerente = self::formatarPessoa('Gerente', $loja, 'gerente');
        $subgerente = self::formatarPessoa('Subgerente', $loja, 'subgerente');

        if ($gerente !== null) {
            $linhas[] = '  ' . $gerente;
        }

        if ($subgerente !== null) {
            $linhas[] = '  ' . $subgerente;
        }

        if ($gerente === null && $subgerente === null) {
            $linhas[] = '  Gerência não cadastrada para esta loja.';
        }

        return implode("\n", $linhas);
    }

    /**
     * @param array<string, mixed> $loja
     */
    private static function formatarPessoa(string $rotulo, array $loja, string $prefixo): ?string
    {
        $nome = trim((string) ($loja[$prefixo] ?? ''));

        if ($nome === '') {
            $usuario = trim((string) ($loja[$prefixo . '_usuario'] ?? ''));
            $nome = $usuario !== '' ? ucwords(str_replace(['.', '_', '-'], ' ', $usuario)) : '';
        }

        if ($nome === '') {
            return null;
        }

        $dados = [];
        $corporativo = trim((string) ($loja[$prefixo . '_corporativo'] ?? ''));
        $ramal = trim((string) ($loja[$prefixo . '_ramal'] ?? ''));
        $email = trim((string) ($loja[$prefixo . '_email'] ?? ''));

        if ($corporativo !== '') {
            $dados[] = 'corporativo ' . $corporativo;
        }

        if ($ramal !== '') {
            $dados[] = 'ramal ' . $ramal;
        }

        if ($email !== '') {
            $dados[] = 'e-mail ' . $email;
        }

        return $rotulo . ': ' . $nome . (empty($dados) ? '' : ' — ' . implode(', ', $dados));
    }
}
