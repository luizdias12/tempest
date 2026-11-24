# Vivi — Assistente Virtual da Intranet

Documentação de uso, operação e manutenção do chatbot **Vivi** do projeto Tempest.

- **Público-alvo:** usuários da intranet, suporte de TI e desenvolvedores.
- **Última atualização:** 23/11/2026.

---

## 1. Visão geral

A Vivi é um assistente virtual que responde perguntas em português do Brasil usando uma
**base de conhecimento interna** (RAG). O widget fica disponível no canto inferior direito
de todas as páginas da intranet, inclusive em páginas públicas.

Principais características:

- Respostas baseadas apenas no conteúdo indexado da base interna.
- Quando não encontra a resposta, orienta abrir um chamado no Helpdesk ou falar com o TI.
- Visitantes não autenticados acessam somente trechos marcados como **públicos**.
- Rate limit por IP, por usuário/sessão e diário.
- Funciona com qualquer provedor compatível com a API OpenAI (Groq, Ollama, OpenRouter etc.).
- A conversa é mantida no navegador (sessionStorage) e sobrevive a recarregamentos de página.

### Fluxo da requisição

```text
Widget (vivi.js)
  → POST /vivi/chat (JSON)
    → ViviController
      → ViviService (validação, rate limit, histórico, prompt)
        → ViviKnowledgeService (busca no MySQL: embeddings ou FULLTEXT)
        → ViviAiService (chat no provedor OpenAI-compatible)
      ← resposta
  ← {"success":true,"data":{"reply":"..."}}
```

### Componentes

| Arquivo | Responsabilidade |
| --- | --- |
| `routes/web.php` | Rotas públicas `POST /vivi/chat`, `POST /vivi/feedback` e `GET /vivi/status`. |
| `app/Controller/ViviController.php` | Validação de Content-Type, envelope JSON, mapeamento de erros. |
| `app/Service/ViviService.php` | Regras de negócio, rate limit, sanitização e prompt do sistema. |
| `app/Service/ViviAiService.php` | Cliente HTTP do provedor de IA (chat e embeddings). |
| `app/Service/ViviKnowledgeService.php` | Indexação, chunking contextual, embeddings e recuperação de trechos. |
| `app/Service/ViviDadosService.php` | Consulta ao vivo de lojas/gerência, plantão comercial e contatos/ramais (dados públicos). |
| `app/Model/Mysql/ViviModel.php` | Consultas e gravações nas tabelas da Vivi. |
| `app/View/partials/vivi.php` | Markup do widget. |
| `public/js/vivi.js` | Comportamento do widget (envio, histórico, status, retry, feedback). |
| `public/css/vivi.css` | Estilo, responsividade e acessibilidade. |
| `public/img/vivi.jpg` | Avatar do widget. |
| `database/vivi.sql` | Criação das tabelas `vivi_conhecimento`, `vivi_rate_limit` e `vivi_feedback`. |
| `database/vivi_permissoes.sql` | Upgrade de bases antigas com `acesso_geral` e `permissoes`. |
| `database/vivi_feedback.sql` | Upgrade de bases antigas com a tabela `vivi_feedback`. |
| `resources/vivi/publico/*.md` | Conteúdo curado público (FAQ de navegação/processos), indexado como `curadoria:*`. |
| `cli/vivi_indexar.php` | Indexação/limpeza de arquivos `.md/.txt/.pdf/.docx`. |
| `cli/vivi_indexar_intranet.php` | Indexação dos documentos do módulo Documentos, com permissões. |
| `cli/vivi_indexar_curadoria.php` | Indexação da FAQ curada em `resources/vivi` (`publico/` = anônimos; demais = autenticados). |
| `cli/vivi_verificar_busca.php` | Diagnóstico somente leitura: mostra os trechos que a Vivi recuperaria. |
| `cli/vivi_verificar_gerencia.php` | Diagnóstico somente leitura da consulta de loja/gerente. |
| `cli/vivi_verificar_plantao.php` | Diagnóstico somente leitura da consulta de plantão comercial. |

---

## 2. Guia do usuário

### Abrir e fechar

- Clique no botão redondo da Vivi no canto inferior direito.
- Feche pelo **X** no cabeçalho ou pressione **Esc**.
- No celular, a janela ocupa a tela inteira.

### Conversa

- **Sugestões rápidas:** atalhos exibidos no início da conversa.
- **Nova conversa:** ícone de recarregar no cabeçalho limpa o histórico local.
- **Histórico:** fica no `sessionStorage` do navegador. Recarregar a página mantém a conversa;
  fechar a aba a descarta.
- O indicador do cabeçalho mostra **Online** ou **Indisponível** conforme `/vivi/status`.
- Em caso de falha, aparece o botão **Tentar novamente**.
- Sob cada resposta recém-recebida há os botões **útil** e **não ajudou**; o voto é opcional e
  anônimo para o usuário (IP e sessão só são gravados como hash).

### O que esperar das respostas

- A Vivi responde apenas com base no conteúdo indexado. Se não encontrar, informa que não
  localizou e orienta procurar o Helpdesk/TI.
- Ela não deve inventar prazos, links, números ou procedimentos.
- Respostas são limitadas a aproximadamente 2.000 caracteres.

### Privacidade e acesso

- **Visitantes não autenticados:** recebem apenas trechos marcados como públicos na indexação.
- **Usuários autenticados:** podem receber conteúdo interno privado.
- A chave do provedor de IA nunca é exposta ao navegador.
- Dúvidas e trechos enviados ao provedor externo saem do servidor. Com **Ollama local**,
  o conteúdo permanece na máquina.
- Perguntas e respostas avaliadas são gravadas em `vivi_feedback` para melhoria da base;
  IP e sessão são armazenados apenas como hash. Perguntas sem contexto recuperado também
  são registradas (mensagens com menos de 15 caracteres são ignoradas).

