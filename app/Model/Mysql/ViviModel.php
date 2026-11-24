<?php

namespace App\Model\Mysql;

use App\Core\DB;
use App\Core\Logger;
use Throwable;

class ViviModel
{
    private const PALAVRAS_IGNORADAS = [
        'como', 'onde', 'qual', 'quais', 'quando', 'porque', 'para', 'sobre',
        'esse', 'essa', 'isso', 'este', 'esta', 'meu', 'minha', 'seu', 'sua',
        'dos', 'das', 'com', 'sem', 'que', 'nao', 'não', 'mais', 'muito',
        'pode', 'posso', 'fazer', 'faço', 'faco', 'tenho', 'preciso',
        'gostaria', 'favor', 'ajuda', 'informacao', 'informação',
        'informacoes', 'informações', 'funciona', 'funcionam', 'quem',
        'tem', 'ter', 'sao', 'são', 'está', 'esta', 'aqui', 'ali', 'por',
        'tudo', 'bem', 'bom', 'boa', 'dia', 'tarde', 'noite', 'hoje', 'agora',
        'ola', 'olá', 'oi', 'obrigado', 'obrigada', 'voce', 'você', 'voces',
        'vocês', 'senhor', 'senhora', 'quero', 'queria', 'saber', 'poderia',
        'consegue', 'conseguir', 'ajudar', 'necessito', 'necessario',
        'necessário', 'existe', 'existem', 'algum', 'alguma', 'alguns',
        'algumas', 'certo', 'certa',
    ];

    /**
     * Extrai os termos significativos de uma pergunta para busca restrita.
     *
     * @return string[]
     */
    public static function termosRelevantes(string $texto): array
    {
        $palavras = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($texto)) ?: [];
        $palavras = array_values(array_unique(array_filter(
            $palavras,
            static fn(string $palavra): bool => mb_strlen($palavra) >= 4
                && !in_array($palavra, self::PALAVRAS_IGNORADAS, true)
        )));

