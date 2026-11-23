-- ============================================================================
-- VIVI (assistente virtual da intranet)
-- Tabelas: vivi_conhecimento, vivi_rate_limit, vivi_feedback
-- Observação: base de conhecimento por trechos (chunks). O embedding é
-- opcional (JSON do vetor); sem ele a busca usa FULLTEXT em pt-BR.
-- ============================================================================

CREATE TABLE IF NOT EXISTS vivi_conhecimento (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fonte VARCHAR(191) NOT NULL COMMENT 'Origem do trecho (arquivo, URL, ticket)',
    titulo VARCHAR(255) NOT NULL,
    conteudo MEDIUMTEXT NOT NULL,
    hash_conteudo CHAR(64) NOT NULL COMMENT 'SHA-256 do trecho para indexação incremental',
    publico TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = pode ser usado por visitantes não autenticados',
    acesso_geral TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = visível a qualquer usuário autenticado',
    permissoes TEXT NULL COMMENT 'Códigos de função pai permitidos, separados por vírgula; NULL = conteúdo antigo (qualquer autenticado)',
    embedding MEDIUMTEXT NULL COMMENT 'Vetor JSON gerado pelo modelo de embeddings',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fonte_hash (fonte, hash_conteudo),
    KEY idx_publico (publico),
    KEY idx_acesso_geral (acesso_geral),
    FULLTEXT KEY ft_busca (titulo, conteudo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vivi_rate_limit (
    chave VARCHAR(64) NOT NULL COMMENT 'SHA-256 da chave de controle (IP + sessão)',
    janela_inicio DATETIME NOT NULL,
    contagem INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vivi_feedback (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo ENUM('util', 'inutil', 'sem_contexto') NOT NULL COMMENT 'util/inutil = avaliação explícita; sem_contexto = pergunta sem trechos recuperados',
    pergunta VARCHAR(1000) NOT NULL,
    resposta MEDIUMTEXT NULL COMMENT 'Resposta avaliada (nula em sem_contexto)',
    publico TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = visitante não autenticado',
    codfuncaopai INT NULL COMMENT 'Função pai do usuário no momento (quando disponível)',
    ip_hash CHAR(64) NULL COMMENT 'SHA-256 do IP',
    sessao_hash CHAR(64) NULL COMMENT 'SHA-256 do identificador de sessão',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tipo_data (tipo, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
