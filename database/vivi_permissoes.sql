-- ============================================================================
-- VIVI — controle de acesso por função (bases já existentes)
-- Execute uma única vez em bancos criados antes da indexação de documentos.
-- ============================================================================

ALTER TABLE vivi_conhecimento
    ADD COLUMN acesso_geral TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = visível a qualquer usuário autenticado' AFTER publico,
    ADD COLUMN permissoes TEXT NULL
        COMMENT 'Códigos de função pai permitidos, separados por vírgula; NULL = conteúdo antigo (qualquer autenticado)'
        AFTER acesso_geral,
    ADD KEY idx_acesso_geral (acesso_geral);
