-- ============================================================================
-- TEMPEST — Dados de referência (seed) para montar uma base nova
-- Gerado a partir do banco de produção em 27/11/2026 14:26 (somente leitura).
-- Aplicar DEPOIS de database/schema_mysql.sql, informando o banco de destino:
--   mysql -u USUARIO -p NOME_DO_BANCO < database/seed_mysql.sql
--
-- Inclui apenas tabelas pequenas de referência:
--   doc_diretorio (6), doc_subdiretorio (50), carousel (13), messages (44).
-- NÃO inclui dados transacionais nem tabelas grandes (chat_*, doc_documento,
-- doc_versao, doc_permissao, doc_acesso, vivi_*, helpdesk_email_importado).
--
-- Observações:
--   - carousel.filename referencia arquivos em public/assets/caroussel/, que
--     é gitignored: copie as imagens separadamente para o carrossel funcionar.
--   - doc_diretorio não tem PII (criador_cpf nulo em todas as linhas).
--   - Os IDs originais são preservados; não há DROP nem TRUNCATE neste arquivo.
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- doc_diretorio: 6 linha(s)
-- ----------------------------------------------------------------------------
INSERT INTO `doc_diretorio` (`id`, `nome`, `visibilidade`, `criador_cpf`, `exibicao`, `ativo`) VALUES
(1, 'TI', 'publico', NULL, 'S', 1),
(4, 'VIDEOS', 'publico', NULL, 'S', 1),
(6, 'OPERACAO DE LOJA', 'publico', NULL, 'S', 1),
(11, 'ADMINISTRATIVO', 'publico', NULL, 'S', 1),
(12, 'RH', 'publico', NULL, 'S', 1),
(42, 'FATURAMENTO', 'publico', NULL, 'S', 1);

-- ----------------------------------------------------------------------------
-- doc_subdiretorio: 50 linha(s)
-- ----------------------------------------------------------------------------
INSERT INTO `doc_subdiretorio` (`id`, `id_dir`, `nome`, `exibicao`, `ativo`) VALUES
(1, 6, 'PADARIA', 'PADARIA', 1),
(2, 6, 'RM', 'RM', 1),
(3, 11, 'SEGURANCA', 'SEGURANCA', 1),
(4, 6, 'PERECIVEIS', 'PERECIVEIS', 1),
(5, 6, 'GERENCIA', 'GERENCIA', 1),
(6, 6, 'ACOUGUE', 'ACOUGUE', 1),
(7, 11, 'QUALIDADE DE ALIMENTOS', 'QUALIDADE DE ALIMENTOS', 1),
(8, 6, 'SALAO', 'SALAO', 1),
(9, 6, 'FRENTE DE CAIXA', 'FRENTE DE CAIXA', 1),
(10, 6, 'EXPEDICAO CALLCENTER', 'EXPEDICAO CALLCENTER', 1),
(11, 6, 'PREVENCAO DE PERDAS', 'PREVENCAO DE PERDAS', 1),
(12, 12, 'SEGURANCA DO TRABALHO', 'SEGURANCA DO TRABALHO', 1),
(13, 11, 'MANUTENCAO', 'MANUTENCAO', 1),
(14, 11, 'SND', 'SND', 1),
(15, 11, 'ECOMMERCE', 'ECOMMERCE', 1),
(16, 11, 'CENTRAL DE NOTAS', 'CENTRAL DE NOTAS', 1),
(17, 11, 'PROCESSOS', 'PROCESSOS', 1),
(18, 11, 'COMERCIAL', 'COMERCIAL', 1),
(19, 6, 'HORTIFRUTI', 'HORTIFRUTI', 1),
(20, 11, 'CENTRAL MONITORAMENTO', 'CENTRAL MONITORAMENTO', 1),
(21, 11, 'PREVENCAO DE PERDAS', 'PREVENCAO DE PERDAS', 1),
(22, 1, 'DESENVOLVIMENTO', 'DESENVOLVIMENTO', 1),
(23, 1, 'TOTVS', 'TOTVS', 1),
(24, 11, 'TI', 'TI', 1),
(25, 4, 'DESENVOLVIMENTO', 'DESENVOLVIMENTO', 1),
(59, 11, 'MARKETING', 'MARKETING', 1),
(60, 11, 'CONTROLADORIA', 'CONTROLADORIA', 1),
(61, 11, 'CONTABILIDADE', 'CONTABILIDADE', 1),
(62, 6, 'E-COMMERCE', 'E-COMMERCE', 1),
(63, 11, 'CADASTRO', 'CADASTRO', 1),
(64, 11, 'SEGURANCA DO TRABALHO', 'SEGURANCA DO TRABALHO', 1),
(65, 11, 'TESOURARIA', 'TESOURARIA', 1),
(66, 12, 'RECRUTAMENTO E SELECAO', 'RECRUTAMENTO E SELECAO', 1),
(67, 12, 'REMUNERACAO E BENEFICIOS', 'REMUNERACAO E BENEFICIOS', 1),
(69, 12, 'TREINAMENTO E DESENVOLVIMENTO', 'TREINAMENTO E DESENVOLVIMENTO', 1),
(70, 6, 'PLANOGRAMA', 'PLANOGRAMA', 1),
(71, 11, 'ADMINISTRACAO FINANCEIRA', 'ADMINISTRACAO FINANCEIRA', 1),
(72, 11, 'CREDITO', 'CREDITO', 1),
(74, 11, 'PERECIVEIS', 'PERECIVEIS', 1),
(75, 11, 'ENGENHARIA', 'ENGENHARIA', 1),
(76, 11, 'DEPARTAMENTO PESSOAL', 'DEPARTAMENTO PESSOAL', 1),
(77, 11, 'ALMOXARIFADO', 'ALMOXARIFADO', 1),
(78, 11, 'CONTAS A RECEBER', 'CONTAS A RECEBER', 1),
(79, 11, 'LOGISTICA', 'LOGISTICA', 1),
(83, 6, 'SERVICOS GERAIS', 'SERVICOS GERAIS', 1),
(84, 12, 'MATERIAIS TREINAMENTOS ONLINE', 'MATERIAIS TREINAMENTOS ONLINE', 1),
(86, 6, 'SND', 'SND', 1),
(87, 1, 'TESTE', 'TESTE', 1),
(88, 6, 'PROMOTOR', 'PROMOTOR', 1),
(90, 42, 'FISCAL', 'FISCAL', 1);

