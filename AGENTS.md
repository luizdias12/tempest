# AGENTS.md

## O que é

Intranet PHP MVC custom ("Tempest") **sem framework** — sem artisan, sem migrations, sem PHPUnit, sem CI. Apesar dos nomes `routes/`, `resources/`, `public/` **não é Laravel**. Fluxo do request: `public/index.php` -> `Router` -> middleware -> Controller -> Service -> Model -> DB.

**Idioma: tudo em pt-BR** — comentários, mensagens de exceção/usuário, textos de view, mensagens de commit. Escreva código novo em pt-BR também.

## Comandos (não há build/lint/test)

- Deps: `composer install` — requer **PHP 8.2** (`composer.lock` trava `phpspreadsheet 5.9.0`, que exige `^8.2`). Não há `composer scripts`.
- Verificar mudança: `php -l <arquivo>` (sintaxe) — único check local seguro.
- Oracle (`pdo_oci`/`oci8`) e LDAP dependem de infra/conexões do servidor; localmente não rodam. Scripts de `test/` e jobs de `cli/` atingem **bancos de produção reais** — nunca rodá-los por acaso.
- Scripts descartáveis de teste ficam em `test/` (gitignored, não é suíte). `cli/` contém jobs estilo cron (`php cli/importar_emails_helpdesk.php` etc.).

## Arquitetura

- **Entrypoint**: `public/index.php` inicia session + composer autoload + dotenv, depois requer `routes/web.php` e `routes/api.php` e despacha.
- **Acesso a dados**: `App\Core\DB` — helpers PDO estáticos (`select`, `first`, `insert`, `update`, `updateWhere`, `deleteWhere`, `findWhere`, `countWhere`, `paginateTable`, `execute`, transações). **O nome da conexão é o último argumento e o default é `rm`**. Conexões: `mysql`, `rm` (Oracle RM/TOTVS), `consinco` (Oracle); `oracle` é alias de `rm`. Conexões Oracle usam PDO::CASE_LOWER, então as chaves voltam minúsculas.
- **Models** são classes de **métodos estáticos** agrupados por conexão: `App\Model\Mysql\*`, `App\Model\Oracle\*`, `App\Model\Consinco\*`. Alguns métodos usam `DB::*`, outros `QueryBuilder::table('view', 'connection')`.
- **Services** (`App\Service\*`) também são classes de métodos estáticos com regras de negócio; controllers chamam services, nunca models diretamente.
- **Views**: ficam em `app/View/<modulo>/` (não em `resources/views`). `view('path', $params, $layout)` resolve `app/View/{path}.php` com layout default `layouts/main` (também `layouts/print`). Helpers (`component`, `partial`, `renderComponent`, `asset`, `dd/dump`, `jsonResponse`, `pagination`, `initcap`, `maskCpf`, etc.) são auto-carregados de `resources/functions/helpers.php` via composer `files`.
- **Schema**: sem migrations. Referência das tabelas de chat MySQL: `database/chat.sql`; carousel e demais tabelas são criadas manualmente nos DBs legados.

## Convenções de rota

- Aliases de middleware são declarados no topo de cada arquivo de rotas: `$router->aliasMiddleware('auth', AuthMiddleware::class)`.
- Rotas web são agrupadas e sempre carregam middleware de grupo `['auth']`; middlewares de role por rota (`role`, `rh`, `gestaoRole`, `comercialRole`) entram como array por rota.
- Existem rotas públicas intencionais fora de grupo auth: `/`, `/error`, `/contatos`, `/login` (+ cadastro/redefinir), `/funcionarios/aniversariantes`, `/comercial` — não exija auth nelas.
- Assinatura de action: `($request, ...$params)`. Segmentos `{param}` de rota chegam como argumentos posicionais após `$request`.
- Retornar **array de qualquer action é serializado como JSON automaticamente**. `jsonResponse()` é a alternativa explícita.
- Middleware que nega acesso retorna `false` e o Router chama `exit` — middlewares de role encerram o request eles mesmos.

## Pegadinhas

- `app/Core/QueryBuilder copy.php` é um **duplicado obsoleto** rastreado no git; o código usa `QueryBuilder.php`. Editar a cópia não muda nada.
- `handleAttach()` em `helpers.php` hardcodiza `http://192.168.101.16:8082/` para anexos enviados.
- `.env.example` define `APP_ENV=prod_dev`; só o valor exato `dev` liga `display_errors` em `public/index.php`.
- Conteúdo de `public/files/` e `public/assets/`, e `.env`, `opencode.json`, `/test`, `/logs` são gitignored — não dependa deles estarem no repo.
- PHP local é 8.2 (`php` e `php-fpm8.2`); o código já usa features 8.1/8.2 (`readonly`, `match`, `str_*`, constructor promotion) — escreva para PHP 8.2.