### Limites de uso (padrões)

| Limite | Padrão |
| --- | --- |
| Mensagens por minuto por IP | 60 |
| Mensagens por minuto por usuário/sessão | 12 |
| Mensagens por 24h por usuário/sessão | 300 |

Ao exceder, a Vivi pede para aguardar. O provedor de IA também pode aplicar limites próprios.

### Auto-refresh

Páginas com recarregamento automático (Helpdesk, TAF e eSocial) **não recarregam enquanto a
janela da Vivi está aberta**, do mesmo modo que fazem com modais abertos. Ao fechar o chat,
o ciclo normal de atualização continua.

---

## 3. Instalação e configuração

### Pré-requisitos

- PHP 8.2 com as extensões já usadas pelo projeto (`pdo_mysql`, `mbstring`, `zip`, etc.).
- MySQL 8 com suporte a `FULLTEXT`.
- Guzzle (já no `composer.lock`).
- Acesso à internet **ou** um Ollama local.
- `pdftotext` (pacote `poppler-utils`) para indexar os PDFs do módulo Documentos.
  DOCX é lido pela extensão `zip`; não é necessário LibreOffice.

### 1) Criar as tabelas

Execute `database/vivi.sql` no banco `Intranet`:

```bash
mysql -h HOST -u USUARIO -p Intranet < database/vivi.sql
```

O script é idempotente (`CREATE TABLE IF NOT EXISTS`) e cria:

- `vivi_conhecimento` — trechos indexados, com `FULLTEXT(titulo, conteudo)`.
- `vivi_rate_limit` — controle de limite por chave.
- `vivi_feedback` — avaliações (útil/não útil) e perguntas sem contexto.

> **Base já existente:** execute uma única vez `database/vivi_permissoes.sql` para adicionar
> `acesso_geral` e `permissoes`, usados no controle por função do módulo Documentos.
> Em bases criadas antes do feedback, execute também `database/vivi_feedback.sql`.

### 2) Configurar o provedor de IA

Copie as variáveis `VIVI_*` de `.env.example` para o `.env` e ajuste conforme o provedor.

**Groq (plano gratuito):**

```dotenv
VIVI_BASE_URL=https://api.groq.com/openai/v1
VIVI_API_KEY=gsk_...
VIVI_MODEL=openai/gpt-oss-20b
VIVI_EMBED_MODEL=
```

- Crie a chave em <https://console.groq.com/keys>.
- O Groq não oferece endpoint de embeddings; deixe `VIVI_EMBED_MODEL` vazio. A busca usa FULLTEXT.
- Limites do plano gratuito para `openai/gpt-oss-20b` (nov/2026): 30 req/min, 1.000 req/dia,
  8K tokens/min e 200K tokens/dia. Confirme os valores atuais no console.

**Ollama local (privado, sem custo por requisição):**

```dotenv
VIVI_BASE_URL=http://127.0.0.1:11434/v1
VIVI_API_KEY=ollama
VIVI_MODEL=qwen2.5:7b-instruct
VIVI_EMBED_MODEL=bge-m3
```

```bash
ollama pull qwen2.5:7b-instruct
ollama pull bge-m3
```

- Um modelo 7B exige cerca de 5 GB de RAM livre. Em servidores com ~8 GB, prefira
  `qwen2.5:3b-instruct` e um embedding menor (ex.: `nomic-embed-text`).
- O embedding é opcional; sem ele, a busca usa FULLTEXT.

**Outro provedor compatível (ex.: OpenRouter):**

```dotenv
VIVI_BASE_URL=https://openrouter.ai/api/v1
VIVI_API_KEY=sk-or-...
VIVI_MODEL=modelo:free
VIVI_EMBED_MODEL=
```

> A Vivi exige `VIVI_API_KEY` para provedores remotos. Endereços locais/privados
> (`localhost`, `127.0.0.1`, faixas privadas, `.local`, `.internal`) dispensam a chave.

### 3) Ajustar timeouts

- Cliente (navegador): 75 s.
- Servidor → provedor: `VIVI_TIMEOUT` (padrão 60 s).
- PHP-FPM/nginx: `request_terminate_timeout` e `fastcgi_read_timeout` devem ser **maiores que 75 s**.

### 4) Verificar

```bash
curl -s https://intranet/vivi/status
```

Resposta esperada:

```json
{"success":true,"message":null,"data":{"disponivel":true,"base_publica":false},"meta":{},"error":null}
```

- `disponivel`: provedor configurado (URL, modelo e chave quando necessário).
- `base_publica`: existe pelo menos um trecho público indexado.

### Variáveis de ambiente

| Variável | Padrão | Descrição |
| --- | --- | --- |
| `VIVI_BASE_URL` | — | URL base do provedor compatível com OpenAI. |
| `VIVI_API_KEY` | vazio | Chave do provedor; obrigatória para provedores remotos. |
| `VIVI_MODEL` | — | ID do modelo de chat. |
| `VIVI_EMBED_MODEL` | vazio | Modelo de embeddings; vazio usa FULLTEXT. |
| `VIVI_TIMEOUT` | `60` | Timeout (s) das chamadas de chat. |
| `VIVI_TOP_K` | `3` | Trechos recuperados por pergunta (1 a 10). |
| `VIVI_MAX_MSG` | `1000` | Tamanho máximo da mensagem do usuário (mín. 100). |
| `VIVI_MAX_TOKENS` | `350` | Máximo de tokens da resposta (mín. 50). |
| `VIVI_TEMPERATURA` | `0.2` | Temperatura do modelo. |
| `VIVI_MAX_CONTEXTO` | `3000` | Máximo de caracteres de contexto enviados ao modelo. |
| `VIVI_MAX_RESPOSTA` | `1500` | Truncamento da resposta final (mín. 200). |
| `VIVI_MAX_CHUNKS_BUSCA` | `1500` | Trechos considerados na busca vetorial (100 a 10.000). |
| `VIVI_SCORE_MIN` | `0.25` | Similaridade mínima para aceitar um trecho vetorial. |
| `VIVI_RATE_IP_MIN` | `60` | Limite por minuto por IP (mín. 10). |
| `VIVI_RATE_MIN` | `12` | Limite por minuto por usuário/sessão. |
| `VIVI_RATE_DIA` | `60` | Limite por 24 h por usuário/sessão para qualquer mensagem. |
| `VIVI_RATE_IA_DIA` | `120` | Teto global de 24 h para chamadas ao provedor de IA; consultas diretas não consomem. |

