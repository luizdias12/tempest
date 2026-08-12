# 🌪️ Tempest — Intranet Corporativa

> 💡 Aplicação web PHP construída sobre um mini-framework MVC próprio (Controller → Service → Model), sem frameworks externos, com foco em organização, escalabilidade e boas práticas de engenharia.

---

## 🖼️ Visão Geral

O **Tempest** é a intranet da Villefort Atacarejo. Reúne em um único sistema os principais serviços internos, integrando-se diretamente aos bancos de dados legados da empresa.

* 🔹 Arquitetura em camadas (Controller → Service → Model)
* 🔹 Sistema de rotas próprio (Web + API)
* 🔹 Middleware (Auth via LDAP, Role TI, CORS, API)
* 🔹 Tratamento de exceções centralizado e logging
* 🔹 Conexão simultânea com **3 bancos de dados**
* 🔹 Paginação, upload de arquivos e notificação por e-mail
* 🔹 Validação de acesso por funções (RH/TOTVS)

---

## 🧠 Arquitetura

```text
Request → Router → Middleware → Controller → Service → Model → Database
```

### 🔸 Controller

Recebe a requisição, orquestra os serviços e devolve a resposta (view, redirect ou JSON).

### 🔸 Service

Camada de regras de negócio — isolada da infraestrutura.

### 🔸 Model

Responsável pelo acesso ao banco de dados, separado por conexão:

```text
app/Model/
├── Mysql/      → dados operacionais (helpdesk, documentos, logs, carousel...)
├── Oracle/     → banco RM (TOTVS) — funcionários, financeiro, filiais
└── Consinco/   → banco Consinco — funções pai
```

### 🔸 Core

Infraestrutura do sistema:

* Router
* Request / Response
* DB (PDO — conexões `mysql`, `rm` e `consinco`)
* ErrorHandler / Logger
* BaseController
* ApiException
* Alerts (AlertManager — toasts/flash messages)

### 🔸 Middleware

* `AuthMiddleware` → autenticação LDAP + presença online (heartbeat)
* `RoleMiddleware` → restringe rotas ao suporte TI
* `CorsMiddleware` / `ApiMiddleware` → camada de API

---

## 🧱 Estrutura do Projeto

```bash
app/
├── Controller/       # Controllers web + API
├── Core/             # Infraestrutura (DB, Router, Request, Response, Logger...)
├── Middleware/       # Auth, Role, CORS, API
├── Model/            # Mysql / Oracle (RM) / Consinco
├── Service/          # Regras de negócio
├── View/             # Templates (por módulo + partials + components)
├── Facades/
├── DTO/
routes/
├── api.php           # Rotas da API
├── web.php           # Rotas web
public/
├── index.php         # Front controller
├── css/ js/ assets/  # Estilos, scripts e arquivos públicos
resources/
├── functions/        # Helpers globais (view, asset, initcap, pagination...)
```

---

## ⚙️ Stack Utilizada

* 🐘 PHP 8+ (PDO)
* 🛢️ MySQL — dados operacionais
* 🛢️ Oracle RM (TOTVS) — RH, financeiro e funções
* 🛢️ Oracle Consinco — funções pai
* 🔐 LDAP / Active Directory — autenticação
* 📦 Composer: `vlucas/phpdotenv`, `phpoffice/phpspreadsheet`, `phpmailer/phpmailer`
* 🌐 JavaScript Vanilla + HTML + CSS (tema dark, sem frameworks de frontend)

---

## 🧩 Módulos

* 🏠 **Home** — carousel de banners/vídeos gerenciável pela tabela `carousel` (imagens em `public/assets/caroussel/`)
* 🖥️ **Helpdesk** — abertura de chamados, histórico com anexos, filtros (por Nº, nome, status, local, "atribuídos a mim"), SLA, cancelamento com motivo, notificações por e-mail e visualização de anexos
* 📄 **Documentos** — árvore de diretórios/subpastas, versões de documentos, permissões por função, download inline e gestão administrativa
* 💰 **Financeiro** — holerite com totais e impressão em PDF (dados do RM)
* 👥 **Funcionários** — listagem com filtros e aniversariantes do mês em cards por dia (dados do RM)
* 🛠️ **TI** — lista de funcionários da TI com exportação Excel (PhpSpreadsheet)
* 📶 **Usuários Online** — presença em tempo real via heartbeat (tabela `online`) com força de deslogamento
* 📜 **Logs** — auditoria centralizada das ações no sistema (`log_user`)

---

## 📡 API (Padrão de Resposta)

```json
{
  "success": true,
  "message": "Operação realizada com sucesso",
  "data": {},
  "meta": {
    "timestamp": "2026-01-01T12:00:00"
  },
  "error": null
}
```

Destaques: autenticação JWT, consulta de funcionários (por chapa/nome), totais de holerite e utilitários de data.

---

## ❌ Tratamento de Erros

* ✔ Erros de negócio → `ApiException`
* ✔ Erros inesperados → capturados globalmente (`ErrorHandler`) e registrados no `Logger`

---

## 🛠️ Funcionalidades

* ✔ Autenticação LDAP com sessão
* ✔ CRUD e gestão administrativa de documentos com permissões por função
* ✔ Carousel administrável (upload, ordem, ativar/inativar, excluir)
* ✔ Upload de anexos (validado por tipo e tamanho)
* ✔ Paginação nativa
* ✔ Notificação por e-mail (PHPMailer)
* ✔ Exportação Excel (PhpSpreadsheet)
* ✔ Presença online com heartbeat throttled + retry em deadlock
* ✔ Logging de auditoria
* ✔ Respostas padronizadas para API

---

## 🚀 Roadmap

* [ ] Validator estilo Laravel
* [ ] DTO completo
* [ ] Query Layer (joins complexos)
* [ ] Autenticação JWT para frontend mobile
* [x] Logger estruturado
* [x] Cache

---

## 🔧 Configuração

1. `composer install`
2. Copie `.env.example` → `.env` e preencha as conexões (LDAP, Oracle RM, Oracle Consinco, MySQL e e-mail)
3. Aponte o servidor web para `public/`
4. Crie a tabela `carousel` (id, filename, ord, dtinicio, dtfim, ativo) e registre os slides

---

## 🧑‍💻 Autor

**Luiz Junior** — Desenvolvimento e arquitetura.

---

## 📃 Licença

Livre para uso em estudos e evolução profissional.

---

> 🔥 Projeto em constante evolução — melhorias sendo adicionadas continuamente.