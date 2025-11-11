---
title: Instalação
description: Guia completo de instalação do He4rt Bot API
order: 2
---

# Instalação

## Pré-requisitos

- PHP 8.3 ou superior
- Composer
- PostgreSQL 18+
- Redis
- Node.js 22+

## Passo a Passo

### 1. Clone o Repositório

```bash
git clone https://github.com/he4rt/he4rt-bot-api.git
cd he4rt-bot-api
```

### 2. Instale as Dependências

```bash
composer install
npm install
```

### 3. Configure o Ambiente

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure o Banco de Dados

Edite o arquivo `.env` com suas credenciais do PostgreSQL:

```text
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=he4rt_bot
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### 5. Execute as Migrações

```bash
php artisan migrate --seed
```

### 6. Inicie o Servidor

```bash
composer run dev
```

O servidor estará disponível em `http://localhost:8000`.