---

## 4. Base de conhecimento (RAG)

### Formatos aceitos

- `.md`, `.txt`, `.pdf` e `.docx`.
- Texto em UTF-8 (ISO-8859-1 é convertido automaticamente).
- PDFs usam `pdftotext`; DOCX é lido diretamente do XML interno.
- Pastas são lidas recursivamente.
- A `fonte` registrada é o caminho relativo à pasta indexada (ou o nome do arquivo, se for um
  arquivo isolado).
- PDFs digitalizados (imagem) podem não ter texto extraível; nesse caso o documento é indexado
  apenas com título/categoria e a Vivi orienta a consultar o original.

### Como o conteúdo é dividido

- Parágrafos são agrupados em trechos de até ~1.200 caracteres, com ~150 de sobreposição.
- **Chunking contextual:** cada trecho começa com um rótulo `[documento/categoria > seção]`,
  que melhora o FULLTEXT e os embeddings para perguntas curtas ou com sinônimos.
- Seções são detectadas por títulos markdown (`#`, `##`, ...) ou por linhas curtas em caixa alta
  (comuns em PDFs). Ao encontrar uma seção, o trecho em andamento é fechado.
- Trechos com menos de 10 caracteres são descartados.
- O título é extraído da primeira linha não vazia (com `#` removido) ou do nome do arquivo.
- Cada trecho recebe SHA-256 para indexação incremental.

> Ao mudar a **forma de chunking**, os hashes mudam. Rode uma vez
> `php cli/vivi_indexar_intranet.php --reindexar` (e `php cli/vivi_indexar.php <pasta> --reindexar`)
> para regravar os trechos com o novo formato.

### Visibilidade

| Origem | Comando | Quem acessa |
| --- | --- | --- |
| Arquivos privados (padrão) | `php cli/vivi_indexar.php /pasta` | Apenas usuários autenticados. |
| Arquivos públicos | `php cli/vivi_indexar.php /pasta --publico` | Todos, inclusive anônimos. |
| Curadoria em `resources/vivi/publico/` | `php cli/vivi_indexar_curadoria.php` | Todos, inclusive anônimos. |
| Curadoria fora de `publico/` | `php cli/vivi_indexar_curadoria.php` | Apenas usuários autenticados. |
| Documentos da intranet | `php cli/vivi_indexar_intranet.php` | Mesmas regras do módulo Documentos: `geral='S'` para qualquer usuário logado; restritos somente para as funções pai cadastradas em `doc_permissao`. |

> Use `--publico` somente em conteúdo que pode ser lido por qualquer visitante.
> A indexação da intranet **nunca** marca documentos como públicos; visitantes anônimos não
> recebem trechos de documentos, mesmo os marcados como gerais.

Quando a busca não encontra conteúdo acessível **relevante**, mas existe conteúdo restrito
relacionado, a Vivi não responde apenas “não encontrei”:

- **anônimo:** informa que a informação exige autenticação e orienta a fazer login em `/login`;
- **autenticado sem permissão:** informa que o conteúdo não está liberado para a função e
  orienta a entrar em contato com o setor de Gestão de Processos.

A checagem é apenas de existência: título, trecho e quantidade não são enviados ao modelo nem
exibidos ao usuário. Cada trecho acessível passa por um filtro individual de relevância (60% dos
termos significativos no texto, ou termo no título com cobertura quase total); trechos fracos não
são enviados ao modelo nem impedem a checagem de conteúdo restrito.

### Comandos

```bash
# Arquivos (.md/.txt/.pdf/.docx)
php cli/vivi_indexar.php /var/www/documentacao_interna
php cli/vivi_indexar.php /var/www/documentacao_interna --publico
php cli/vivi_indexar.php /var/www/documentacao_interna --reindexar

# Documentos do módulo Documentos (MySQL + arquivos em public/files/documentos)
php cli/vivi_indexar_intranet.php
php cli/vivi_indexar_intranet.php --reindexar
php cli/vivi_indexar_intranet.php --apenas-geral
php cli/vivi_indexar_intranet.php --limite=5
php cli/vivi_indexar_intranet.php --limpar

# Conteúdo curado (FAQ de navegação em resources/vivi)
php cli/vivi_indexar_curadoria.php
php cli/vivi_indexar_curadoria.php --reindexar
php cli/vivi_indexar_curadoria.php --limpar

# Verificar o que a busca recupera (somente leitura)
php cli/vivi_verificar_busca.php "Onde encontro os documentos internos?"
php cli/vivi_verificar_busca.php "Onde encontro os documentos internos?" --pai=19
php cli/vivi_verificar_busca.php "Onde encontro os documentos internos?" --publico

# Consultar loja/gerente (somente leitura, dados atuais do MySQL)
php cli/vivi_verificar_gerencia.php "Quem é o gerente da loja Betania?"
php cli/vivi_verificar_gerencia.php "e o subgerente?" --anterior="Quem é o gerente da loja Betania?"

# Consultar plantão comercial (somente leitura, dados atuais do MySQL)
php cli/vivi_verificar_plantao.php "Quem está de plantão hoje?"
php cli/vivi_verificar_plantao.php "e no próximo sábado?" --anterior="Quem está de plantão hoje?"

# Limpar toda a base, inclusive arquivos e documentos
php cli/vivi_indexar.php --limpar
```

