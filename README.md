# 🚀 Web Master — PHP MVC Framework (Portfolio)

> 💡 Um mini-framework desenvolvido do zero em PHP, inspirado em arquiteturas modernas como MVC + Service Layer, com foco em organização, escalabilidade e boas práticas de engenharia.

---

## 🖼️ Visão Geral

Este projeto demonstra a construção de uma aplicação completa **sem frameworks externos**, incluindo:

* 🔹 Arquitetura em camadas (Controller → Service → Model)
* 🔹 Sistema de rotas próprio
* 🔹 Middleware (Auth, CORS, API)
* 🔹 Tratamento de exceções centralizado
* 🔹 Padrão de resposta para APIs
* 🔹 Paginação nativa
* 🔹 Separação entre API e Web

---

## 🧠 Arquitetura (Clean-like)

```text
Request → Router → Middleware → Controller → Service → Model → Database
```

### 🔸 Controller

Recebe a requisição e retorna a resposta.

### 🔸 Service

Camada de regras de negócio.

### 🔸 Model

Responsável pelo acesso ao banco de dados.

### 🔸 Core

Infraestrutura do sistema:

* Router
* Request / Response
* DB (PDO)
* ApiException
* ErrorHandler

### 🔸 Middleware

Intercepta requisições para:

* Autenticação
* CORS
* Tratamento de API

---

## 🧱 Estrutura do Projeto

```bash
app/
├── Controller/
├── Core/
├── DTO/
├── Middleware/
├── Model/
├── Service/
├── View/
routes/
├── api.php
├── web.php
public/
├── index.php
resources/
├── functions/
```

---

## ⚙️ Stack Utilizada

* 🐘 PHP 8+
* 🛢️ MySQL (PDO)
* 📦 Composer (PSR-4 Autoload)
* 🌐 JavaScript (Vanilla)
* 🎨 HTML + CSS

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

---

## ❌ Tratamento de Erros

Uso de exceção customizada:

```php
throw new ApiException('Usuário não encontrado', 404);
```

✔ Erros de negócio → `ApiException`
✔ Erros inesperados → capturados globalmente

---

## 🔐 Middlewares

* `AuthMiddleware` → autenticação
* `CorsMiddleware` → controle de acesso
* `ApiMiddleware` → padronização de requisições API

---

## 📄 Exemplo de Endpoint

```http
GET /api/usuarios?page=1&limit=10
```

---

## 🛠️ Funcionalidades

* ✔ CRUD completo de usuários
* ✔ Paginação
* ✔ Upload de arquivos
* ✔ Sistema de rotas customizado
* ✔ Middleware configurável
* ✔ Respostas padronizadas

---

## 🚀 Roadmap (Evolução)

* [ ] Validator estilo Laravel
* [ ] DTO completo
* [ ] Query Layer (joins complexos)
* [ ] Logger estruturado
* [ ] Autenticação JWT
* [x] Cache

---

## 🧑‍💻 Sobre o Projeto

Este projeto foi desenvolvido com o objetivo de:

* 📚 Estudo de arquitetura backend
* 🧠 Prática de boas práticas em PHP
* 🏗️ Construção de um mini-framework próprio

---

## 👨‍💻 Autor

**Luiz Junior**

---

## ⭐ Destaque

Este projeto demonstra conhecimento em:

* Arquitetura em camadas
* Padrões de projeto
* Backend estruturado
* Criação de framework próprio

---

## 📃 Licença

Livre para uso em estudos e evolução profissional.

---

> 🔥 Projeto em constante evolução — melhorias sendo adicionadas continuamente.
