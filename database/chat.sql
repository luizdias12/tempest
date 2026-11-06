-- ============================================================================
-- CHAT DA INTRANET
-- Tabelas: chat_conversas, chat_participantes, chat_mensagens,
--          chat_reacoes, chat_eventos
-- Observação: sem FOREIGN KEY real (InnoDB 8.0.46 apresentou bug de DD nesse
-- caso); a integridade é garantida na camada de aplicação.
-- Observação: chat_mensagens.texto guarda a mensagem criptografada
-- (AES-256-GCM via App\Service\CryptoService), nunca o texto puro.
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
    ultimo_lido INT UNSIGNED NULL,
    limpo_em INT UNSIGNED NULL,
    silenciado ENUM('S','N') NOT NULL DEFAULT 'N',
    apagado_em DATETIME NULL,
    UNIQUE KEY uq_conversa_cpf (conversa_id, cpf),
    KEY idx_participante_cpf (cpf),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_mensagens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversa_id INT UNSIGNED NOT NULL,
    cpf VARCHAR(11) NOT NULL COMMENT 'CPF do remetente',
    texto TEXT NOT NULL COMMENT 'Mensagem criptografada (AES-256-GCM)',
    tipo ENUM('texto','imagem','arquivo') NOT NULL DEFAULT 'texto',
    anexo VARCHAR(255) NULL COMMENT 'Caminho relativo do anexo',
    anexo_nome VARCHAR(255) NULL COMMENT 'Nome original do arquivo',
    editado_em DATETIME NULL,
    apagado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_msg_conversa (conversa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_reacoes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mensagem_id INT UNSIGNED NOT NULL,
    cpf VARCHAR(11) NOT NULL,
    emoji VARCHAR(20) NOT NULL,
    UNIQUE KEY uq_reacao_mensagem (mensagem_id, cpf, emoji),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_eventos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversa_id INT UNSIGNED NOT NULL,
    evento VARCHAR(30) NOT NULL,
    dados TEXT NULL COMMENT 'Payload do evento em JSON',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_evento_conversa (conversa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;