- `--limpar` no `vivi_indexar_intranet.php` remove somente as fontes `intranet:doc:*`.
- `--apenas-geral` indexa apenas os documentos com `geral='S'` (útil para teste).
- A indexação ignora a categoria **TI / DESENVOLVIMENTO** (lista `CATEGORIAS_IGNORADAS` em
  `ViviKnowledgeService`). Esses documentos continuam no módulo Documentos, mas não alimentam a
  Vivi. A indexação completa (sem `--limite`) também remove trechos antigos dessas categorias;
  execuções parciais não fazem essa limpeza.
- `--limite=N` processa somente os N primeiros documentos (não remove documentos ausentes).
- A saída informa documentos, trechos, ignorados, sem texto, embeddings, removidos, erros e o
  modo de busca. Códigos de saída: `0` sucesso, `1` falha geral, `2` concluído com erros por
  documento.

### Indexação incremental

- Se o conjunto de hashes do arquivo for idêntico ao já gravado **e** o valor de `publico`
  não mudou, o arquivo é ignorado.
- Se mudou, os trechos antigos daquela `fonte` são removidos e os novos inseridos em transação.
- Alterar o `--publico` de uma pasta já indexada força a regravação.
- Documentos da intranet usam a fonte `intranet:doc:{id_doc}:v{id_versao}`. Uma nova versão
  gera uma fonte nova; a versão anterior é removida após a indexação bem-sucedida.
- Mudanças de permissão (`geral`/`doc_permissao`) são aplicadas mesmo sem nova versão, sem
  reextrair o arquivo.
- Documentos desativados são removidos da base em uma execução completa (sem `--limite` e sem
  `--apenas-geral`).

### Conteúdo curado (FAQ de navegação)

Documentos reais explicam processos, mas raramente explicam **como navegar na intranet**.
Para isso existe a curadoria em `resources/vivi`, versionada no repositório:

- Cada arquivo é indexado com a fonte `curadoria:{caminho}` (ex.:
  `curadoria:publico/faq_senha.md`).
- Arquivos dentro de `publico/` ficam visíveis a **todos os visitantes**, inclusive anônimos
  (`publico = 1` e `acesso_geral = 1`).
- Arquivos fora de `publico/` ficam visíveis apenas a **usuários autenticados**
  (`acesso_geral = 1`).
- Editar o arquivo e rodar `php cli/vivi_indexar_curadoria.php` atualiza os trechos; arquivos
  removidos da pasta têm os trechos apagados na próxima execução.
- O conteúdo usa títulos de seção (`##`), que viram o rótulo do trecho e ajudam o FULLTEXT.
- Perguntas de navegação devem ser respondidas por aqui, não por livros ou POPs indexados.

Para **verificar** o que a Vivi usaria em uma pergunta (sem chamar o modelo de IA):

```bash
php cli/vivi_verificar_busca.php "Onde encontro os documentos internos?"
php cli/vivi_verificar_busca.php "Onde encontro os documentos internos?" --pai=19
php cli/vivi_verificar_busca.php "Onde encontro os documentos internos?" --publico
```

A saída mostra fonte, score, título e início do trecho para cada resultado. Se o conteúdo
curado não aparecer nos primeiros lugares, ajuste as palavras do arquivo (use os termos que
as pessoas digitam) e reindexe. O `--pai=NN` simula um usuário com essa função pai; `--publico`
simula um visitante.

### Embeddings (opcional)

- Com `VIVI_EMBED_MODEL` preenchido, os trechos recebem vetores em lotes de 16.
- Na pergunta, o vetor é comparado por similaridade de cosseno com os trechos recentes.
- Se o provedor de embeddings falhar, a indexação continua e a busca cai para FULLTEXT.
- Groq não oferece embeddings; Ollama oferece (`bge-m3`, `nomic-embed-text` etc.).

### Recuperação

1. Se houver modelo de embedding, tenta busca vetorial com `VIVI_SCORE_MIN` e `VIVI_TOP_K`.
2. Se não houver resultados, usa FULLTEXT em modo natural language.
3. Se o FULLTEXT não estiver disponível, usa `LIKE` com até 6 palavras-chave.
4. Usuários anônimos filtram sempre `publico = 1`.
5. Usuários autenticados recebem `publico = 1`, documentos gerais (`acesso_geral = 1`) e
   documentos restritos cuja lista `permissoes` contenha a função pai do usuário.
6. Conteúdo antigo importado de arquivos (`permissoes IS NULL`) continua visível a qualquer
   usuário autenticado, preservando o comportamento anterior.

> A função pai é resolvida no Consinco (`vilrhfuncaofilho`/`vilrhfuncaopai`) e fica em cache na
> sessão. Se o Consinco estiver indisponível, a busca retorna apenas conteúdo público/geral.

### Dados estruturados (lojas e gerentes)

Perguntas como “Quem é o gerente da loja Betânia?” não são respondidas pelo RAG: a gerência
muda com frequência e indexar nomes/contatos criaria uma cópia desatualizada. Em vez disso,
a Vivi consulta o MySQL **na hora da pergunta**:

1. `ViviService` detecta a intenção de gerência (`gerente`, `subgerente`, `responsável`) e
   extrai a loja de trechos como `loja 26`, `loja Betânia`, `gerente da Betânia` ou
   `unidade do Shopping X`. Em perguntas curtas de acompanhamento (“e o subgerente?”), usa a
   última pergunta do histórico.