        return array_slice($palavras, 0, 6);
    }

    /**
     * Monta o filtro de visibilidade dos trechos.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private static function filtroAcesso(bool $somentePublico, ?int $codfuncaoPai): array
    {
        if ($somentePublico) {
            return [' AND publico = 1', []];
        }

        $sql = ' AND (publico = 1 OR acesso_geral = 1 OR permissoes IS NULL';
        $parametros = [];

        if ($codfuncaoPai !== null) {
            $sql .= ' OR FIND_IN_SET(:codfuncao_pai, permissoes) > 0';
            $parametros['codfuncao_pai'] = (string) $codfuncaoPai;
        }

        return [$sql . ')', $parametros];
    }

    public static function contar(bool $somentePublico = false): int
    {
        $filtro = $somentePublico ? ' WHERE publico = 1' : '';
        $row = DB::first("SELECT COUNT(*) AS total FROM vivi_conhecimento{$filtro}", [], 'mysql');

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Busca por texto: FULLTEXT (natural language) com fallback para LIKE.
     *
     * @return array<int, array{id: int, titulo: string, fonte: string, conteudo: string, score?: float}>
     */
    public static function buscarTexto(
        string $termo,
        int $limite,
        bool $somentePublico,
        ?int $codfuncaoPai = null
    ): array {
        $limite = max(1, min(20, $limite));
        [$filtro, $filtroParametros] = self::filtroAcesso($somentePublico, $codfuncaoPai);
        $parametros = $filtroParametros;
        $parametros['termo'] = $termo;
        $parametros['termo2'] = $termo;

        try {
            $rows = DB::select(
                "SELECT id, titulo, fonte, conteudo,
                        MATCH(titulo, conteudo) AGAINST (:termo IN NATURAL LANGUAGE MODE) AS score
                 FROM vivi_conhecimento
                 WHERE MATCH(titulo, conteudo) AGAINST (:termo2 IN NATURAL LANGUAGE MODE){$filtro}
                 ORDER BY score DESC
                 LIMIT {$limite}",
                $parametros,
                'mysql'
            );

            if (!empty($rows)) {
                return $rows;
            }
        } catch (Throwable $e) {
            Logger::warning('Vivi: busca FULLTEXT indisponível, tentando LIKE', ['erro' => $e->getMessage()]);
        }

        $palavras = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($termo)) ?: [];
        $palavras = array_values(array_unique(array_filter(
            $palavras,
            static fn(string $palavra): bool => mb_strlen($palavra) >= 4
        )));
        $palavras = array_slice($palavras, 0, 6);

        if (empty($palavras)) {
            return [];
        }

        $condicoes = [];
        $parametros = $filtroParametros;

        foreach ($palavras as $indice => $palavra) {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $palavra) . '%';
            $condicoes[] = "(titulo LIKE :titulo{$indice} OR conteudo LIKE :conteudo{$indice})";
            $parametros["titulo{$indice}"] = $like;
            $parametros["conteudo{$indice}"] = $like;
        }

        return DB::select(
            'SELECT id, titulo, fonte, conteudo, 0 AS score
             FROM vivi_conhecimento
             WHERE (' . implode(' OR ', $condicoes) . "){$filtro}
             LIMIT {$limite}",
            $parametros,
            'mysql'
        );
    }

    /**
     * Verifica se há trechos restritos relacionados ao termo, sem devolver conteúdo.
     * Usado para orientar login/permissão em vez de dizer que nada foi encontrado.
     */
    public static function existeConteudoRestrito(
        string $termo,
        bool $somentePublico,
        ?int $codfuncaoPai = null
    ): bool {
        $termo = trim($termo);

        if ($termo === '') {
            return false;
        }

        if ($somentePublico) {
            $restricao = 'publico = 0';
            $parametrosBase = [];
        } else {
            $restricao = 'publico = 0 AND acesso_geral = 0 AND permissoes IS NOT NULL';
            $parametrosBase = [];

            if ($codfuncaoPai !== null) {
                $restricao .= ' AND FIND_IN_SET(:codfuncao_pai, permissoes) = 0';
                $parametrosBase['codfuncao_pai'] = (string) $codfuncaoPai;
            }
        }

        $palavras = self::termosRelevantes($termo);

        if (empty($palavras)) {
            return false;
        }

        $parametros = $parametrosBase;
        $parametros['termo'] = '+' . implode(' +', $palavras);

        try {
            $encontrado = DB::first(
                "SELECT 1 AS encontrado
                 FROM vivi_conhecimento
                 WHERE MATCH(titulo, conteudo) AGAINST (:termo IN BOOLEAN MODE)
                   AND {$restricao}
                 LIMIT 1",
                $parametros,
                'mysql'
            );

            if ($encontrado !== null) {
                return true;
            }
        } catch (Throwable $e) {
            Logger::warning('Vivi: checagem FULLTEXT de conteúdo restrito falhou, tentando LIKE', [
                'erro' => $e->getMessage(),
            ]);
        }

        $condicoes = [];
        $parametros = $parametrosBase;

        foreach ($palavras as $indice => $palavra) {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $palavra) . '%';
            $condicoes[] = "(titulo LIKE :titulo{$indice} OR conteudo LIKE :conteudo{$indice})";
            $parametros["titulo{$indice}"] = $like;
            $parametros["conteudo{$indice}"] = $like;
        }

        return DB::first(
            'SELECT 1 AS encontrado
             FROM vivi_conhecimento
             WHERE ' . implode(' AND ', $condicoes) . "
               AND {$restricao}
             LIMIT 1",
            $parametros,
            'mysql'
        ) !== null;
    }

    /**
     * @return array<int, array{id: int, titulo: string, fonte: string, conteudo: string, embedding: string}>
     */
    public static function listarComEmbedding(
        bool $somentePublico,
        int $limite = 3000,
        ?int $codfuncaoPai = null
    ): array {
        $limite = max(1, min(10000, $limite));
        [$filtro, $parametros] = self::filtroAcesso($somentePublico, $codfuncaoPai);

        return DB::select(
            "SELECT id, titulo, fonte, conteudo, embedding
             FROM vivi_conhecimento
             WHERE embedding IS NOT NULL AND embedding <> ''{$filtro}
             ORDER BY id DESC
             LIMIT {$limite}",
            $parametros,
            'mysql'
        );
    }

    /**
     * @return string[]
     */
    public static function hashesPorFonte(string $fonte): array
    {
        $linhas = DB::select(
            'SELECT hash_conteudo FROM vivi_conhecimento WHERE fonte = :fonte',
            ['fonte' => $fonte],
            'mysql'
        );

        return array_map(static fn(array $linha): string => (string) $linha['hash_conteudo'], $linhas);
    }

    public static function publicoDivergente(string $fonte, bool $publico): bool
    {
        $linha = DB::first(
            'SELECT COUNT(*) AS total FROM vivi_conhecimento
             WHERE fonte = :fonte AND publico <> :publico',
            ['fonte' => $fonte, 'publico' => $publico ? 1 : 0],
            'mysql'
        );

        return (int) ($linha['total'] ?? 0) > 0;
    }

    public static function inserir(
        string $fonte,
        string $titulo,
        string $conteudo,
        string $hash,
        bool $publico,
        ?string $embedding = null,
        bool $acessoGeral = false,
        ?string $permissoes = null
    ): ?int {
        return DB::insert('vivi_conhecimento', [
            'fonte' => mb_substr($fonte, 0, 191),
            'titulo' => mb_substr($titulo, 0, 255),
            'conteudo' => $conteudo,
            'hash_conteudo' => $hash,
            'publico' => $publico ? 1 : 0,
            'acesso_geral' => $acessoGeral ? 1 : 0,
            'permissoes' => $permissoes,
            'embedding' => $embedding,
        ], 'mysql');
    }

    public static function existeFonte(string $fonte): bool
    {
        return DB::first(
            'SELECT 1 AS encontrado FROM vivi_conhecimento WHERE fonte = :fonte LIMIT 1',
            ['fonte' => $fonte],
            'mysql'
        ) !== null;
    }

    /**
     * @return array{titulo: string, acesso_geral: int, permissoes: ?string}|null
     */
    public static function metadadosPorFonte(string $fonte): ?array
    {
        return DB::first(
            'SELECT titulo, acesso_geral, permissoes
             FROM vivi_conhecimento
             WHERE fonte = :fonte
             LIMIT 1',
            ['fonte' => $fonte],
            'mysql'
        );
    }

    public static function atualizarAcessoPorFonte(
        string $fonte,
        bool $acessoGeral,
        ?string $permissoes
    ): int {
        $stmt = DB::connect('mysql')->prepare(
            'UPDATE vivi_conhecimento
             SET acesso_geral = ?, permissoes = ?
             WHERE fonte = ?'
        );
        $stmt->execute([$acessoGeral ? 1 : 0, $permissoes, $fonte]);

        return $stmt->rowCount();
    }

    /**
     * @return string[]
     */
    public static function fontesComPrefixo(string $prefixo): array
    {
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prefixo) . '%';
        $linhas = DB::select(
            'SELECT DISTINCT fonte FROM vivi_conhecimento WHERE fonte LIKE :prefixo',
            ['prefixo' => $like],
            'mysql'
        );

        return array_map(static fn(array $linha): string => (string) $linha['fonte'], $linhas);
    }

    public static function deletarPorFontePrefixo(string $prefixo, ?string $exceto = null): int
    {
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prefixo) . '%';
        $sql = 'DELETE FROM vivi_conhecimento WHERE fonte LIKE :prefixo';
        $parametros = ['prefixo' => $like];

        if ($exceto !== null) {
            $sql .= ' AND fonte <> :exceto';
            $parametros['exceto'] = $exceto;
        }

        $stmt = DB::connect('mysql')->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->rowCount();
    }

    public static function deletarPorFonte(string $fonte): int
    {
        $stmt = DB::connect('mysql')->prepare('DELETE FROM vivi_conhecimento WHERE fonte = ?');
        $stmt->execute([$fonte]);

        return $stmt->rowCount();
    }

    public static function limpar(): int
    {
        return (int) DB::connect('mysql')->exec('DELETE FROM vivi_conhecimento');
    }

    /**
     * Registra feedback explícito do usuário ou uma pergunta sem contexto recuperado.
     *
     * @param array{tipo: string, pergunta: string, resposta: ?string, publico: bool, codfuncaopai: ?int, ip_hash: string, sessao_hash: string} $dados
     */
    public static function registrarFeedback(array $dados): void
    {
        DB::insert('vivi_feedback', [
            'tipo' => $dados['tipo'],
            'pergunta' => mb_substr((string) $dados['pergunta'], 0, 1000),
            'resposta' => $dados['resposta'],
            'publico' => !empty($dados['publico']) ? 1 : 0,
            'codfuncaopai' => $dados['codfuncaopai'],
            'ip_hash' => $dados['ip_hash'],
            'sessao_hash' => $dados['sessao_hash'],
        ], 'mysql');
    }

    /**
     * Consome uma unidade do limite em janela fixa. Retorna false se estourou.
     */
    public static function consumirLimite(string $chave, int $limite, int $janelaSegundos): bool
    {
        $limite = max(1, $limite);
        $janelaSegundos = max(1, $janelaSegundos);
        $conn = DB::connect('mysql');

        $stmt = $conn->prepare(
            'SELECT TIMESTAMPDIFF(SECOND, janela_inicio, NOW()) AS decorrido, contagem
             FROM vivi_rate_limit WHERE chave = ? LIMIT 1'
        );
        $stmt->execute([$chave]);
        $linha = $stmt->fetch() ?: null;

        $expirada = $linha === null || (int) $linha['decorrido'] >= $janelaSegundos;

        if ($expirada) {
            $conn->prepare(
                'INSERT INTO vivi_rate_limit (chave, janela_inicio, contagem)
                 VALUES (?, NOW(), 1)
                 ON DUPLICATE KEY UPDATE janela_inicio = NOW(), contagem = 1'
            )->execute([$chave]);

            return true;
        }

        if ((int) $linha['contagem'] >= $limite) {
            return false;
        }

        $conn->prepare('UPDATE vivi_rate_limit SET contagem = contagem + 1 WHERE chave = ?')->execute([$chave]);

        if (random_int(1, 200) === 1) {
            $conn->exec('DELETE FROM vivi_rate_limit WHERE janela_inicio < NOW() - INTERVAL 2 DAY');
        }

        return true;
    }
}