-- ----------------------------------------------------------------------------
-- carousel: 13 linha(s)
-- ----------------------------------------------------------------------------
INSERT INTO `carousel` (`id`, `filename`, `ord`, `dtinicio`, `dtfim`, `ativo`, `link`) VALUES
(336, 'e170a0fb9de32c.png', 14, '2026-11-10', '2026-11-30', 'S', NULL),
(337, 'deb8bee01981e3.png', 15, '2026-11-10', '2026-11-30', 'S', NULL),
(338, '69694d946f9035.png', 16, '2026-11-10', '2026-11-30', 'S', NULL),
(339, '388254f07223c9.png', 17, '2026-11-10', '2026-11-30', 'S', NULL),
(340, 'fff7fdc2f5be09.png', 18, '2026-11-10', '2026-11-30', 'S', NULL),
(341, '4ce0dca9e4ca22.jpg', 12, '2026-11-19', '2026-12-10', 'S', NULL),
(342, 'e7e1e1703914dc.png', 13, '2026-11-30', '2026-12-05', 'S', NULL),
(343, 'a932838cb487ae.png', 6, '2026-11-26', '2026-11-30', 'S', NULL),
(344, 'a5461e2ab99552.png', 7, '2026-11-26', '2026-11-30', 'S', NULL),
(345, 'b2b79d8e570dc5.png', 8, '2026-11-26', '2026-11-30', 'S', NULL),
(346, '0281481334d0e1.png', 9, '2026-11-26', '2026-11-30', 'S', NULL),
(347, 'd1d32898f515c5.png', 10, '2026-11-26', '2026-11-30', 'S', NULL),
(348, 'dc73949a2b8324.png', 11, '2026-11-26', '2026-11-30', 'S', NULL);