2. `ViviDadosService` consulta `filial`, `regional_filial`, `gerentes` (último cadastro **da
   regional vinculada**), `func`, `usuarios` e `ger_regional` em
   `RegionalModel::buscarGerenciaPorLoja()`. O vínculo `regional_filial` é o mesmo usado pela
   tela **Regional**; linhas de `gerentes` com `codregional` divergente são ignoradas. Se o
   termo não bater direto, tenta remover palavras genéricas (`loja`, `shopping`,
   `supermercado`) e depois cada palavra. Quando o CPF do gerente não existe em `func`, o nome
   é exibido a partir de `usuarios.usuario`.
3. Quando a pergunta é só de gerência, `ViviDadosService::respostaDireta()` monta a resposta
   diretamente, sem chamar o provedor de IA. Se a pergunta misturar outro assunto, o resultado
   entra no prompt como bloco **DADOS ATUAIS DO SISTEMA**, com prioridade sobre o contexto
   indexado. Se nada for encontrado, a Vivi pede o nome ou código exato.

Regras de segurança:

- A consulta roda para **usuários autenticados e visitantes anônimos**. Os contatos são
  corporativos e considerados públicos pela empresa.
- O bloco não inclui CPF. Contatos corporativos (telefone, ramal, e-mail) são os mesmos já
  exibidos na tela **Regional**.
- Como é consulta ao vivo, mudanças feitas em `/regional` refletem imediatamente, sem
  reindexação. Nas consultas diretas, os nomes e contatos não são enviados ao provedor de IA;
  em perguntas mistas, entram no prompt como qualquer outro contexto.
- O rate limit do chat continua valendo para anônimos (60 mensagens/minuto por IP, 12/minuto
  por sessão e 60/dia por sessão). Além disso, há um teto global de 120 chamadas de IA por dia;
  consultas diretas de ramal, gerente e plantão não consomem esse teto.

Diagnóstico (somente leitura):

```bash
php cli/vivi_verificar_gerencia.php "Quem é o gerente da loja Betania?"
php cli/vivi_verificar_gerencia.php "Quem é o gerente da loja 26?"
php cli/vivi_verificar_gerencia.php "e o subgerente?" --anterior="Quem é o gerente da loja Betania?"
```

A saída mostra exatamente o bloco que seria enviado ao modelo, ou “Nenhuma consulta de
gerência foi disparada” quando a mensagem não tem intenção de loja/gerência. Se o nome estiver
errado, corrija o cadastro em `/regional`; não há reindexação envolvida.

Se a tela Regional e a Vivi mostrarem gerentes diferentes, verifique se há linhas em `gerentes`
com `codregional` diferente do vínculo em `regional_filial` para a mesma loja (o cadastro de
gerente permite escolher qualquer regional, mas não altera o vínculo). A Vivi segue o vínculo
oficial; corrija a linha divergente ou o vínculo para alinhar a resposta.

### Plantão comercial (dados públicos)

A escala de plantão fica em `escalacom` (MySQL) e é exibida publicamente em `/comercial`.
Como muda com frequência, também é consultada na hora, sem indexação:

1. `ViviService` detecta `plantão`, `plantões` ou `escala comercial` e interpreta o período:
   `hoje`, `amanhã`, `ontem`, dia da semana (com `próximo`/`passado`), `dd/mm[/aaaa]`,
   `aaaa-mm-dd`, semana atual/próxima/passada e mês por nome ou `este`/`próximo`/`último`.
   Acompanhamentos curtos (“e amanhã?”) usam a última pergunta se ela falava de plantão.
   Perguntas conceituais (“como funciona…”) sem data explícita não disparam a consulta.
2. Sem período explícito, `plantão` no singular consulta hoje; `plantões`/`escala comercial`
   consulta o mês atual.
3. `ComercialModel::plantoesPorData/plantoesPorPeriodo` juntam `escalacom` a `func` pelo CPF
   do comprador. Se o dia consultado é futuro e não tem registro, a resposta inclui os dois
   próximos plantões cadastrados.
4. Quando a pergunta é só de plantão, `ViviDadosService::respostaDireta()` responde sem chamar
   a IA. Em perguntas mistas, o bloco **DADOS ATUAIS DO SISTEMA** tem prioridade sobre o
   contexto indexado. O CPF do comprador não é enviado; aparecem apenas nome, data e feriado,
   os mesmos dados de `/comercial`.

Assim como lojas/gerentes, o plantão é respondido também para **visitantes anônimos**, porque
a escala já é pública em `/comercial`.

Diagnóstico (somente leitura):

```bash
php cli/vivi_verificar_plantao.php "Quem está de plantão hoje?"
php cli/vivi_verificar_plantao.php "Qual o plantão do dia 25/12/2026?"
php cli/vivi_verificar_plantao.php "Quais os plantões de dezembro?"
php cli/vivi_verificar_plantao.php "e no próximo sábado?" --anterior="Quem está de plantão hoje?"
```

A saída mostra o bloco que seria enviado ao modelo, ou “Nenhuma consulta de plantão foi
disparada”. Para corrigir a escala, use `/comercial/gestao` (perfil comercial).

### Ramais e contatos de funcionários (dados públicos)

Perguntas como “Qual o ramal do Luiz da TI?” também consultam o MySQL na hora:

1. `ViviService` detecta pedidos de contato (`ramal`, `telefone`, `contato`, `e-mail`,
   `busque por`, `quem é`) e extrai o nome e, quando presente, o setor
   (`Luiz da TI`, `Ana Luiza Farace`, `Luiz da Silva`, `Rafael do comercial`).
   Para verbos genéricos de busca, a consulta só dispara quando há um setor reconhecido;
   acompanhamentos curtos (“e o ramal dele?”) usam a última pergunta com nome no histórico.
