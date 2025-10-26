---
title: Setup do Ambiente de Desenvolvimento
description: Guia completo de configuração do ambiente
version: 1.0.0
last_updated: 2025-10-26
tags: [setup, installation, configuration, development]
---

# 🛠️ Setup do Ambiente de Desenvolvimento

## Visão Geral

Este guia cobre a configuração completa do ambiente de desenvolvimento para o He4rtBot Discord API.

## 📋 Pré-requisitos

### Obrigatórios

- **Docker** 20.10+
- **Docker Compose** 1.29+
- **Git** 2.x

### Opcionais (mas recomendados)

- **Make** (para comandos simplificados)
- **VS Code** (com extensões PHP e Docker)
- **Postman** ou **Insomnia** (testar API)

### Para desenvolvimento sem Docker

- PHP 8.3+
- Composer 2.x
- MySQL 8.0+
- Redis 6.2+
- Node.js 18+ (para assets)

## 🚀 Instalação Rápida

### 1. Clone o Repositório

```bash
git clone https://github.com/he4rt/he4rt-bot-api.git
cd he4rt-bot-api
```

### 2. Configure Variáveis de Ambiente

```bash
# Copie o arquivo de exemplo
cp .env.example .env

# Edite as variáveis necessárias
nano .env  # ou seu editor preferido
```

### 3. Build e Inicie os Containers

#### Com Make (Linux/macOS/WSL)

```bash
# Build completo
make build

# Executar migrations
make migrate
```

#### Sem Make (Windows PowerShell / Universal)

```bash
# Build e subir containers
docker-compose up -d --build

# Gerar chave da aplicação
docker exec -it discord-bot-api php artisan key:generate

# Executar migrations
docker exec -it discord-bot-api php artisan migrate --database=testing --seed
docker exec -it discord-bot-api php artisan migrate --seed
```

### 4. Verificar Instalação

```bash
# Verificar se containers estão rodando
docker-compose ps

# Acessar a API
curl http://localhost:88
```

## ⚙️ Configuração Detalhada

### Variáveis de Ambiente

#### .env Principal

```env
# Application
APP_NAME="He4rtBot Discord API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:88
APP_KEY=

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=he4rt_bot
DB_USERNAME=root
DB_PASSWORD=root

# Database Testing
DB_TEST_HOST=mysql
DB_TEST_PORT=3306
DB_TEST_DATABASE=he4rt_bot_test
DB_TEST_USERNAME=root
DB_TEST_PASSWORD=root

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=cache
REDIS_PASSWORD=eYVX7EwVmmxKPCDmwMtyKVge8oLd2t81
REDIS_PORT=6379
REDIS_DB=0

# Discord OAuth
DISCORD_CLIENT_ID=your_discord_client_id
DISCORD_CLIENT_SECRET=your_discord_client_secret
DISCORD_REDIRECT_URI=http://localhost:88/auth/callback

# GitHub OAuth
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
GITHUB_REDIRECT_URI=http://localhost:88/auth/github/callback

# Bot Authentication
BOT_TOKEN=your_bot_token_here

# Features
FEATURES_GAMIFICATION_BADGES=true
FEATURES_GAMIFICATION_SEASONS=true
FEATURES_MEETINGS=true
FEATURES_FEEDBACK=true

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Discord Webhook Logger (opcional)
DISCORD_WEBHOOK_URL=
```

### Configuração OAuth

#### Discord Application