-- ----------------------------------------------------------------------------
-- messages: 44 linha(s)
-- ----------------------------------------------------------------------------
INSERT INTO `messages` (`id`, `mensagem`) VALUES
(1, 'Não importa que você vá devagar, contanto que você não pare.'),
(2, 'Comece onde você está, use o que você tem e faça o que você pode.'),
(3, 'Tudo o que um sonho precisa para ser realizado é alguém que acredite que ele possa ser realizado.'),
(4, 'Devíamos ser ensinados a não esperar por inspiração para começar algo. Ação sempre gera inspiração. Inspiração raramente gera ação.'),
(5, 'Coragem é saber o que não temer.'),
(6, 'Conhecer a si mesmo é o começo de toda sabedoria.'),
(7, 'Descubra quem é você, e seja essa pessoa. A sua alma foi colocada nesse mundo para ser isso, então viva essa verdade e todo resto virá.'),
(8, 'Acredite em milagres, mas não dependa deles.'),
(9, 'Não é a carga que o derruba, mas a maneira como você a carrega.'),
(10, 'Não existe nada de completamente errado no mundo, mesmo um relógio parado, consegue estar certo duas vezes por dia.'),
(11, 'O otimismo é a fé daquele que conduz à realização; nada pode ser feito sem esperança.'),
(12, 'A felicidade não é algo pronto. Ela é feita das suas próprias ações.'),
(13, 'Nossa maior fraqueza está em desistir. O caminho mais certo de vencer é tentar mais uma vez.'),
(14, 'Mude o modo que você olha para as coisas, e as coisas que você olha mudarão.'),
(15, 'Sábio é aquele que conhece os limites da própria ignorância.'),
(16, 'Qualquer pessoa de sucesso sabe que é uma peça importante, mas sabe que não conseguirá nada sozinho.'),
(17, 'Se a montanha que você está subindo parece cada vez mais imponente é sinal que você está mais próximo ao topo.'),
(18, 'Se você cansar, aprenda a descansar e não a desistir.'),
(19, 'Sempre que se deparar com um obstáculo, encare-o como uma oportunidade.'),
(20, 'Para chegar onde a maioria não chega, é necessário fazer o que a maioria não faz.'),
(21, 'Você nunca sabe que resultados virão da sua ação. Mas se você não fizer nada, não existirão resultados.'),
(22, 'Se é difícil, é porque vale a pena.'),
(23, 'Não tenha medo de errar, mas sim de não tentar.'),
(24, 'Para realizar coisas grandes, comece pequeno.'),
(25, 'Seja mais forte do que a sua melhor desculpa.'),
(26, 'Às vezes, mais tarde torna-se nunca. Faça isso agora.'),
(27, 'O sucesso não vai simplesmente te encontrar. Você precisa sair da zona de conforto e ir atrás dele.'),
(28, 'Não permita que uma derrota desvie seus passos do caminho até ao sucesso.'),
(29, 'Em mentes quadradas ideias novas não circulam.'),
(30, 'O sucesso é a soma de pequenos esforços repetidos dia após dia.'),
(31, 'Que o nosso cansaço não vença as nossas metas.'),
(32, 'Você só deve tentar ser melhor do que uma pessoa: quem você era ontem.'),
(33, 'Perca menos tempo lamentando os fracassos do passado e mais planejando os sucessos do futuro.'),
(34, 'Quer? Então faça acontecer, porque a única coisa que cai do céu é a chuva.'),
(35, 'Seja tão bom que as outras pessoas não possam ignorar você.'),
(36, 'Saber esperar também é importante. Por isso, semeie aos poucos o sucesso de amanhã.'),
(37, 'Acredite, você tem forças para chegar onde quiser. Basta querer.'),
(38, 'Lembre-se: o sucesso é uma jornada, não um destino. Tenha fé em sua capacidade.'),
(39, 'Não desista, pois se ainda existir vontade é sinal que está no caminho certo para o sucesso.'),
(40, 'Nós não temos a eternidade, então aproveite ao máximo o seu tempo. Às vezes, quando se arrisca é que se consegue as recompensas mais incríveis.'),
(41, 'Não se leve tão à sério, no fim das contas você vai preferir lembrar dos seus sorrisos.'),
(42, 'Espalhe coisas boas porque a gente merece e o mundo precisa.'),
(43, 'Faça a tristeza de confete, jogue para o alto e deixe o vento levar.'),
(44, 'Pensar é o trabalho mais difícil que existe. Talvez por isso tão poucos se dediquem a ele.');