2. `ContatoService::buscarPorNomeSetor()` consulta `usuarios` (`ativo='S'`), `func`
   e `setor`, considerando apenas quem tem ramal, e-mail ou telefone corporativo.
   A comparação de nome é por palavra completa: “Luiz” não confunde com “Luiza”.
3. O setor pode ser o nome exato de `setor` ou um apelido comum (`TI`, `tecnologia`,
   `informática`, `suporte`, `RH`, `DP`, `financeiro`). Sem setor, a Vivi lista até
   cinco resultados e pede o nome completo ou o setor para refinar.
4. Quando a pergunta é só de contato/ramal, `ViviDadosService::respostaDireta()` responde sem
   chamar a IA. Em perguntas mistas, o resultado entra no prompt como bloco **DADOS ATUAIS DO
   SISTEMA**, com prioridade sobre o contexto indexado. O bloco mostra nome, setor, ramal,
   telefone corporativo e e-mail; **CPF não é enviado**.

Assim como a tela `/contatos`, a consulta é pública e funciona para visitantes anônimos.
Como é consulta ao vivo, alterações feitas no perfil do usuário refletem imediatamente,
sem reindexação.

> Na tela `/contatos`, o filtro **Nome** procura apenas no nome do funcionário. Para achar
> “Rafael do Comercial”, informe `Rafael` em **Nome** e selecione `Comercial` em **Setor**;
> o campo Nome não interpreta o trecho “do Comercial”.

### Feedback e lacunas

O widget envia avaliações pelo `POST /vivi/feedback` (`rating: up|down`) e a própria Vivi
registra automaticamente as perguntas em que **nenhum trecho** foi recuperado. Tudo fica em
`vivi_feedback`:

| Coluna | Conteúdo |
| --- | --- |
| `tipo` | `util`, `inutil` (avaliação explícita) ou `sem_contexto` (lacuna). |
| `pergunta` / `resposta` | Texto avaliado (resposta nula em `sem_contexto`). |
| `publico` | `1` para visitante não autenticado. |
| `codfuncaopai` | Função pai do usuário no momento, quando disponível. |
| `ip_hash` / `sessao_hash` | SHA-256 do IP e da sessão (sem dado pessoal em claro). |

O endpoint exige `Content-Type: application/json`, valida o tamanho dos textos e limita a
40 feedbacks por IP a cada 24 h. Falhas de gravação não interrompem o chat.

Consultas úteis para curadoria:

```sql
-- Perguntas que mais ficaram sem resposta (candidatas a novo documento/FAQ)
SELECT pergunta, COUNT(*) AS vezes, MAX(criado_em) AS ultima
FROM vivi_feedback
WHERE tipo = 'sem_contexto'
GROUP BY pergunta
ORDER BY vezes DESC, ultima DESC
LIMIT 30;

-- Respostas marcadas como não úteis
SELECT pergunta, LEFT(resposta, 300) AS inicio_resposta, criado_em
FROM vivi_feedback
WHERE tipo = 'inutil'
ORDER BY criado_em DESC
LIMIT 30;
```

Use as lacunas para criar/atualizar documentos no módulo Documentos (a próxima indexação os
torna recuperáveis) e as respostas “não úteis” para ajustar chunking, prompt ou conteúdo.

### Atualização periódica (opcional)

Exemplos de cron incremental (conteúdo inalterado é ignorado):

```cron
# Arquivos de documentação
0 3 * * * cd /var/www/html/tempest && /usr/bin/php cli/vivi_indexar.php /var/www/documentacao >> logs/vivi_indexacao.log 2>&1

# Documentos do módulo Documentos (a cada 30 min, sem execuções simultâneas)
*/30 * * * * cd /var/www/html/tempest && flock -n /tmp/vivi_indexar_intranet.lock /usr/bin/php cli/vivi_indexar_intranet.php >> logs/vivi_documentos.log 2>&1
```

Use `--reindexar` apenas quando precisar forçar o reprocessamento de todos os arquivos/documentos.
A primeira carga completa dos 444 documentos pode levar vários minutos; depois, versões
inalteradas são puladas. Garanta que o usuário do cron tenha permissão de leitura nos documentos
e de escrita em `logs/`. A curadoria (`resources/vivi`) não precisa de cron: rode
`php cli/vivi_indexar_curadoria.php` sempre que editar a FAQ.

---

## 5. Operação e manutenção

### Logs

- Arquivo diário: `logs/AAAA-MM-DD.log`.
- Busque ocorrências da Vivi:

```bash
grep "Vivi" logs/$(date +%F).log
```

- Erros do provedor são registrados com status e mensagem retornada (sem a chave).

### Rate limit

- Chaves são SHA-256 de IP + identidade (CPF quando autenticado, senão sessão).
- Janelas fixas: 60 s e 24 h.
- Limpeza automática esporádica de registros com mais de 2 dias.
- Limpeza manual:

```sql
DELETE FROM vivi_rate_limit WHERE janela_inicio < NOW() - INTERVAL 2 DAY;
```

### Backup da base

```bash
mysqldump -h HOST -u USUARIO -p Intranet vivi_conhecimento > vivi_conhecimento.sql
```

Restauração:

```bash
mysql -h HOST -u USUARIO -p Intranet < vivi_conhecimento.sql
```

### Monitoramento sugerido

- `GET /vivi/status` a cada minuto. Ele verifica configuração e base pública, mas **não faz
  chamada ao provedor**; para saúde real, acompanhe os logs ou faça uma pergunta de teste.
- Alertar quando houver muitos `429` ou `503` nos logs.
- Acompanhar o consumo do provedor (TPM/TPD) no console.

---

## 6. Referência técnica

### `POST /vivi/chat`

