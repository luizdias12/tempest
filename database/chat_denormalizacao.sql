-- ============================================================================
-- CHAT: denormalização da última mensagem + cache de "digitando"
-- Aplicar UMA VEZ no MySQL ANTES de subir o código novo (o ChatModel passa a
-- ler chat_conversas.ultima_mensagem_id e a tabela chat_digitando).
-- ============================================================================

ALTER TABLE chat_conversas
    ADD COLUMN ultima_mensagem_id INT UNSIGNED NULL
        COMMENT 'Denormalização: última mensagem da conversa' AFTER titulo,
    ADD KEY idx_conversa_ultima (ultima_mensagem_id);

-- Backfill: última mensagem existente de cada conversa.
UPDATE chat_conversas c
SET c.ultima_mensagem_id = (
    SELECT MAX(m.id)
    FROM chat_mensagens m
    WHERE m.conversa_id = c.id
);

-- "Digitando" deixa de ser evento persistido e passa a ter TTL neste cache.
CREATE TABLE IF NOT EXISTS chat_digitando (
    conversa_id INT UNSIGNED NOT NULL,
    cpf VARCHAR(11) NOT NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (conversa_id, cpf),
    KEY idx_digitando_atualizado (atualizado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice para o job de retenção (DELETE ... WHERE criado_em < ...).
ALTER TABLE chat_eventos
    ADD KEY idx_evento_criado (criado_em);
