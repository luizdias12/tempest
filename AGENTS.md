# AGENTS.md

## O que é

Intranet PHP MVC custom ("Tempest") **sem framework** — sem artisan, sem migrations, sem PHPUnit, sem CI. Apesar dos nomes `routes/`, `resources/`, `public/` **não é Laravel**. Fluxo do request: `public/index.php` -> `Router` -> middleware -> Controller -> Service -> Model -> DB.

**Idioma: tudo em pt-BR** — comentários, mensagens de exceção/usuário, textos de view, mensagens de commit. Escreva código novo em pt-BR também.

## Comandos (não há build/lint/test)

- Deps: `composer install` — requer **PHP 8.2** (`composer.lock` trava `phpspreadsheet 5.9.0`, que exige `^8.2`). Não há `composer scripts`.
- Verificar mudança: `php -l <arquivo>` (sintaxe PHP) e `node --check public/js/<arquivo>.js` (sintaxe JS) — únicos checks locais seguros.
- Oracle (`pdo_oci`/`oci8`) e LDAP dependem de infra/conexões do servidor; localmente não rodam. Scripts de `test/` e jobs de `cli/` atingem **bancos de produção reais** — nunca rodá-los por acaso.
- Scripts descartáveis de teste ficam em `test/` (gitignored, não é suíte). `cli/` contém jobs estilo cron (`php cli/importar_emails_helpdesk.php` etc.).

## Arquitetura

- **Entrypoint**: `public/index.php` inicia session + composer autoload + dotenv, depois requer `routes/web.php` e `routes/api.php` e despacha.
- **Acesso a dados**: `App\Core\DB` — helpers PDO estáticos (`select`, `first`, `insert`, `update`, `updateWhere`, `deleteWhere`, `findWhere`, `countWhere`, `paginateTable`, `execute`, transações). **O nome da conexão é o último argumento e o default é `rm`**. Conexões: `mysql`, `rm` (Oracle RM/TOTVS), `consinco` (Oracle); `oracle` é alias de `rm`. Conexões Oracle usam PDO::CASE_LOWER, então as chaves voltam minúsculas.
- **Models** são classes de **métodos estáticos** agrupados por conexão: `App\Model\Mysql\*`, `App\Model\Oracle\*`, `App\Model\Consinco\*`. Alguns métodos usam `DB::*`, outros `QueryBuilder::table('view', 'connection')`.
- **Services** (`App\Service\*`) também são classes de métodos estáticos com regras de negócio; controllers chamam services, nunca models diretamente.
- **Views**: ficam em `app/View/<modulo>/` (não em `resources/views`). `view('path', $params, $layout)` resolve `app/View/{path}.php` com layout default `layouts/main` (também `layouts/print`). Helpers (`component`, `partial`, `renderComponent`, `asset`, `dd/dump`, `jsonResponse`, `pagination`, `initcap`, `maskCpf`, etc.) são auto-carregados de `resources/functions/helpers.php` via composer `files`.
- **Schema**: sem migrations. `database/schema_mysql.sql` é o dump de estrutura (só DDL, sem dados, escopo tempest-only) das tabelas MySQL do projeto, gerado do banco de produção — serve para montar uma base nova, e `database/seed_mysql.sql` completa com os dados de referência (`doc_diretorio`, `doc_subdiretorio`, `carousel`, `messages`; as imagens do carrossel ficam em `public/assets/caroussel/`, que é gitignored); os `database/*.sql` antigos seguem como registro dos scripts aplicados em bases já existentes. Ficam de fora: tabelas legadas, `tbl_extrato_unimed`, bases Oracle (RM/Consinco) e os arquivos de `public/files/`.

## Módulos e integrações