Rota pública. Exige `Content-Type: application/json`.

**Requisição:**

```json
{
  "message": "Como abrir um chamado no Helpdesk?",
  "history": [
    {"role": "user", "content": "Olá"},
    {"role": "assistant", "content": "Olá! Como posso ajudar?"}
  ]
}
```

- `message`: obrigatório, até `VIVI_MAX_MSG`.
- `history`: opcional; apenas `user`/`assistant`, cada item truncado em 1.500 caracteres,
  últimos 8 itens, sem `assistant` no início.
- O servidor acrescenta a mensagem atual se ela ainda não for a última do histórico.

**Sucesso (200):**

```json
{
  "success": true,
  "message": null,
  "data": {"reply": "Para abrir um chamado..."},
  "meta": {"timestamp": "2026-11-23T11:00:00-03:00"},
  "error": null
}
```

**Erro:**

```json
{
  "success": false,
  "message": null,
  "data": null,
  "meta": {"timestamp": "2026-11-23T11:00:00-03:00"},
  "error": {"message": "Muitas mensagens em pouco tempo...", "code": 429, "details": {"retry_after": 60}}
}
```

**Códigos:**

| HTTP | Quando |
| --- | --- |
| 200 | Resposta gerada. |
| 415 | `Content-Type` não é JSON. |
| 422 | Mensagem vazia ou acima do limite. |
| 429 | Limite local ou do provedor. |
| 502 | Provedor retornou resposta inválida. |
| 503 | Vivi não configurada, banco de rate limit indisponível ou provedor fora. |
| 500 | Erro inesperado. |

Cabeçalhos de resposta: `Cache-Control: no-store` e `X-Content-Type-Options: nosniff`.

### `GET /vivi/status`

```json
{"success":true,"data":{"disponivel":true,"base_publica":true}}
```

### Banco de dados

**`vivi_conhecimento`**

| Coluna | Tipo | Observação |
| --- | --- | --- |
| `id` | INT UNSIGNED AI PK | Identificador. |
| `fonte` | VARCHAR(191) | Arquivo/caminho de origem. |
| `titulo` | VARCHAR(255) | Título do trecho. |
| `conteudo` | MEDIUMTEXT | Texto do trecho. |
| `hash_conteudo` | CHAR(64) | SHA-256 do trecho. |
| `publico` | TINYINT(1) | 1 = acessível a anônimos. |
| `acesso_geral` | TINYINT(1) | 1 = visível a qualquer usuário autenticado. |
| `permissoes` | TEXT | Funções pai permitidas, separadas por vírgula; `NULL` = conteúdo antigo (qualquer autenticado). |
| `embedding` | MEDIUMTEXT | Vetor JSON (opcional). |
| `criado_em` / `atualizado_em` | DATETIME | Controle temporal. |

Índices: `PRIMARY KEY (id)`, `UNIQUE (fonte, hash_conteudo)`, `KEY (publico)`,
`KEY (acesso_geral)`, `FULLTEXT (titulo, conteudo)`.

**`vivi_rate_limit`**

| Coluna | Tipo | Observação |
| --- | --- | --- |
| `chave` | VARCHAR(64) PK | SHA-256 da chave de controle. |
| `janela_inicio` | DATETIME | Início da janela fixa. |
| `contagem` | INT UNSIGNED | Consumo atual. |

### Segurança implementada

- Rota pública com rate limit por IP e por identidade.
- `Content-Type` JSON obrigatório.
- Histórico sanitizado (papéis permitidos, tamanho e quantidade).
- Prompt com regras anti-invenção e anti-injeção; conteúdo do contexto não é tratado como instrução.
- CPF não é enviado ao modelo; apenas nome e seção do usuário autenticado.
- Documentos da intranet respeitam `geral` e `doc_permissao` do módulo Documentos; a permissão
  é aplicada na consulta ao MySQL, não apenas no prompt.
- Visitantes anônimos nunca recebem trechos de documentos da intranet.
- Saída renderizada com escape no frontend.
- Chave do provedor somente no servidor.
- `Cache-Control: no-store` e `nosniff`.

---

## 7. Solução de problemas

