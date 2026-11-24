<?php

namespace App\Service;

use App\Core\Logger;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Consulta dados estruturados da intranet (lojas, gerência, plantão comercial e
 * contatos/ramais) para responder perguntas que dependem de informação atual,
 * sem indexá-las na base do Vivi.
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

    private const ALIASES_SETOR = [
        'ti' => 'TI',
        't.i' => 'TI',
        't i' => 'TI',
        'tecnologia' => 'TI',
        'tecnologia da informacao' => 'TI',
        'informatica' => 'TI',
        'suporte' => 'TI',
        'rh' => 'Recursos Humanos',
        'dp' => 'Departamento Pessoal',
        'financeiro' => 'Administrativo | Financeiro',
        'comercial' => 'Comercial',
        'logistica' => 'Logistica',
        'manutencao' => 'Manutencao',
    ];

    private static ?array $setoresCache = null;

    /**
     * Resposta determinística para consultas de dados estruturados.
     * Evita chamar o provedor de IA para ramais, gerentes e plantões.
     *
     * @param array<int, array{role: string, content: string}> $historico
     */
    public static function respostaDireta(string $mensagem, array $historico = []): ?string
    {
        return self::respostaContato($mensagem, $historico)
            ?? self::respostaPlantao($mensagem, $historico)
            ?? self::respostaGerencia($mensagem, $historico);
    }

    /**
     * @param array<int, array{role: string, content: string}> $historico
     */
    public static function fatosGerencia(string $mensagem, array $historico = []): ?string
    {
        $consulta = self::consultaGerencia($mensagem, $historico);

        if ($consulta === null) {
            return null;
        }

        $termo = $consulta['termo'];
        $lojas = $consulta['lojas'];

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
     * @param array<int, array{role: string, content: string}> $historico
     */
    private static function respostaGerencia(string $mensagem, array $historico = []): ?string
    {
        $consulta = self::consultaGerencia($mensagem, $historico);

        if ($consulta === null) {
            return null;
        }

        $termo = $consulta['termo'];
        $lojas = $consulta['lojas'];

        if (empty($lojas)) {
            return 'Não encontrei nenhuma loja para "' . $termo . '". '
                . 'Informe o nome ou o código exato da loja.';
        }

        $linhas = [count($lojas) === 1 ? 'Gerência da loja:' : 'Gerências encontradas:'];

        foreach (array_slice($lojas, 0, 5) as $loja) {
            $linhas[] = self::formatarLoja($loja);
        }

        if (count($lojas) > 5) {
            $linhas[] = 'Há mais lojas com esse termo. Informe o código exato para refinar.';
        }

        return implode("\n", $linhas);
    }

    /**
     * @param array<int, array{role: string, content: string}> $historico
     * @return array{termo: string, lojas: array<int, array<string, mixed>>}|null
     */
    private static function consultaGerencia(string $mensagem, array $historico = []): ?array
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

        return ['termo' => $termo, 'lojas' => $lojas];
    }

    /**
     * Escala de plantão comercial (tabela escalacom). É informação pública:
     * a mesma listagem fica disponível em /comercial sem autenticação.
     *
     * @param array<int, array{role: string, content: string}> $historico
     */
    public static function fatosPlantao(string $mensagem, array $historico = []): ?string
    {
        $consulta = self::consultaPlantao($mensagem, $historico);

        if ($consulta === null) {
            return null;
        }

        return 'DADOS ATUAIS DO SISTEMA (escala de plantão comercial; informação pública exibida em /comercial):'
            . "\n" . implode("\n", self::linhasPlantao($consulta));
    }

    /**
     * @param array<int, array{role: string, content: string}> $historico
     */
    private static function respostaPlantao(string $mensagem, array $historico = []): ?string
    {
        $consulta = self::consultaPlantao($mensagem, $historico);

        if ($consulta === null) {
            return null;
        }

        return 'Plantão comercial (informação pública exibida em /comercial):'
            . "\n" . implode("\n", self::linhasPlantao($consulta));
    }

    /**
     * @param array<int, array{role: string, content: string}> $historico
     * @return array{
     *     periodo: array{tipo: string, inicio: string, fim: string, explicito: bool, rotulo?: string, contexto?: string},
     *     registros: array<int, array<string, mixed>>,
     *     hoje: string
     * }|null
     */
    private static function consultaPlantao(string $mensagem, array $historico = []): ?array
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

        return [
            'periodo' => $periodo,
            'registros' => $registros,
            'hoje' => self::hoje()->format('Y-m-d'),
        ];
    }

    /**
     * @param array{
     *     periodo: array{tipo: string, inicio: string, fim: string, explicito: bool, rotulo?: string, contexto?: string},
     *     registros: array<int, array<string, mixed>>,
     *     hoje: string
     * } $consulta
     * @return array<int, string>
     */
    private static function linhasPlantao(array $consulta): array
    {
        $periodo = $consulta['periodo'];
        $registros = $consulta['registros'];
        $hoje = $consulta['hoje'];

        if ($periodo['tipo'] === 'dia') {
            $linhas = [];
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

            return $linhas;
        }

        $linhas = ['Plantões de ' . ($periodo['rotulo'] ?? self::formatarData($periodo['inicio'])) . ':'];

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

        return $linhas;
    }

    /**
     * Ramal/contatos de um funcionário pelo nome e, opcionalmente, setor.
     * A base de contatos é pública em /contatos.
     *
     * @param array<int, array{role: string, content: string}> $historico
     */
    public static function fatosContato(string $mensagem, array $historico = []): ?string
    {
        $consulta = self::consultaContato($mensagem, $historico);

        if ($consulta === null) {
            return null;
        }

        $nome = $consulta['nome'];
        $setor = $consulta['setor'];
        $contatos = $consulta['contatos'];
        $cabecalho = 'DADOS ATUAIS DO SISTEMA (consulta direta à base de contatos da intranet; '
            . 'informação pública exibida em /contatos):';
        $filtroSetor = $setor !== null ? ' no setor ' . $setor : '';

        if (empty($contatos)) {
            return $cabecalho . "\n"
                . 'Nenhum contato ativo encontrado para "' . $nome . '"' . $filtroSetor . '. '
                . 'Peça o nome completo ou o setor para refinar a busca.';
        }

        $linhas = [$cabecalho];

        if (count($contatos) === 1) {
            $linhas[] = 'Contato encontrado:';
        } else {
            $linhas[] = 'Foram encontrados ' . count($contatos) . ' contatos' . $filtroSetor . '. '
                . 'Se não for a pessoa certa, peça o nome completo ou o setor.';
        }

        foreach (array_slice($contatos, 0, 5) as $contato) {
            $linhas[] = self::formatarContato($contato);
        }

        if (count($contatos) > 5) {
            $linhas[] = 'Há mais resultados; peça o nome completo ou o setor para refinar.';
        }

        return implode("\n", $linhas);
    }

    /**
     * @param array<int, array{role: string, content: string}> $historico
     */
    private static function respostaContato(string $mensagem, array $historico = []): ?string
    {
        $consulta = self::consultaContato($mensagem, $historico);

        if ($consulta === null) {
            return null;
        }

        $nome = $consulta['nome'];
        $setor = $consulta['setor'];
        $contatos = $consulta['contatos'];
        $filtroSetor = $setor !== null ? ' no setor ' . $setor : '';

        if (empty($contatos)) {
            return 'Não encontrei nenhum contato ativo para "' . $nome . '"' . $filtroSetor . '. '
                . 'Confira o nome ou informe também o setor.';
        }

        $linhas = [];

        if (count($contatos) === 1) {
            $linhas[] = 'Encontrei este contato:';
        } else {
            $linhas[] = 'Encontrei ' . count($contatos) . ' contatos' . $filtroSetor . '. '
                . 'Se não for a pessoa certa, informe o nome completo ou o setor:';
        }

        foreach (array_slice($contatos, 0, 5) as $contato) {
            $linhas[] = self::formatarContato($contato);
        }

        if (count($contatos) > 5) {
            $linhas[] = 'Há mais resultados; informe o nome completo ou o setor para refinar.';
        }

        return implode("\n", $linhas);
    }

    /**
     * @param array<int, array{role: string, content: string}> $historico
     * @return array{nome: string, setor: ?string, contatos: array<int, array<string, mixed>>}|null
     */
    private static function consultaContato(string $mensagem, array $historico = []): ?array
    {
        $pedido = self::extrairPedidoContato($mensagem);

        if ($pedido === null) {
            return null;
        }

        if ($pedido['nome'] === null && mb_strlen($mensagem) <= 60) {
            foreach (array_reverse($historico) as $item) {
                if (($item['role'] ?? '') !== 'user') {
                    continue;
                }

                $anterior = self::extrairPedidoContato((string) ($item['content'] ?? ''));

                if ($anterior !== null && $anterior['nome'] !== null) {
                    $pedido = $anterior;
                    break;
                }
            }
        }

        if ($pedido['nome'] === null) {
            return null;
        }

        try {
            $contatos = ContatoService::buscarPorNomeSetor($pedido['nome'], $pedido['setor']);
        } catch (Throwable $e) {
            Logger::exception($e, ['servico' => 'ViviDadosService', 'etapa' => 'contato']);

            return null;
        }

        $contatos = array_values(array_filter(
            $contatos,
            static fn(array $contato): bool => self::contatoCorresponde($contato, (string) $pedido['nome'])
        ));

        $alvo = self::normalizarNome((string) $pedido['nome']);
        usort($contatos, static function (array $a, array $b) use ($alvo): int {
            $aNome = self::normalizarNome((string) ($a['nome'] ?? ''));
            $bNome = self::normalizarNome((string) ($b['nome'] ?? ''));
            $aScore = str_starts_with($aNome, $alvo) ? 0 : 1;
            $bScore = str_starts_with($bNome, $alvo) ? 0 : 1;

            return $aScore <=> $bScore ?: strcmp($aNome, $bNome);
        });

        return [
            'nome' => (string) $pedido['nome'],
            'setor' => $pedido['setor'],
            'contatos' => $contatos,
        ];
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

    /**
     * @return array{nome: ?string, setor: ?string}|null
     */
    private static function extrairPedidoContato(string $mensagem): ?array
    {
        $temContato = preg_match('/\b(ramal|telefone|fone|corporativo|contato|e-?mail)\b/iu', $mensagem) === 1;
        $temBusca = preg_match('/\b(busque|buscar|procure|procurar|localize|localizar|quem\s+[eé])\b/iu', $mensagem) === 1;

        if (!$temContato && !$temBusca) {
            return null;
        }

        $padroes = [];

        if ($temContato) {
            $padroes[] = '/\b(?:ramal|telefone|fone|corporativo|contato|e-?mail)\s+(?:d[aeo]s?\s+)?(.+)$/iu';
        }

        if ($temBusca) {
            $padroes[] = '/\b(?:busque|buscar|procure|procurar|localize|localizar)\s+(?:por\s+|o\s+|a\s+)?(.+)$/iu';
            $padroes[] = '/\bquem\s+[eé]\s+(?:o\s+|a\s+)?(.+)$/iu';
        }

        $capturado = null;

        foreach ($padroes as $padrao) {
            if (preg_match($padrao, $mensagem, $m)) {
                $capturado = $m[1];
                break;
            }
        }

        if ($capturado === null) {
            return ['nome' => null, 'setor' => null];
        }

        $texto = preg_replace('/[?!.,;]+/u', ' ', $capturado) ?? $capturado;
        $texto = preg_replace('/\b(por favor|obrigado|obrigada|valeu)\b.*$/iu', '', $texto) ?? $texto;
        $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);
        $partes = preg_split('/\s+/u', $texto) ?: [];

        for ($i = count($partes) - 1; $i >= 1; $i--) {
            if (!in_array(mb_strtolower($partes[$i - 1]), ['da', 'do', 'de', 'das', 'dos'], true)) {
                continue;
            }

            $nome = trim(implode(' ', array_slice($partes, 0, $i - 1)));
            $setorTexto = trim(implode(' ', array_slice($partes, $i)));

            if ($nome === '' || $setorTexto === '') {
                continue;
            }

            $setor = self::resolverSetor($setorTexto);

            if ($setor !== null) {
                return ['nome' => self::limparNome($nome), 'setor' => $setor];
            }
        }

        if (!$temContato) {
            return null;
        }

        return ['nome' => self::limparNome($texto), 'setor' => null];
    }

    private static function limparNome(string $nome): ?string
    {
        $nome = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $nome) ?? $nome;
        $nome = trim(preg_replace('/\s+/u', ' ', $nome) ?? $nome);
        $nome = trim(preg_replace('/^(?:o|a|os|as|de|da|do|das|dos)\s+/iu', '', $nome) ?? $nome);

        if ($nome === '' || mb_strlen($nome) < 2) {
            return null;
        }

        if (preg_match(
            '/\b(gerente|subgerente|sub\s*gerente|loja|filial|plant[aã]o|plant[oõ]es|comprador|'
            . 'equipe|setor|departamento|suporte|pessoa|funcion[aá]rio|colaborador|'
            . 'telefone|telefones|ramal|ramais|contato|contatos|corporativo|fone|fones|'
            . 'e-?mail|e-?mails|funciona|significa|serve|cadastr\w*|atualiz\w*|alterar|trocar|'
            . 'empresa|intranet|sistema|geral|dele|dela|deles|delas|deste|desta|desse|dessa)\b/iu',
            $nome
        )) {
            return null;
        }

        return mb_substr($nome, 0, 80);
    }

    private static function resolverSetor(string $texto): ?string
    {
        $termo = self::normalizarNome($texto);

        if ($termo === '') {
            return null;
        }

        if (isset(self::ALIASES_SETOR[$termo])) {
            return self::ALIASES_SETOR[$termo];
        }

        $encontrados = [];

        foreach (self::setoresContato() as $setor) {
            $nome = self::normalizarNome((string) ($setor['setor'] ?? ''));

            if ($nome === '') {
                continue;
            }

            if ($nome === $termo) {
                return (string) $setor['setor'];
            }

            foreach (preg_split('/\s*\|\s*/', $nome) ?: [] as $segmento) {
                if ($segmento !== '' && $segmento === $termo) {
                    $encontrados[(string) $setor['setor']] = (string) $setor['setor'];
                }
            }
        }

        return count($encontrados) === 1 ? array_values($encontrados)[0] : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function setoresContato(): array
    {
        if (self::$setoresCache !== null) {
            return self::$setoresCache;
        }

        try {
            self::$setoresCache = ContatoService::listarSetores();
        } catch (Throwable $e) {
            Logger::exception($e, ['servico' => 'ViviDadosService', 'etapa' => 'setores_contato']);
            self::$setoresCache = [];
        }

        return self::$setoresCache;
    }

    /**
     * @param array<string, mixed> $contato
     */
    private static function contatoCorresponde(array $contato, string $nome): bool
    {
        $alvo = self::normalizarNome(
            trim((string) ($contato['nome'] ?? '')) . ' ' . trim((string) ($contato['nomesocial'] ?? ''))
        );
        $termos = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', self::normalizarNome($nome)) ?: [],
            static fn(string $termo): bool => $termo !== ''
        ));

        if (empty($termos)) {
            return true;
        }

        foreach ($termos as $termo) {
            if (!preg_match('/(?:^|\s)' . preg_quote($termo, '/') . '(?:$|\s)/u', $alvo)) {
                return false;
            }
        }

        return true;
    }

    private static function normalizarNome(string $texto): string
    {
        $texto = removeAccents(mb_strtolower($texto));
        $texto = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $texto) ?? $texto;

        return trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);
    }

    /**
     * @param array<string, mixed> $contato
     */
    private static function formatarContato(array $contato): string
    {
        $registro = trim((string) ($contato['nome'] ?? ''));
        $social = trim((string) ($contato['nomesocial'] ?? ''));
        $nome = $social !== '' ? $social : $registro;
        $nome = initcap($nome);

        if ($social !== '' && mb_strtoupper($social) !== mb_strtoupper($registro)) {
            $nome .= ' (registro: ' . initcap($registro) . ')';
        }

        $setor = trim((string) ($contato['setor'] ?? ''));
        $ramal = trim((string) ($contato['ramal'] ?? ''));
        $corporativo = trim((string) ($contato['corporativo'] ?? ''));
        $email = trim((string) ($contato['email'] ?? ''));
        $dados = [];

        if ($ramal !== '') {
            $dados[] = 'ramal ' . $ramal;
        } else {
            $dados[] = 'sem ramal cadastrado';
        }

        if ($corporativo !== '') {
            $dados[] = 'corporativo ' . $corporativo;
        }

        if ($email !== '') {
            $dados[] = 'e-mail ' . $email;
        }

        return '- ' . $nome
            . ($setor !== '' ? ' — ' . $setor : '')
            . ' — ' . implode(', ', $dados);
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
