-- ============================================================================
-- TEMPEST — Estrutura MySQL das tabelas do projeto
-- Gerado a partir do banco de produção em 27/11/2026 14:22 (somente leitura).
-- Contém APENAS estrutura (DDL): nenhum dado, nenhuma tabela do legado.
-- Escopo: chat_*, vivi_*, doc_*, carousel, messages, helpdesk_email_importado.
-- Fora do escopo: tabelas legadas (func, usuarios, helpdesk, sla etc.),
-- tbl_extrato_unimed, bases Oracle (RM/Consinco) e arquivos de public/files/.
--
-- Restauração em banco novo (o nome abaixo pode ser ajustado):
--   mysql -u USUARIO -p < database/schema_mysql.sql
-- ATENÇÃO: este arquivo não usa DROP nem IF NOT EXISTS. Em uma base que já
-- tenha essas tabelas ele falha na primeira tabela existente, sem apagar nada.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `Intranet` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `Intranet`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- chat_conversas (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `chat_conversas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo` enum('direta','grupo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'direta',
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ultima_mensagem_id` int unsigned DEFAULT NULL COMMENT 'Denormalização: última mensagem da conversa',
  `criado_por` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conversa_ultima` (`ultima_mensagem_id`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- chat_participantes (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `chat_participantes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `conversa_id` int unsigned NOT NULL,
  `cpf` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ultimo_lido` int unsigned DEFAULT NULL COMMENT 'ID da ultima mensagem lida pelo participante',
  `limpo_em` int unsigned DEFAULT NULL COMMENT 'ID a partir do qual o participante volta a ver mensagens (limpar p/ mim)',
  `silenciado` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `apagado_em` datetime DEFAULT NULL COMMENT 'Quando o participante saiu (apagar conversa p/ mim)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conversa_cpf` (`conversa_id`,`cpf`),
  KEY `idx_participante_cpf` (`cpf`)
) ENGINE=InnoDB AUTO_INCREMENT=223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- chat_mensagens (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `chat_mensagens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `conversa_id` int unsigned NOT NULL,
  `cpf` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CPF do remetente',
  `texto` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'texto',
  `anexo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anexo_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `editado_em` datetime DEFAULT NULL,
  `apagado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_msg_conversa` (`conversa_id`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=575 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- chat_reacoes (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `chat_reacoes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mensagem_id` bigint unsigned NOT NULL,
  `cpf` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emoji` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reacao` (`mensagem_id`,`cpf`,`emoji`),
  KEY `idx_reacao_mensagem` (`mensagem_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- chat_eventos (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `chat_eventos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversa_id` int unsigned NOT NULL,
  `evento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dados` json DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_evento_conversa` (`conversa_id`,`id`),
  KEY `idx_evento_criado` (`criado_em`)
) ENGINE=InnoDB AUTO_INCREMENT=3043 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- chat_digitando (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `chat_digitando` (
  `conversa_id` int unsigned NOT NULL,
  `cpf` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversa_id`,`cpf`),
  KEY `idx_digitando_atualizado` (`atualizado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- vivi_conhecimento (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `vivi_conhecimento` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `fonte` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Origem do trecho (arquivo, URL, ticket)',
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conteudo` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash_conteudo` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA-256 do trecho para indexação incremental',
  `publico` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = pode ser usado por visitantes não autenticados',
  `acesso_geral` tinyint(1) NOT NULL DEFAULT '0',
  `permissoes` text COLLATE utf8mb4_unicode_ci,
  `embedding` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'Vetor JSON gerado pelo modelo de embeddings',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fonte_hash` (`fonte`,`hash_conteudo`),
  KEY `idx_publico` (`publico`),
  KEY `idx_acesso_geral` (`acesso_geral`),
  FULLTEXT KEY `ft_busca` (`titulo`,`conteudo`)
) ENGINE=InnoDB AUTO_INCREMENT=10775 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- vivi_rate_limit (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `vivi_rate_limit` (
  `chave` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA-256 da chave de controle (IP + sessão)',
  `janela_inicio` datetime NOT NULL,
  `contagem` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- vivi_feedback (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `vivi_feedback` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo` enum('util','inutil','sem_contexto') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'util/inutil = avaliação explícita; sem_contexto = pergunta sem trechos recuperados',
  `pergunta` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resposta` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'Resposta avaliada (nula em sem_contexto)',
  `publico` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = visitante não autenticado',
  `codfuncaopai` int DEFAULT NULL COMMENT 'Função pai do usuário no momento (quando disponível)',
  `ip_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA-256 do IP',
  `sessao_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA-256 do identificador de sessão',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo_data` (`tipo`,`criado_em`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- doc_diretorio (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `doc_diretorio` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visibilidade` enum('publico','restrito') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'publico',
  `criador_cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exibicao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- doc_subdiretorio (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `doc_subdiretorio` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_dir` int unsigned NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exibicao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_subdir_dir` (`id_dir`),
  CONSTRAINT `fk_subdir_dir` FOREIGN KEY (`id_dir`) REFERENCES `doc_diretorio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- doc_documento (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `doc_documento` (
  `id_doc` int unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_dir` int unsigned NOT NULL,
  `id_subdir` int unsigned NOT NULL,
  `tipo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `geral` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `datacad` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dt_alteracao` datetime DEFAULT NULL,
  `uploader` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `id_versao_atual` int unsigned DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_doc`),
  KEY `idx_doc_dir` (`id_dir`,`id_subdir`),
  KEY `idx_doc_uploader` (`uploader`),
  KEY `fk_doc_subdir` (`id_subdir`),
  KEY `fk_doc_versao_atual` (`id_versao_atual`),
  CONSTRAINT `fk_doc_dir` FOREIGN KEY (`id_dir`) REFERENCES `doc_diretorio` (`id`),
  CONSTRAINT `fk_doc_subdir` FOREIGN KEY (`id_subdir`) REFERENCES `doc_subdiretorio` (`id`),
  CONSTRAINT `fk_doc_uploader` FOREIGN KEY (`uploader`) REFERENCES `usuarios` (`cpf`) ON DELETE SET NULL,
  CONSTRAINT `fk_doc_versao_atual` FOREIGN KEY (`id_versao_atual`) REFERENCES `doc_versao` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=632 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- doc_versao (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `doc_versao` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_doc` int unsigned NOT NULL,
  `versao` int NOT NULL,
  `rotulo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caminho` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho` bigint NOT NULL DEFAULT '0',
  `dt_upload` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uploader` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_versao_doc` (`id_doc`,`versao`),
  KEY `idx_versao_uploader` (`uploader`),
  CONSTRAINT `fk_versao_doc` FOREIGN KEY (`id_doc`) REFERENCES `doc_documento` (`id_doc`) ON DELETE CASCADE,
  CONSTRAINT `fk_versao_uploader` FOREIGN KEY (`uploader`) REFERENCES `usuarios` (`cpf`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- doc_permissao (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `doc_permissao` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_doc` int unsigned NOT NULL,
  `codfuncao` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_perm_doc_funcao` (`id_doc`,`codfuncao`),
  CONSTRAINT `fk_perm_doc` FOREIGN KEY (`id_doc`) REFERENCES `doc_documento` (`id_doc`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34308 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- doc_acesso (InnoDB, utf8mb4_unicode_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `doc_acesso` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `id_doc` int unsigned NOT NULL,
  `id_versao` int unsigned NOT NULL,
  `dt_view` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_acesso_cpf_versao` (`cpf`,`id_doc`,`id_versao`),
  KEY `idx_acesso_doc` (`id_doc`),
  KEY `idx_acesso_cpf` (`cpf`),
  KEY `fk_acesso_versao` (`id_versao`),
  CONSTRAINT `fk_acesso_cpf` FOREIGN KEY (`cpf`) REFERENCES `usuarios` (`cpf`) ON DELETE CASCADE,
  CONSTRAINT `fk_acesso_doc` FOREIGN KEY (`id_doc`) REFERENCES `doc_documento` (`id_doc`) ON DELETE CASCADE,
  CONSTRAINT `fk_acesso_versao` FOREIGN KEY (`id_versao`) REFERENCES `doc_versao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39193 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- carousel (InnoDB, utf8mb3_general_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `carousel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `ord` int DEFAULT NULL,
  `dtinicio` date DEFAULT NULL,
  `dtfim` date DEFAULT NULL,
  `ativo` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=349 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

-- ----------------------------------------------------------------------------
-- messages (InnoDB, utf8mb3_general_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mensagem` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

-- ----------------------------------------------------------------------------
-- helpdesk_email_importado (InnoDB, utf8mb4_0900_ai_ci)
-- ----------------------------------------------------------------------------
CREATE TABLE `helpdesk_email_importado` (
  `message_id` varchar(200) NOT NULL,
  `help_id` int NOT NULL,
  `conversation_id` varchar(200) DEFAULT NULL,
  `importado_em` datetime NOT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
