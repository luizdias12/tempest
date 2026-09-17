-- ============================================================================
-- CHAT DA INTRANET (POC)
-- Tabelas: chat_conversas, chat_participantes, chat_mensagens
-- Observação: sem FOREIGN KEY real (InnoDB 8.0.46 apresentou bug de DD nesse
-- par de tabelas); a integridade é garantida na camada de aplicação.
-- ============================================================================

CREATE TABLE IF NOT EXISTS chat_conversas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo ENUM('direta','grupo') NOT NULL DEFAULT 'direta',
    titulo VARCHAR(255) NULL,
    criado_por VARCHAR(11) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_participantes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversa_id INT UNSIGNED NOT NULL,
    cpf VARCHAR(11) NOT NULL,
    ultimo_lido INT UNSIGNED NULL COMMENT 'ID da última mensagem lida pelo participante',
    limpo_em INT UNSIGNED NULL COMMENT 'ID a partir do qual o participante volta a ver mensagens (limpar p/ mim)',
    silenciado ENUM('S','N') NOT NULL DEFAULT 'N',
    apagado_em DATETIME NULL COMMENT 'Quando o participante saiu (apagar conversa p/ mim)',
    UNIQUE KEY uq_conversa_cpf (conversa_id, cpf),
    KEY idx_participante_cpf (cpf),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_mensagens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversa_id INT UNSIGNED NOT NULL,
    cpf VARCHAR(11) NOT NULL COMMENT 'CPF do remetente',
    texto TEXT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_msg_conversa (conversa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;