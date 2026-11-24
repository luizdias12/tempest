<?php

namespace App\Service;

use App\Core\DB;
use App\Core\Logger;
use App\Model\Mysql\DocModel;
use App\Model\Mysql\ViviModel;
use DOMDocument;
use DOMXPath;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;
use ZipArchive;

class ViviKnowledgeService
{
    private const EXTENSOES = ['md', 'txt', 'pdf', 'docx'];

    /**
     * Categorias do módulo Documentos que não devem alimentar a Vivi.
     * Formato: "DIRETORIO/SUBDIRETORIO" em maiúsculas e sem acentos.
     */
    private const CATEGORIAS_IGNORADAS = ['TI/DESENVOLVIMENTO'];

    private static function env(string $chave, string $padrao = ''): string
    {
        return trim((string) (getenv($chave) ?: ($_ENV[$chave] ?? $padrao)));
    }

    /**
     * Indexa um arquivo ou diretório (.md/.txt/.pdf/.docx), recursivamente.
     *
     * @param array{publico?: bool, reindexar?: bool} $opcoes
     * @return array{arquivos: int, trechos: int, ignorados: int, embeddings: int, erros: string[]}
     */
    public static function indexar(string $caminho, array $opcoes = []): array
    {
        if (!file_exists($caminho)) {
            throw new RuntimeException('Caminho não encontrado: ' . $caminho);
        }

        $publico = (bool) ($opcoes['publico'] ?? false);
        $reindexar = (bool) ($opcoes['reindexar'] ?? false);
        $arquivos = [];

        if (is_dir($caminho)) {
            $base = rtrim($caminho, '/');
            $iterador = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterador as $arquivo) {
                if (!$arquivo->isFile()) {
                    continue;
                }

                if (!in_array(strtolower($arquivo->getExtension()), self::EXTENSOES, true)) {
                    continue;
                }

                $arquivos[] = $arquivo->getPathname();
            }

            sort($arquivos);
            $prefixo = $base . '/';
        } else {
            $extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));

            if (!in_array($extensao, self::EXTENSOES, true)) {
                throw new RuntimeException('Formato não suportado. Use arquivos .md, .txt, .pdf ou .docx.');
            }

            $arquivos[] = $caminho;
            $prefixo = '';
        }

        $resumo = [
            'arquivos' => 0,
            'trechos' => 0,
            'ignorados' => 0,
            'embeddings' => 0,
            'erros' => [],
        ];

        foreach ($arquivos as $arquivo) {
            $fonte = $prefixo !== '' && str_starts_with($arquivo, $prefixo)
                ? substr($arquivo, strlen($prefixo))
                : basename($arquivo);

            try {
                $resultado = self::indexarArquivo($arquivo, $fonte, $publico, $reindexar);

                $resumo['arquivos']++;
                $resumo['trechos'] += $resultado['trechos'];
                $resumo['ignorados'] += $resultado['ignorados'];
                $resumo['embeddings'] += $resultado['embeddings'];
            } catch (Throwable $e) {
                Logger::exception($e, ['servico' => 'ViviKnowledgeService', 'arquivo' => $arquivo]);
                $resumo['erros'][] = basename($arquivo) . ': ' . $e->getMessage();
            }
        }

        return $resumo;
    }

    /**
     * @return array{trechos: int, ignorados: int, embeddings: int}
     */
    public static function indexarArquivo(
        string $arquivo,
        string $fonte,
        bool $publico = false,
        bool $reindexar = false
    ): array {
        $conteudo = self::extrairTextoArquivo($arquivo);
        $titulo = self::extrairTitulo($conteudo, $arquivo);

        return self::indexarConteudo($fonte, $titulo, $conteudo, $publico, $reindexar);
    }

    /**
     * Indexa conteúdo já extraído (arquivo, documento da intranet etc.).
     *
     * @return array{trechos: int, ignorados: int, embeddings: int}
     */
    public static function indexarConteudo(
        string $fonte,
        string $titulo,
        string $conteudo,
        bool $publico = false,
        bool $reindexar = false,
        bool $acessoGeral = false,
        ?string $permissoes = null,
        ?string $contexto = null
    ): array {
        $conteudo = self::normalizarTexto($conteudo);
        $trechos = self::montarTrechos($conteudo, $contexto ?? $titulo);

        if (empty($trechos)) {
            return ['trechos' => 0, 'ignorados' => 0, 'embeddings' => 0];
        }

        $hashes = array_map(static fn(string $t): string => hash('sha256', $t), $trechos);
        $existentes = ViviModel::hashesPorFonte($fonte);

        sort($hashes);
        sort($existentes);

        if (!$reindexar && $hashes === $existentes && !ViviModel::publicoDivergente($fonte, $publico)) {
            ViviModel::atualizarAcessoPorFonte($fonte, $acessoGeral, $permissoes);

            return ['trechos' => 0, 'ignorados' => count($trechos), 'embeddings' => 0];
        }

        $embeddings = self::gerarEmbeddings($trechos);
        $conexao = DB::connect('mysql');
        $conexao->beginTransaction();

        $inseridos = 0;
        $comEmbedding = 0;

        try {
            ViviModel::deletarPorFonte($fonte);

            foreach ($trechos as $indice => $texto) {
                $embedding = $embeddings[$indice] ?? null;
                $jsonEmbedding = null;

                if (is_array($embedding) && !empty($embedding)) {
                    $codificado = json_encode($embedding, JSON_UNESCAPED_UNICODE);
                    $jsonEmbedding = is_string($codificado) ? $codificado : null;
                }

                if ($jsonEmbedding !== null) {
                    $comEmbedding++;
                }

                ViviModel::inserir(
                    $fonte,
                    $titulo,
                    $texto,
                    hash('sha256', $texto),
                    $publico,
                    $jsonEmbedding,
                    $acessoGeral,
                    $permissoes
                );

                $inseridos++;
            }

            $conexao->commit();
        } catch (Throwable $e) {
            $conexao->rollBack();

            throw $e;
        }

        return ['trechos' => $inseridos, 'ignorados' => 0, 'embeddings' => $comEmbedding];
    }

    public static function limpar(): int
    {
        return ViviModel::limpar();
    }

    public static function limparDocumentosIntranet(): int
    {
        return ViviModel::deletarPorFontePrefixo('intranet:doc:');
    }

    public static function limparCuradoria(): int
    {
        return ViviModel::deletarPorFontePrefixo('curadoria:');
    }

    /**
     * Indexa conteúdo curado (FAQ de navegação e processos) mantido no repositório.
     * Arquivos em publico/ ficam visíveis também a visitantes anônimos; os demais,
     * apenas a usuários autenticados.
     * arquivos em /vivi/publico sao visiveis a usuarios anonimos
     *
     * @param array{reindexar?: bool, onProgress?: callable} $opcoes
     * @return array{arquivos: int, trechos: int, ignorados: int, embeddings: int, removidos: int, erros: string[]}
     */
    public static function indexarCuradoria(string $pasta, array $opcoes = []): array
    {
        if (!is_dir($pasta)) {
            throw new RuntimeException('Pasta de curadoria não encontrada: ' . $pasta);
        }

        $reindexar = (bool) ($opcoes['reindexar'] ?? false);
        $onProgress = $opcoes['onProgress'] ?? null;
        $base = rtrim($pasta, '/');
        $arquivos = [];

        $iterador = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterador as $arquivo) {
            if ($arquivo->isFile() && in_array(strtolower($arquivo->getExtension()), self::EXTENSOES, true)) {
                $arquivos[] = $arquivo->getPathname();
            }
        }

        sort($arquivos);

        $resumo = [
            'arquivos' => count($arquivos),
            'trechos' => 0,
            'ignorados' => 0,
            'embeddings' => 0,
            'removidos' => 0,
            'erros' => [],
        ];

        $fontesExistentes = [];

        foreach ($arquivos as $indice => $arquivo) {
            $relativo = str_replace(DIRECTORY_SEPARATOR, '/', substr($arquivo, strlen($base) + 1));
            $fonte = 'curadoria:' . $relativo;
            $publico = str_starts_with($relativo, 'publico/');
            $fontesExistentes[$fonte] = true;
            $status = 'indexado';

            try {
                $conteudo = self::extrairTextoArquivo($arquivo);
                $titulo = self::extrairTitulo($conteudo, $arquivo);
                $resultado = self::indexarConteudo(
                    $fonte,
                    $titulo,
                    $conteudo,
                    $publico,
                    $reindexar,
                    true,
                    null,
                    $titulo
                );

                $resumo['trechos'] += $resultado['trechos'];
                $resumo['ignorados'] += $resultado['ignorados'];
                $resumo['embeddings'] += $resultado['embeddings'];

                if ($resultado['trechos'] === 0) {
                    $status = 'ignorado';
                }
            } catch (Throwable $e) {
                Logger::exception($e, ['servico' => 'ViviKnowledgeService', 'curadoria' => $arquivo]);
                $resumo['erros'][] = basename($arquivo) . ': ' . $e->getMessage();
                $status = 'erro';
            }

            if (is_callable($onProgress)) {
                $onProgress($indice + 1, count($arquivos), $arquivo, $status);
            }
        }

        foreach (ViviModel::fontesComPrefixo('curadoria:') as $fonteAntiga) {
            if (!isset($fontesExistentes[$fonteAntiga])) {
                $resumo['removidos'] += ViviModel::deletarPorFonte($fonteAntiga);
            }
        }

        return $resumo;
    }

    /**
     * Indexa os documentos ativos do módulo Documentos com as permissões do módulo.
     *
     * Documentos com geral = 'S' ficam acessíveis a qualquer usuário autenticado.
     * Documentos restritos guardam os códigos de função pai de doc_permissao.
     *
     * @param array{reindexar?: bool, apenas_gerais?: bool, limite?: int, onProgress?: callable} $opcoes
     * @return array{documentos: int, indexados: int, ignorados: int, excluidos: int, sem_texto: int, trechos: int, embeddings: int, removidos: int, erros: string[]}
     */
    public static function indexarDocumentosIntranet(array $opcoes = []): array
    {
        $reindexar = (bool) ($opcoes['reindexar'] ?? false);
        $apenasGerais = (bool) ($opcoes['apenas_gerais'] ?? false);
        $limite = isset($opcoes['limite']) ? max(0, (int) $opcoes['limite']) : null;
        $onProgress = $opcoes['onProgress'] ?? null;

        $docs = DocModel::listarParaIndexacao($apenasGerais, $limite);
        $permissoes = DocModel::permissoesPorDocumento();

        $resumo = [
            'documentos' => count($docs),
            'indexados' => 0,
            'ignorados' => 0,
            'excluidos' => 0,
            'sem_texto' => 0,
            'trechos' => 0,
            'embeddings' => 0,
            'removidos' => 0,
            'erros' => [],
        ];

        $ativos = [];
        $total = count($docs);

        foreach ($docs as $indice => $doc) {
            if (self::categoriaIgnorada($doc)) {
                // Não entra em $ativos: a limpeza de órfãos remove os trechos antigos.
                $resumo['excluidos']++;

                if (is_callable($onProgress)) {
                    $onProgress($indice + 1, $total, $doc, 'excluido');
                }

                continue;
            }

            $idDoc = (int) ($doc['id_doc'] ?? 0);
            $idVersao = (int) ($doc['id_versao'] ?? 0);

            if ($idDoc <= 0 || $idVersao <= 0) {
                continue;
            }

            $ativos[$idDoc] = true;
            $fonte = "intranet:doc:{$idDoc}:v{$idVersao}";
            $acessoGeral = ((string) ($doc['geral'] ?? 'N')) === 'S';
            $funcoes = $acessoGeral ? [] : ($permissoes[$idDoc]['funcoes'] ?? []);
            $funcoes = array_values(array_unique(array_map('strval', $funcoes)));
            sort($funcoes, SORT_STRING);
            $permissoesTexto = $acessoGeral ? null : implode(',', $funcoes);
            $titulo = self::tituloDocumento($doc);
            $status = 'indexado';

            try {
                $metadados = ViviModel::metadadosPorFonte($fonte);

                if (!$reindexar && $metadados !== null && (string) ($metadados['titulo'] ?? '') === $titulo) {
                    ViviModel::atualizarAcessoPorFonte($fonte, $acessoGeral, $permissoesTexto);
                    $resumo['ignorados']++;
                    $status = 'ignorado';
                } else {
                    [$conteudo, $semTexto] = self::montarConteudoDocumento($doc);
                    $resultado = self::indexarConteudo(
                        $fonte,
                        $titulo,
                        $conteudo,
                        false,
                        true,
                        $acessoGeral,
                        $permissoesTexto,
                        $titulo
                    );

                    ViviModel::deletarPorFontePrefixo("intranet:doc:{$idDoc}:", $fonte);

                    $resumo['indexados']++;
                    $resumo['trechos'] += $resultado['trechos'];
                    $resumo['embeddings'] += $resultado['embeddings'];

                    if ($semTexto) {
                        $resumo['sem_texto']++;
                    }
                }
            } catch (Throwable $e) {
                Logger::exception($e, [
                    'servico' => 'ViviKnowledgeService',
                    'documento' => $idDoc,
                    'arquivo' => $doc['caminho'] ?? null,
                ]);
                $resumo['erros'][] = "#{$idDoc} " . ($doc['titulo'] ?? '') . ': ' . $e->getMessage();
                $status = 'erro';
            }

            if (is_callable($onProgress)) {
                $onProgress($indice + 1, $total, $doc, $status);
            }
        }

        // Só remove registros de documentos removidos em uma execução completa.
        if (!$apenasGerais && $limite === null && !empty($ativos)) {
            foreach (ViviModel::fontesComPrefixo('intranet:doc:') as $fonteAntiga) {
                if (!preg_match('/^intranet:doc:(\d+):v\d+$/', $fonteAntiga, $partes)) {
                    continue;
                }

                if (!isset($ativos[(int) $partes[1]])) {
                    $resumo['removidos'] += ViviModel::deletarPorFonte($fonteAntiga);
                }
            }
        }

        return $resumo;
    }

    /**
     * Categoria normalizada do documento: "DIRETORIO/SUBDIRETORIO".
     *
     * @param array<string, mixed> $doc
     */
    private static function categoriaDocumento(array $doc): string
    {
        $diretorio = (string) preg_replace('/\s+/', ' ', trim((string) ($doc['diretorio'] ?? '')));
        $subdiretorio = (string) preg_replace('/\s+/', ' ', trim((string) ($doc['subdiretorio'] ?? '')));
        $categoria = trim($diretorio . '/' . $subdiretorio, '/');

        return removeAccents(mb_strtoupper($categoria));
    }

    /**
     * @param array<string, mixed> $doc
     */
    private static function categoriaIgnorada(array $doc): bool
    {
        return in_array(self::categoriaDocumento($doc), self::CATEGORIAS_IGNORADAS, true);
    }

    /**
     * @param array<string, mixed> $doc
     * @return array{0: string, 1: bool}
     */
    private static function montarConteudoDocumento(array $doc): array
    {
        $caminho = (string) ($doc['caminho'] ?? '');
        $diretorio = trim((string) ($doc['diretorio'] ?? ''));
        $subdiretorio = trim((string) ($doc['subdiretorio'] ?? ''));
        $categoria = trim($diretorio . ' / ' . $subdiretorio, ' /');
        $tipo = strtolower((string) ($doc['tipo'] ?? pathinfo($caminho, PATHINFO_EXTENSION)));
        $versao = (int) ($doc['versao'] ?? 0);
        $atualizado = trim((string) ($doc['dt_upload'] ?? ''));

        $cabecalho = [
            'Documento: ' . (string) ($doc['titulo'] ?? ''),
            'Categoria: ' . ($categoria !== '' ? $categoria : 'não informada'),
            'Tipo: ' . ($tipo !== '' ? $tipo : 'desconhecido'),
            'Versão: ' . $versao,
            'Arquivo: ' . $caminho,
        ];

        if ($atualizado !== '') {
            $cabecalho[] = 'Atualizado em: ' . $atualizado;
        }

        $extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
        $texto = '';
        $semTexto = false;

        if (in_array($extensao, ['md', 'txt', 'pdf', 'docx'], true)) {
            $absoluto = DocService::caminhoAbsoluto($caminho);

            if (!is_file($absoluto)) {
                throw new RuntimeException('Arquivo do documento não encontrado no servidor.');
            }

            $texto = self::extrairTextoArquivo($absoluto);
            $semTexto = trim($texto) === '';
        } else {
            $semTexto = true;
        }

        if ($semTexto) {
            $texto = 'Não foi possível extrair o texto deste arquivo. '
                . 'Consulte o documento original na intranet para ver o conteúdo.';
        }

        return [implode("\n", $cabecalho) . "\n\n" . $texto, $semTexto];
    }

    /**
     * @param array<string, mixed> $doc
     */
    private static function tituloDocumento(array $doc): string
    {
        $titulo = trim((string) ($doc['titulo'] ?? ''));
        $categoria = trim(
            trim((string) ($doc['diretorio'] ?? '')) . ' / ' . trim((string) ($doc['subdiretorio'] ?? '')),
            ' /'
        );

        if ($categoria !== '') {
            $titulo .= ($titulo !== '' ? ' — ' : '') . $categoria;
        }

        return mb_substr($titulo, 0, 255);
    }

    /**
     * Extrai texto de .md/.txt/.pdf/.docx.
     */
    private static function extrairTextoArquivo(string $arquivo): string
    {
        $tamanho = @filesize($arquivo);

        if ($tamanho !== false && $tamanho > DocService::MAX_SIZE) {
            throw new RuntimeException('Arquivo maior que o limite permitido para indexação.');
        }

        $extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));

        return match ($extensao) {
            'md', 'txt' => self::lerTexto($arquivo),
            'pdf' => self::extrairPdf($arquivo),
            'docx' => self::extrairDocx($arquivo),
            default => throw new RuntimeException(
                'Formato não suportado para extração de texto. Use .md, .txt, .pdf ou .docx.'
            ),
        };
    }

    private static function lerTexto(string $arquivo): string
    {
        $conteudo = @file_get_contents($arquivo);

        if ($conteudo === false) {
            throw new RuntimeException('Não foi possível ler o arquivo.');
        }

        return self::normalizarTexto($conteudo);
    }

    private static function extrairPdf(string $arquivo): string
    {
        $comando = [];

        if (is_executable('/usr/bin/timeout')) {
            $comando = ['/usr/bin/timeout', '120'];
        } elseif (is_executable('/bin/timeout')) {
            $comando = ['/bin/timeout', '120'];
        }

        $comando = array_merge($comando, [
            'pdftotext',
            '-layout',
            '-enc',
            'UTF-8',
            '-nopgbrk',
            $arquivo,
            '-',
        ]);

        [$codigo, $saida, $erro] = self::executar($comando);

        if ($codigo === 124) {
            throw new RuntimeException('Tempo esgotado ao extrair o texto do PDF.');
        }

        if ($codigo !== 0) {
            $detalhe = trim($erro);
            throw new RuntimeException(
                'Falha ao extrair o PDF' . ($detalhe !== '' ? ': ' . mb_substr($detalhe, 0, 200) : '.')
            );
        }

        return self::normalizarTexto($saida);
    }

    private static function extrairDocx(string $arquivo): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('A extensão PHP zip é necessária para ler arquivos .docx.');
        }

        $zip = new ZipArchive();

        if ($zip->open($arquivo) !== true) {
            throw new RuntimeException('Não foi possível abrir o arquivo .docx.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('O arquivo .docx não contém word/document.xml.');
        }

        $dom = new DOMDocument();

        if (!@$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            throw new RuntimeException('Não foi possível interpretar o conteúdo do .docx.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $linhas = [];
        $paragrafos = $xpath->query('//w:p') ?: [];

        foreach ($paragrafos as $paragrafo) {
            $linha = '';
            $nos = $xpath->query('.//w:t | .//w:tab | .//w:br', $paragrafo) ?: [];

            foreach ($nos as $no) {
                if ($no->nodeName === 'w:tab') {
                    $linha .= "\t";
                } elseif ($no->nodeName === 'w:br') {
                    $linha .= "\n";
                } else {
                    $linha .= $no->nodeValue;
                }
            }

            $linha = trim($linha);

            if ($linha !== '') {
                $linhas[] = $linha;
            }
        }

        return self::normalizarTexto(implode("\n", $linhas));
    }

    /**
     * @param string[] $comando
     * @return array{0: int, 1: string, 2: string}
     */
    private static function executar(array $comando): array
    {
        $processo = @proc_open($comando, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($processo)) {
            throw new RuntimeException('Não foi possível executar o extrator de texto.');
        }

        $saida = stream_get_contents($pipes[1]);
        $erro = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $codigo = proc_close($processo);

        return [$codigo, (string) $saida, (string) $erro];
    }

    private static function normalizarTexto(string $texto): string
    {
        if (!mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
        }

        $texto = str_replace("\f", "\n\n", $texto);
        $texto = (string) preg_replace('/\r\n?/', "\n", $texto);
        $texto = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $texto);

        return trim($texto);
    }

    /**
     * Informa se existe conteúdo relacionado que o visitante/usuário não pode acessar,
     * sem retornar título, trecho ou quantidade.
     */
    public static function existeConteudoRestrito(
        string $pergunta,
        bool $somentePublico,
        ?int $codfuncaoPai = null
    ): bool {
        $pergunta = trim($pergunta);

        if ($pergunta === '') {
            return false;
        }

        try {
            return ViviModel::existeConteudoRestrito($pergunta, $somentePublico, $codfuncaoPai);
        } catch (Throwable $e) {
            Logger::warning('Vivi: não foi possível verificar conteúdo restrito', [
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Mantém apenas os trechos que cobrem os termos significativos da consulta,
     * descartando resultados lexicais fracos (documentos técnicos, por exemplo).
     * Um trecho é aceito ao cobrir 60% dos termos no texto ou ao citar algum
     * termo no título com cobertura quase total no texto.
     *
     * @param array<int, array<string, mixed>> $trechos
     * @return array<int, array<string, mixed>>
     */
    public static function filtrarRelevantes(string $consulta, array $trechos): array
    {
        if (empty($trechos)) {
            return [];
        }

        $termos = ViviModel::termosRelevantes($consulta);

        if (empty($termos)) {
            return [];
        }

        $minimo = max(1, (int) ceil(count($termos) * 0.6));
        $filtrados = [];

        foreach ($trechos as $trecho) {
            $titulo = removeAccents(mb_strtolower((string) ($trecho['titulo'] ?? '')));
            $texto = $titulo . ' ' . removeAccents(mb_strtolower((string) ($trecho['conteudo'] ?? '')));
            $noTexto = 0;
            $noTitulo = 0;

            foreach ($termos as $termo) {
                $alvo = removeAccents($termo);

                if (mb_strpos($texto, $alvo) !== false) {
                    $noTexto++;
                }

                if (mb_strpos($titulo, $alvo) !== false) {
                    $noTitulo++;
                }
            }

            if ($noTexto >= $minimo || ($noTitulo >= 1 && $noTexto >= $minimo - 1)) {
                $filtrados[] = $trecho;
            }
        }

        return $filtrados;
    }

    /**
     * Recupera trechos relevantes: embeddings quando disponíveis,
     * com fallback para FULLTEXT/LIKE.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recuperar(
        string $pergunta,
        bool $somentePublico,
        int $limite,
        ?int $codfuncaoPai = null
    ): array {
        $pergunta = trim($pergunta);

        if ($pergunta === '') {
            return [];
        }

        $limite = max(1, min(10, $limite));

        if (ViviAiService::modeloEmbedding() !== '') {
            try {
                $vetor = ViviAiService::embeddings([$pergunta])[0] ?? null;

                if (is_array($vetor) && !empty($vetor)) {
                    $maxChunks = max(100, (int) self::env('VIVI_MAX_CHUNKS_BUSCA', '1500'));
                    $scoreMin = (float) self::env('VIVI_SCORE_MIN', '0.25');
                    $linhas = ViviModel::listarComEmbedding($somentePublico, $maxChunks, $codfuncaoPai);
                    $ranqueadas = [];

                    foreach ($linhas as $linha) {
                        $embedding = json_decode((string) ($linha['embedding'] ?? ''), true);

                        if (!is_array($embedding) || count($embedding) !== count($vetor)) {
                            continue;
                        }

                        $score = self::similaridade($vetor, $embedding);

                        if ($score >= $scoreMin) {
                            $linha['score'] = $score;
                            $ranqueadas[] = $linha;
                        }
                    }

                    if (!empty($ranqueadas)) {
                        usort($ranqueadas, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

                        return array_slice($ranqueadas, 0, $limite);
                    }
                }
            } catch (Throwable $e) {
                Logger::warning('Vivi: busca vetorial falhou, usando FULLTEXT', ['erro' => $e->getMessage()]);
            }
        }

        return ViviModel::buscarTexto($pergunta, $limite, $somentePublico, $codfuncaoPai);
    }

    /**
     * @param string[] $trechos
     * @return array<int, float[]|null>
     */
    private static function gerarEmbeddings(array $trechos): array
    {
        $resultado = array_fill(0, count($trechos), null);

        if (ViviAiService::modeloEmbedding() === '' || empty($trechos)) {
            return $resultado;
        }

        $posicao = 0;

        foreach (array_chunk($trechos, 16) as $lote) {
            try {
                $vetores = ViviAiService::embeddings($lote);
            } catch (Throwable $e) {
                Logger::warning('Vivi: falha ao gerar embeddings do lote', ['erro' => $e->getMessage()]);
                $vetores = [];
            }

            foreach ($lote as $indice => $_) {
                $resultado[$posicao] = $vetores[$indice] ?? null;
                $posicao++;
            }
        }

        return $resultado;
    }

    /**
     * Divide o conteúdo em trechos e prefixa cada um com o contexto do documento/seção.
     *
     * @return string[]
     */
    private static function montarTrechos(string $texto, string $contexto): array
    {
        $contexto = trim($contexto);
        $trechos = [];

        foreach (self::dividir($texto) as $parte) {
            $conteudo = trim($parte['texto']);

            if ($conteudo === '') {
                continue;
            }

            $rotulo = $contexto;

            if (($parte['secao'] ?? '') !== '') {
                $rotulo = trim($rotulo . ' > ' . $parte['secao'], ' >');
            }

            if ($rotulo !== '') {
                $conteudo = '[' . mb_substr($rotulo, 0, 220) . "]\n\n" . $conteudo;
            }

            $trechos[] = $conteudo;
        }

        return array_values(array_unique($trechos));
    }

    /**
     * Divide o texto em trechos, tentando respeitar seções (títulos).
     *
     * @return array<int, array{secao: ?string, texto: string}>
     */
    private static function dividir(string $texto, int $tamanho = 1200, int $sobreposicao = 150): array
    {
        $texto = trim((string) preg_replace('/\r\n?/', "\n", $texto));
        $texto = (string) preg_replace("/\n{3,}/", "\n\n", $texto);

        if ($texto === '') {
            return [];
        }

        $paragrafos = preg_split("/\n\s*\n/", $texto) ?: [];
        $trechos = [];
        $secao = null;
        $atual = '';
        $atualEhTitulo = false;

        $descarregar = static function () use (&$trechos, &$atual, &$secao, &$atualEhTitulo): void {
            $atual = trim($atual);

            if ($atual !== '' && !$atualEhTitulo) {
                $trechos[] = ['secao' => $secao, 'texto' => $atual];
            }

            $atual = '';
            $atualEhTitulo = false;
        };

        foreach ($paragrafos as $paragrafo) {
            $paragrafo = trim($paragrafo);

            if ($paragrafo === '') {
                continue;
            }

            $titulo = self::detectarSecao($paragrafo);

            if ($titulo !== null) {
                $descarregar();
                $secao = $titulo;
                $atual = $paragrafo;
                $atualEhTitulo = true;

                continue;
            }

            if (mb_strlen($paragrafo) > $tamanho) {
                $descarregar();

                foreach (mb_str_split($paragrafo, $tamanho) as $pedaco) {
                    $pedaco = trim($pedaco);

                    if ($pedaco !== '') {
                        $trechos[] = ['secao' => $secao, 'texto' => $pedaco];
                    }
                }

                continue;
            }

            $candidato = $atual === '' ? $paragrafo : $atual . "\n\n" . $paragrafo;

            if (mb_strlen($candidato) > $tamanho && $atual !== '') {
                $anterior = $atual;
                $descarregar();
                $inicio = $sobreposicao > 0 ? mb_substr($anterior, -$sobreposicao) : '';
                $atual = ltrim($inicio . "\n\n" . $paragrafo);
                $atualEhTitulo = false;
            } else {
                $atual = $candidato;
                $atualEhTitulo = false;
            }
        }

        $descarregar();

        return array_values(array_filter(
            $trechos,
            static fn(array $trecho): bool => mb_strlen($trecho['texto']) >= 10
        ));
    }

    /**
     * Reconhece títulos de seção: markdown ("## Título") ou linha curta em caixa alta.
     */
    private static function detectarSecao(string $paragrafo): ?string
    {
        if (strpos($paragrafo, "\n") !== false) {
            return null;
        }

        $linha = trim($paragrafo);
        $tamanho = mb_strlen($linha);

        if ($tamanho < 3 || $tamanho > 80 || !preg_match('/\p{L}/u', $linha)) {
            return null;
        }

        if (preg_match('/^#{1,6}\s+(.+)$/u', $linha, $partes)) {
            $titulo = trim($partes[1], " \t#*_");

            return $titulo !== '' ? mb_substr($titulo, 0, 80) : null;
        }

        if (preg_match('/^[\p{Lu}\p{N}][\p{Lu}\p{N} .,\/&()º°\-–—:]+$/u', $linha)) {
            return $linha;
        }

        return null;
    }

    private static function extrairTitulo(string $conteudo, string $arquivo): string
    {
        $linhas = array_slice(preg_split('/\R/', $conteudo) ?: [], 0, 10);

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            if ($linha === '') {
                continue;
            }

            $titulo = trim((string) preg_replace('/^#+\s*/', '', $linha));

            if ($titulo !== '') {
                return mb_substr($titulo, 0, 200);
            }
        }

        return mb_substr(pathinfo($arquivo, PATHINFO_FILENAME), 0, 200);
    }

    /**
     * Similaridade de cosseno entre dois vetores.
     *
     * @param float[] $a
     * @param float[] $b
     */
    private static function similaridade(array $a, array $b): float
    {
        $total = min(count($a), count($b));

        if ($total === 0) {
            return 0.0;
        }

        $produto = 0.0;
        $normaA = 0.0;
        $normaB = 0.0;

        for ($i = 0; $i < $total; $i++) {
            $x = (float) $a[$i];
            $y = (float) $b[$i];

            $produto += $x * $y;
            $normaA += $x * $x;
            $normaB += $y * $y;
        }

        if ($normaA <= 0.0 || $normaB <= 0.0) {
            return 0.0;
        }

        return $produto / (sqrt($normaA) * sqrt($normaB));
    }
}
