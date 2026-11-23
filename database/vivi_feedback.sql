-- ============================================================================
-- VIVI — tabela de feedback
-- Execute em bases já existentes para habilitar:
--   - avaliação explícita das respostas (útil / não útil);
--   - registro automático de perguntas sem contexto recuperado (lacunas).
-- ============================================================================

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