1. Acesse [Discord Developer Portal](https://discord.com/developers/applications)
2. Crie uma nova aplicação
3. Em **OAuth2**, adicione redirect URI: `http://localhost:88/auth/callback`
4. Copie `Client ID` e `Client Secret` para o `.env`

#### GitHub Application

1. Acesse [GitHub Settings > Developer Settings](https://github.com/settings/developers)
2. Crie uma nova OAuth App
3. Authorization callback URL: `http://localhost:88/auth/github/callback`
4. Copie `Client ID` e `Client Secret` para o `.env`

## 🐳 Configuração Docker

### docker-compose.yml Personalizado

```yaml
# docker-compose.override.yml (opcional, não commitar)
version: '3.1'

services:
    app:
        ports:
            - "9000:9000"
        environment:
            XDEBUG_MODE: debug
            XDEBUG_CONFIG: client_host=host.docker.internal

    mysql:
        ports:
            - "3307:3306"  # Mudar se 3306 estiver em uso

    cache:
        ports:
            - "6380:6379"  # Mudar se 6379 estiver em uso
```

### Xdebug (Debugging)

```ini
# docker/php.ini
[xdebug]
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
```

## 🔧 Comandos Úteis

### Artisan

```bash
# Lista de comandos
docker exec -it discord-bot-api php artisan list

# Limpar caches
docker exec -it discord-bot-api php artisan cache:clear
docker exec -it discord-bot-api php artisan config:clear
docker exec -it discord-bot-api php artisan route:clear
docker exec -it discord-bot-api php artisan view:clear

# Otimizar para produção
docker exec -it discord-bot-api php artisan config:cache
docker exec -it discord-bot-api php artisan route:cache
docker exec -it discord-bot-api php artisan view:cache

# Tinker (REPL)
docker exec -it discord-bot-api php artisan tinker
```

### Composer

```bash
# Instalar dependências
docker exec -it discord-bot-api composer install

# Atualizar dependências
docker exec -it discord-bot-api composer update

# Atualizar específico
docker exec -it discord-bot-api composer update laravel/framework

# Dump autoload
docker exec -it discord-bot-api composer dump-autoload
```

### Database

```bash
# Migrations
docker exec -it discord-bot-api php artisan migrate
docker exec -it discord-bot-api php artisan migrate:fresh --seed

# Seeds
docker exec -it discord-bot-api php artisan db:seed

# Ver status das migrations
docker exec -it discord-bot-api php artisan migrate:status
```

## 🧪 Ambiente de Testes

### Configurar Banco de Teste

```bash
# Criar banco de teste (dentro do MySQL)
docker exec -it {mysql-container} mysql -uroot -proot
CREATE DATABASE he4rt_bot_test;
exit;

# Executar migrations no banco de teste
docker exec -it discord-bot-api php artisan migrate --database=testing --seed
```

### Executar Testes

```bash
# Todos os testes
docker exec -it discord-bot-api vendor/bin/phpunit

# Com cobertura
docker exec -it discord-bot-api vendor/bin/phpunit --coverage-html coverage/
```

## 💻 IDEs e Editores

### VS Code

#### Extensões Recomendadas

```json
{
    "recommendations": [
        "bmewburn.vscode-intelephense-client",
        "felixfbecker.php-debug",
        "ms-azuretools.vscode-docker",
        "junstyle.php-cs-fixer",
        "neilbrayfield.php-docblocker",
        "cjhowe7.laravel-blade",
        "onecentlin.laravel-blade"
    ]
}
```

#### Settings.json

```json
{
    "php.suggest.basic": false,
    "intelephense.files.maxSize": 5000000,
    "intelephense.environment.phpVersion": "8.3.0",
    
    "editor.formatOnSave": true,
    "[php]": {
        "editor.defaultFormatter": "junstyle.php-cs-fixer"
    },
    
    "phpcs.enable": true,
    "phpcs.standard": "PSR12"
}
```

#### Launch.json (Debug)

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www": "${workspaceFolder}"
            }
        }
    ]
}
```

### PhpStorm

1. **Configure PHP Interpreter:**
   - Settings > PHP > CLI Interpreter
   - Add Docker Compose interpreter

2. **Configure Xdebug:**
   - Settings > PHP > Debug
   - Port: 9003

3. **Configure Database:**
   - Database tool
   - MySQL: localhost:3306
   - User: root, Password: root

## 🚨 Troubleshooting

### Porta já em uso

```bash
# Windows
netstat -ano | findstr :88
taskkill /PID {PID} /F

# Linux/macOS
lsof -ti:88 | xargs kill -9
```

### Permissões no Docker

```bash
# Linux: adicionar usuário ao grupo docker
sudo usermod -aG docker $USER
newgrp docker

# Reiniciar Docker
sudo systemctl restart docker
```

### Container não inicia

```bash
# Ver logs
docker-compose logs app

# Rebuild sem cache
docker-compose build --no-cache app
docker-compose up -d
```

### Composer install falha

```bash
# Limpar cache do Composer
docker exec -it discord-bot-api composer clear-cache

# Reinstalar
docker exec -it discord-bot-api composer install --no-cache
```

### MySQL connection refused

```bash
# Verificar se MySQL está rodando
docker-compose ps mysql

# Reiniciar MySQL
docker-compose restart mysql

# Ver logs do MySQL
docker logs {mysql-container-name}
```

## 🔄 Workflow Diário

### Iniciando o Dia

```bash
# 1. Atualizar código
git pull origin main

# 2. Subir containers
docker-compose up -d

# 3. Instalar novas dependências (se houver)
docker exec -it discord-bot-api composer install

# 4. Executar novas migrations (se houver)
docker exec -it discord-bot-api php artisan migrate

# 5. Limpar caches
docker exec -it discord-bot-api php artisan cache:clear
```

### Finalizando o Dia

```bash
# 1. Commit e push
git add .
git commit -m "feat: description"
git push

# 2. Parar containers (opcional)
docker-compose down
```

## 📚 Recursos Adicionais

- [Documentação Docker](./docker.md)
- [Guia de Testes](../testing/guide.md)
- [API Endpoints](../api/endpoints.md)

---

## 📞 Suporte

Problemas na configuração?

- **Discord**: [He4rt Developers](https://discord.gg/he4rt)
- **Issues**: [GitHub Issues](https://github.com/he4rt/he4rt-bot-api/issues)
- **Discussions**: [GitHub Discussions](https://github.com/he4rt/he4rt-bot-api/discussions)

---

> 💡 **Dica**: Mantenha seu Docker e Docker Compose atualizados para evitar problemas de compatibilidade.