# He4rtBot Discord API

<p align="center">
  <a href="https://discord.gg/he4rt">
    <img src="./.github/logo.png" height="220">
  </a>
</p>

<h1 align="center">
He4rt Discord Bot API
</h1>
<p align="center">
  <a href="https://discord.gg/he4rt"><img src="https://img.shields.io/endpoint?url=https://github.com/he4rt/he4rt-bot-api/blob/main/version.json"></a>
  <a href="https://discord.gg/he4rt"><img src="https://img.shields.io/github/license/he4rt/he4rt-bot-api?color=A655FF&style=for-the-badge"></a>
<p>

## 📖 Sobre o Projeto

API REST desenvolvida para gerenciar as funcionalidades do bot de Discord da comunidade He4rt Developers. Construída com Laravel 12 e arquitetura modular, oferece endpoints para gerenciamento de usuários, badges, rankings, meetings, feedback e integrações com serviços externos.

## 📑 Índice

- [Stack Tecnológica](#-stack-tecnológica)
- [Arquitetura](#-arquitetura)
- [Pré-requisitos](#-pré-requisitos)
- [Instalação](#-instalação)
- [Configuração](#️-configuração)
- [Comandos Disponíveis](#-comandos-disponíveis)
- [Desenvolvimento](#-desenvolvimento)
- [Testes](#-testes)
- [Qualidade de Código](#-qualidade-de-código)
- [Estrutura de Módulos](#-estrutura-de-módulos)
- [Documentação da API](#-documentação-da-api)
- [Contribuindo](#-contribuindo)
- [Licença](#-licença)

## 🚀 Stack Tecnológica

### Backend

- **PHP 8.3+** - Linguagem principal
- **Laravel 12** - Framework PHP
- **Laravel Sanctum** - Autenticação de API
- **Scramble** - Documentação automática de API
- **PostgreSQL 18** - Banco de dados relacional
- **Redis 8** - Cache e filas
- **Predis** - Cliente Redis para PHP

### Frontend

- **Node.js 24+** - Runtime JavaScript
- **Vite 7** - Build tool e dev server
- **Tailwind CSS 4** - Framework CSS utility-first
- **Axios** - Cliente HTTP

### DevOps & Ferramentas

- **Docker** - Containerização (PostgreSQL, Redis, Mailpit)
- **Mailpit** - Servidor SMTP para desenvolvimento
- **Composer** - Gerenciador de dependências PHP
- **npm** - Gerenciador de dependências JavaScript

### Qualidade de Código

- **Pest 4** - Framework de testes moderno
- **Laravel Pint** - Code style fixer (PSR-12)
- **PHPStan** - Análise estática de código
- **Rector** - Refatoração automatizada
- **Prettier** - Formatação de código (JS, CSS, Blade)
- **Husky** - Git hooks para CI/CD local
- **lint-staged** - Linting em arquivos staged

## 🏗️ Arquitetura

Este projeto utiliza **arquitetura modular** implementada com [InternaChi Modular](https://github.com/InterNACHI/modular). Cada módulo é um pacote Composer independente localizado em `app-modules/`.

### Módulos Disponíveis

| Módulo           | Descrição                                      |
| ---------------- | ---------------------------------------------- |
| `authentication` | Gerenciamento de autenticação e tokens         |
| `badge`          | Sistema de badges e conquistas                 |
| `character`      | Gerenciamento de personagens/avatares          |
| `feedback`       | Sistema de feedback da comunidade              |
| `integrations`   | Integrações com serviços externos              |
| `meeting`        | Gerenciamento de reuniões e eventos            |
| `message`        | Sistema de mensagens                           |
| `provider`       | Provedores de serviços                         |
| `ranking`        | Sistema de ranking e pontuação                 |
| `season`         | Gerenciamento de temporadas                    |
| `shared`         | Funcionalidades compartilhadas                 |
| `tenant`         | Sistema multi-tenancy para isolamento de dados |
| `user`           | Gerenciamento de usuários                      |

Cada módulo possui sua própria estrutura:

```
app-modules/{module}/
├── composer.json
├── database/
│   └── migrations/
├── routes/
│   └── {module}-routes.php
├── src/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── ...
└── tests/
    ├── Feature/
    └── Unit/
```

## 📋 Pré-requisitos

### Ferramentas Necessárias

- **PHP** 8.3 ou superior
- **Composer** 2.x
- **Node.js** 24 ou superior
- **npm** (incluído com Node.js)
- **Docker** & **Docker Compose**
- **Git**

### Shells Suportados

Este projeto oferece suporte para os seguintes shells:

- Bash
- Zsh
- Fish
- PowerShell (pwsh)

### Extensões PHP Requeridas

```bash
php -m | grep -E '(pdo|pgsql|redis|mbstring|xml|curl|zip|openssl)'
```

Certifique-se de que as seguintes extensões estão instaladas:

- `pdo_pgsql`
- `redis`
- `mbstring`
- `xml`
- `curl`
- `zip`
- `openssl`

## 📦 Instalação

### 1. Clone o Repositório

```bash
git clone https://github.com/he4rt/he4rt-bot-api.git
cd he4rt-bot-api
```

### 2. Instalação Rápida

Use o comando `make setup` para configurar tudo automaticamente:

```bash
make setup
```

Este comando irá:

- Instalar dependências PHP (Composer)
- Instalar dependências JavaScript (npm)
- Copiar `.env.example` para `.env`
- Gerar chave da aplicação
- Executar migrações e seeders
- Criar link simbólico para storage
- Gerar arquivos de IDE Helper

### 3. Instalação Manual (Alternativa)

Se preferir, você pode executar os passos manualmente:

```bash
# Instalar dependências
composer install
npm install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Subir containers Docker
make env-up

# Executar migrações
php artisan migrate:fresh --seed

# Link de storage
php artisan storage:link

# IDE Helper (opcional, mas recomendado)
composer run-script ide-helper
```

## ⚙️ Configuração

### Variáveis de Ambiente

Edite o arquivo `.env` com suas configurações:

#### Aplicação

```env
APP_NAME=He4rtBotAPI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
```

#### Banco de Dados (PostgreSQL)

```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=dev_he4rtbot
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

#### Redis

```env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

#### Email (Mailpit em desenvolvimento)

```env
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
```

#### Configurações He4rt Discord Bot

```env
HE4RT_SEASON_ID=
HE4RT_BOT_SECRET=
HE4RT_DISCORD_BOT_KEY=
HE4RT_DISCORD_LEVELUP_CHANNEL=
HE4RT_DISCORD_GUILD=
```

### Serviços Docker

```bash
# Iniciar serviços (PostgreSQL, Redis, Mailpit)
make env-up

# Parar serviços
make env-down
```

Os seguintes serviços estarão disponíveis:

- **PostgreSQL**: `localhost:5432`
- **Redis**: `localhost:6379`
- **Mailpit Web UI**: `http://localhost:8025`
- **Mailpit SMTP**: `localhost:1025`

## 🔧 Comandos Disponíveis

### Makefile Commands

Use `make help` para ver todos os comandos disponíveis:

```bash
make help
```

#### Desenvolvimento

```bash
make dev                 # Inicia servidor, queue, logs e vite
make route-list          # Lista todas as rotas registradas
make migrate-fresh       # Recria banco de dados com seeds
```

#### Ambiente Docker

```bash
make env-up             # Inicia containers Docker
make env-down           # Para e remove containers
```

#### Qualidade de Código

```bash
make format             # Corrige estilo (Pint + Rector)
make check              # Verifica código (Pint + PHPStan + Rector)
make pint               # Executa Pint
make rector             # Executa Rector
make phpstan            # Executa PHPStan
```

#### Testes

```bash
make test               # Executa todos os testes
make test-unit          # Executa apenas testes unitários
make test-feature       # Executa apenas testes de feature
```

### Composer Scripts

```bash
composer setup          # Setup completo do projeto
composer dev            # Inicia ambiente de desenvolvimento
composer test           # Executa testes com Pest
composer test:unit      # Testes unitários
composer test:feature   # Testes de feature
composer test:cov       # Testes com cobertura de código
composer check          # Verifica qualidade do código
composer pint           # Formata código
composer rector         # Refatora código
composer phpstan        # Análise estática
composer ide-helper     # Gera arquivos para IDEs
```

### NPM Scripts

```bash
npm run dev             # Inicia Vite dev server
npm run build           # Build para produção
npm run format          # Formata código com Prettier
npm run outdated        # Verifica dependências desatualizadas
```

## 💻 Desenvolvimento

### Iniciar Servidor de Desenvolvimento

O comando `dev` inicia todos os serviços necessários em paralelo:

```bash
make dev
# ou
composer dev
```

Isso irá iniciar:

- **Laravel Server** (porta 8000)
- **Queue Listener** (processamento de filas)
- **Pail** (logs em tempo real)
- **Vite** (HMR para assets)

Acesse: `http://localhost:8000`

### Estrutura de Rotas

As rotas estão organizadas por módulo. Cada módulo define suas próprias rotas em:

```
app-modules/{module}/routes/{module}-routes.php
```

Para listar todas as rotas:

```bash
make route-list
```

### Trabalhando com Migrações

```bash
# Criar nova migration
php artisan make:migration create_table_name

# Executar migrações
php artisan migrate

# Reverter última migração
php artisan migrate:rollback

# Recriar banco com seeds
make migrate-fresh
```

## 🧪 Testes

Este projeto utiliza **Pest PHP** para testes.

### Executar Testes

```bash
# Todos os testes
make test

# Apenas testes unitários
make test-unit

# Apenas testes de feature
make test-feature

# Com cobertura de código
composer test:cov

# Testes em paralelo (mais rápido)
vendor/bin/pest --parallel
```

### Estrutura de Testes

```
tests/
├── Feature/          # Testes de integração/feature
├── Unit/            # Testes unitários
├── Pest.php         # Configuração do Pest
└── TestCase.php     # Classe base para testes

app-modules/{module}/tests/
├── Feature/         # Testes de feature do módulo
└── Unit/           # Testes unitários do módulo
```

### Criar Novos Testes

```bash
php artisan make:test UserTest --unit
php artisan make:test UserFeatureTest
```

## ✨ Qualidade de Código

### Code Style (Pint)

Formata código seguindo PSR-12:

```bash
make pint              # Corrige automaticamente
make test-pint         # Apenas verifica (CI)
```

### Análise Estática (PHPStan)

Detecta bugs sem executar código:

```bash
make phpstan           # Analisa código
```

Configuração em: `phpstan.neon`

### Refatoração (Rector)

Moderniza código automaticamente:

```bash
make rector            # Aplica refatorações
make test-rector       # Apenas verifica (CI)
```

Configuração em: `rector.php`

### Verificação Completa

```bash
make check             # Pint + PHPStan + Rector (dry-run)
make format            # Pint + Rector (aplica mudanças)
```

### Pre-commit Hooks

O projeto usa **Husky** e **lint-staged** para validar código antes de commits:

```bash
# Configurado automaticamente após npm install
npm run prepare
```

Arquivos validados:

- **PHP**: Pint + Rector
- **JS/CSS/Blade**: Prettier

## 📚 Estrutura de Módulos

Cada módulo é um pacote Composer independente com:

### Estrutura de um Módulo

```
app-modules/{module}/
├── composer.json              # Dependências do módulo
├── database/
│   ├── factories/            # Factories para testes
│   ├── migrations/           # Migrações do módulo
│   └── seeders/             # Seeders do módulo
├── routes/
│   └── {module}-routes.php   # Rotas do módulo
├── src/
│   ├── Controllers/          # Controllers HTTP
│   ├── Models/              # Eloquent Models
│   ├── Services/            # Lógica de negócio
│   ├── DTOs/                # Data Transfer Objects
│   ├── Requests/            # Form Requests
│   ├── Resources/           # API Resources
│   └── Providers/           # Service Providers
└── tests/
    ├── Feature/             # Testes de integração
    └── Unit/               # Testes unitários
```

### Criar Novo Módulo

```bash
php artisan module:make NomeDoModulo
```

### Publicar Assets de Módulos

```bash
php artisan module:publish NomeDoModulo
```

## 📖 Documentação da API

A documentação da API é gerada automaticamente usando **Scramble**.

### Acessar Documentação

```bash
# Com servidor rodando
make dev
```

Acesse: `http://localhost:8000/docs/api`

### Atualizar Documentação

A documentação é gerada automaticamente a partir de:

- **Rotas** definidas em `routes/`
- **Form Requests** (validação)
- **API Resources** (responses)
- **DocBlocks** nos controllers

## 🤝 Contribuindo

Contribuições são sempre bem-vindas! Siga estas diretrizes:

### 1. Fork e Clone

```bash
git clone https://github.com/seu-usuario/he4rt-bot-api.git
cd he4rt-bot-api
```

### 2. Crie uma Branch

```bash
git checkout -b feature/minha-feature
# ou
git checkout -b fix/meu-bugfix
```

### 3. Faça suas Alterações

- Siga os padrões de código (Pint, Rector, PHPStan)
- Escreva testes para novas funcionalidades
- Documente mudanças significativas

### 4. Verifique Qualidade

```bash
make check              # Verifica código
make test              # Executa testes
```

### 5. Commit e Push

```bash
git add .
git commit -m "feat: adiciona nova funcionalidade"
git push origin feature/minha-feature
```

### 6. Abra um Pull Request

Descreva suas mudanças e aguarde review!

### Padrões de Commit

Usamos [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` Nova funcionalidade
- `fix:` Correção de bug
- `docs:` Documentação
- `style:` Formatação de código
- `refactor:` Refatoração
- `test:` Testes
- `chore:` Tarefas gerais

## 📄 Licença

Este projeto está sob a licença **MIT**. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

<p align="center">
  Feito com 💜 pela comunidade <a href="https://discord.gg/he4rt">He4rt Developers</a>
</p>