| Sintoma | Causa provável | Ação |
| --- | --- | --- |
| “A Vivi está temporariamente indisponível…” (503) | Tabelas ausentes, falha no MySQL do rate limit ou erro 4xx/5xx do provedor. | Veja `logs/AAAA-MM-DD.log`, confirme as tabelas e o status do provedor. |
| “A Vivi ainda não foi configurada…” (503) | `VIVI_BASE_URL`/`VIVI_MODEL` vazios ou provedor remoto sem `VIVI_API_KEY`. | Preencha o `.env` e confirme em `/vivi/status`. |
| “A Vivi atingiu o limite de uso do provedor…” (429) | Cota do provedor (RPM/TPM/RPD/TPD). | Aguarde; consultas de ramal, gerente e plantão continuam funcionando sem IA; revise os limites no console e ajuste `VIVI_RATE_*`. |
| “Muitas mensagens em pouco tempo…” (429) | Rate limit local. | Aguarde 60 s. |
| “Limite diário de mensagens atingido…” (429) | Limite de 24 h. | Aguarde ou ajuste `VIVI_RATE_DIA`. |
| “A Vivi atingiu o limite diário de respostas com IA…” (429) | Teto global `VIVI_RATE_IA_DIA`. | Aguarde a janela de 24 h; consultas diretas continuam disponíveis; aumente o teto se o provedor permitir. |
| “Sua mensagem é muito longa…” (422) | Acima de `VIVI_MAX_MSG`. | Reduza o texto ou aumente a variável. |
| “Envie a mensagem em JSON.” (415) | `Content-Type` incorreto. | Envie `application/json`. |
| Respostas dizem que não encontrou | Base vazia, documento privado para anônimo/função sem acesso ou pergunta fora da base. | Rode `php cli/vivi_indexar_intranet.php`; confira a permissão do documento no módulo Documentos. |
| Resposta errada sobre onde encontrar algo | A base não tem conteúdo de navegação; a busca retorna documentos parecidos. | Use `php cli/vivi_verificar_busca.php "pergunta"`; ajuste `resources/vivi/*.md` e rode `php cli/vivi_indexar_curadoria.php`. |
| Não sabe quem é o gerente da loja | Consulta não disparada (pergunta sem menção a loja/filial), loja não encontrada, cadastro ausente em `gerentes` ou `codregional` divergente de `regional_filial`. | Rode `php cli/vivi_verificar_gerencia.php "pergunta"`; confira o cadastro e o vínculo em `/regional`. |
| Não sabe o plantão comercial | Período não reconhecido, pergunta conceitual sem data ou tabela `escalacom` sem registros. | Rode `php cli/vivi_verificar_plantao.php "pergunta"`; confira a escala em `/comercial/gestao`. |
| Chat fecha sozinho | Página com auto-refresh sem o resguardo do chat. | Garanta a checagem de `body.vivi-aberto` no reload. |
| “Não foi possível falar com a Vivi agora…” | Rede, TLS, DNS ou provedor inacessível. | Teste a `VIVI_BASE_URL` a partir do servidor. |
| Indexação sem efeito | Arquivo/versão inalterado e mesmas permissões. | Use `--reindexar` ou envie uma nova versão do documento. |
| Indexação com erros de permissão | Usuário do CLI sem leitura nos documentos. | Ajuste permissões ou rode com o usuário correto. |
| PDF indexado com “sem texto” | PDF digitalizado (imagem) ou protegido. | Use OCR antes de indexar ou consulte o original; imagens/vídeos entram apenas com metadados. |
| Erro “pdftotext” não encontrado | Pacote `poppler-utils` ausente. | Instale `poppler-utils` e rode a indexação novamente. |
| Feedback não aparece após atualizar a página | Os botões são exibidos só para respostas novas. | Comportamento esperado; o voto já enviado permanece gravado em `vivi_feedback`. |
| Feedback retorna 415 | `Content-Type` incorreto. | Envie `application/json`. |
| Lentidão/timeout | Contexto grande ou provedor lento. | Reduza `VIVI_TOP_K`/`VIVI_MAX_CONTEXTO`; aumente timeouts do PHP-FPM. |

---

## 8. Checklist de produção

- [ ] `database/vivi.sql` executado e tabelas existentes.
- [ ] Em bases antigas, `database/vivi_permissoes.sql` e `database/vivi_feedback.sql` executados.
- [ ] `pdftotext` instalado e acessível pelo usuário do PHP/cron.
- [ ] Bloco `VIVI_*` preenchido no `.env` do servidor.
- [ ] Chave do provedor válida (quando remoto).
- [ ] Modelo de chat disponível para a chave.
- [ ] `/vivi/status` retorna `disponivel:true`.
- [ ] Base indexada com a visibilidade correta (`php cli/vivi_indexar_intranet.php`).
- [ ] Curadoria indexada (`php cli/vivi_indexar_curadoria.php`).
- [ ] Perguntas de navegação testadas com `php cli/vivi_verificar_busca.php`.
- [ ] Perguntas de loja/gerente testadas com `php cli/vivi_verificar_gerencia.php`.
- [ ] Perguntas de plantão comercial testadas com `php cli/vivi_verificar_plantao.php`.
- [ ] Perguntas de ramal testadas com nome e setor (ex.: “ramal do Luiz da TI”).
- [ ] Conteúdo público revisado (sem dados sensíveis).
- [ ] Documentos restritos testados com um usuário sem permissão e com um usuário permitido.
- [ ] Timeouts do PHP-FPM/nginx acima de 75 s.
- [ ] `logs/` gravável pelo PHP.
- [ ] Cron de reindexação configurado (se necessário).
- [ ] Limites de rate limit compatíveis com a cota do provedor.
- [ ] Teste como anônimo e como usuário autenticado.
- [ ] Backup de `vivi_conhecimento` incluído na rotina.

---

## 9. Limitações e evolução

- Sem streaming: a resposta chega completa e é truncada em `VIVI_MAX_RESPOSTA`.
- Histórico apenas no navegador; o servidor é stateless.
- Documentos restritos dependem da função pai resolvida no Consinco; se o Consinco estiver
  indisponível, a Vivi mostra apenas conteúdo público/geral.
- A Vivi usa a função do usuário e não replica o acesso administrativo da Gestão de Documentos.
- PDFs digitalizados não têm OCR; imagens e vídeos entram apenas com metadados.
- Busca vetorial em PHP com limite de trechos (`VIVI_MAX_CHUNKS_BUSCA`); para bases grandes,
  avalie um índice vetorial dedicado.
- Rate limit em janela fixa; atrás de NAT/proxy, o IP pode ser compartilhado.
- Sem interface web de indexação; operação via CLI.
- A checagem de conteúdo restrito é apenas de existência; termos muito genéricos podem gerar
  falso positivo, mas título, trecho e quantidade nunca são expostos.
- Sem citação de fontes na resposta (o contexto é usado, mas não há links por trecho).
- Dados estruturados cobrem loja → gerente/subgerente, plantão comercial por dia, semana ou
  mês e contatos/ramais por nome + setor. Outras consultas (aniversariantes, férias, estoque)
  exigiriam novos resolvedores ou tool calling no provedor.

Melhorias possíveis: streaming, painel de administração da base, citações com fonte, OCR para
PDFs digitalizados, métricas de uso, filtros de categoria na indexação e tool calling para
ampliar as consultas estruturadas.