- **Documentos** (`/documentos`; gestão em `/documentos/gestao` com `gestaoRole`): diretórios têm `visibilidade` `publico`/`restrito` (restrito = só o criador); arquivos ficam em `public/files/documentos/{DIR}/{SUB}/` e são servidos apenas via PHP. A migração de documento (`POST /documentos/mover`) move os arquivos físicos e todas as versões e **mantém as permissões atuais**.
- **Importação Unimed** (`/importacao/unimed`, `gestaoRole` + `hasPermission('ti')`): arquivo texto layout 176 separado por `;` (16 campos; datas `dd/mm/yyyy`; valor com vírgula decimal), `.txt`/`.csv` até 5 MB, gravado em `tbl_extrato_unimed` (MySQL/MyISAM). A PK inclui `id_registro` auto-incremento, então `INSERT IGNORE` **não** deduplica — o resumo mostra quantos registros já existiam na competência. Importação real grava em produção: nunca execute sem autorização.
- **Extrato Unimed** (`/unimed`, Área do Funcionário, só `auth`): consulta `tbl_extrato_unimed` por competência e casa o usuário logado com `nome_titular` por nome normalizado (maiúsculas, sem acentos); inclui dependentes (linhas cujo `nome_cliente` difere do titular).
- **Efeito de Natal**: `public/css/natal.css` + `public/js/natal.js` (neve, luzes e Papai Noel voador) carregados pelo `layouts/main` de 1º/dez a 6/jan; `?natal=1` força e `?natal=0` desliga. Respeita `prefers-reduced-motion`; z-index 9000–9002 (abaixo de toasts 9999 e modais 10000).

## Convenções de rota

- Aliases de middleware são declarados no topo de cada arquivo de rotas: `$router->aliasMiddleware('auth', AuthMiddleware::class)`.
- Rotas web são agrupadas e sempre carregam middleware de grupo `['auth']`; middlewares de role por rota (`role`, `rh`, `gestaoRole`, `comercialRole`) entram como array por rota.
- Existem rotas públicas intencionais fora de grupo auth: `/`, `/error`, `/manutencao`, `/contatos`, `/login` (+ cadastro/redefinir), `/funcionarios/aniversariantes`, `/comercial`, `/regional/*` (consultas) e `/vivi/*` (assistente, com rate limit) — não exija auth nelas.
- Assinatura de action: `($request, ...$params)`. Segmentos `{param}` de rota chegam como argumentos posicionais após `$request`.
- Retornar **array de qualquer action é serializado como JSON automaticamente**. `jsonResponse()` é a alternativa explícita.
- Middleware que nega acesso retorna `false` e o Router chama `exit` — middlewares de role encerram o request eles mesmos.

## Infra (produção)

- Acesso HTTPS via **HAProxy** (TLS em `:443`, PEM em `/etc/haproxy/certs/intranet.pem`) encaminhando HTTP para o **Apache em `127.0.0.1:8082`**. `public/index.php` detecta HTTPS por `HTTPS`, `SERVER_PORT=443` ou `HTTP_X_FORWARDED_PROTO` para marcar o cookie de sessão como `secure`.
- Atrás do proxy, `REMOTE_ADDR` vira o IP do HAProxy; para logs/auditoria/online refletirem o cliente real é preciso habilitar `mod_remoteip` no Apache com os ranges confiáveis do proxy.
- Cadeia TLS: o certificado exige o intermediário "GlobalSign GCC R6 AlphaSSL CA 2025" no PEM do HAProxy (o `server_1.crt` antigo contém o intermediário de 2023 e quebra a validação).

## Pegadinhas

- `app/Core/QueryBuilder copy.php` é um **duplicado obsoleto** rastreado no git; o código usa `QueryBuilder.php`. Editar a cópia não muda nada.
- Anexos do helpdesk: `handleAttach()` em `helpers.php` devolve caminho relativo (`/...`). Não volte a hardcodizar host/porta — quebra sob HTTPS e em outro host.
- `public/index.php` lê `APP_ENV` do `.env` (default `production`); só os valores exatos `development` (liga `display_errors`), `production` e `maintenance` têm efeito. O `.env.example` traz um placeholder inválido (`production_development_maintenance`).
- Sem CDNs para assets dos layouts: a rede interna bloqueia `cdnjs.cloudflare.com` em alguns computadores. Font Awesome 6.7.2 fica em `public/vendor/fontawesome/` (CSS + webfonts) e é referenciado via `asset()` nos layouts `main` e `simple`. O Chart.js do dashboard de helpdesk ainda é carregado do cdnjs — mesmo risco de bloqueio.
- Conteúdo de `public/files/` e `public/assets/`, e `.env`, `opencode.json`, `/test`, `/logs` são gitignored — não dependa deles estarem no repo.
- PHP local é 8.2 (`php` e `php-fpm8.2`); o código já usa features 8.1/8.2 (`readonly`, `match`, `str_*`, constructor promotion) — escreva para PHP 8.2.