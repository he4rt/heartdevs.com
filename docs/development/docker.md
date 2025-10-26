---
title: Configuração Docker
description: Guia completo de Docker e containers
version: 1.0.0
last_updated: 2025-10-26
tags: [docker, containers, docker-compose, devops]
---

# 🐳 Configuração Docker

## Visão Geral

O He4rtBot API usa Docker para criar um ambiente de desenvolvimento consistente e isolado.

## 📦 Containers

### Serviços

```yaml
services:
  app:          # PHP 8.3 + Laravel
  webserver:    # Nginx
  mysql:        # MySQL 8.0
  cache:        # Redis 6.2
```

### Arquitetura

```
┌─────────────────────────────────────────┐
│           Host Machine                   │
│  ┌───────────────────────────────────┐  │
│  │        Docker Network              │  │
│  │  ┌──────────┐  ┌──────────┐      │  │
│  │  │ Webserver│  │   App    │      │  │
│  │  │  Nginx   │→ │ PHP+Laravel     │  │
│  │  └────┬─────┘  └────┬─────┘      │  │
│  │       │             │             │  │
│  │  ┌────▼─────┐  ┌───▼──────┐     │  │
│  │  │  MySQL   │  │  Redis   │     │  │
│  │  └──────────┘  └──────────┘     │  │
│  └───────────────────────────────────┘  │
│          ↕ Port 88, 3306, 6379          │
└─────────────────────────────────────────┘
```

## 🚀 Comandos Docker

### Básicos

```bash
# Subir containers
docker-compose up -d

# Ver status
docker-compose ps

# Ver logs
docker-compose logs -f

# Parar containers
docker-compose down

# Rebuild containers
docker-compose up -d --build

# Remover tudo (⚠️ cuidado!)
docker-compose down -v
```

### Container App (PHP)

```bash
# Acessar bash do container
docker exec -it discord-bot-api sh

# Executar comando Artisan
docker exec -it discord-bot-api php artisan {command}

# Composer
docker exec -it discord-bot-api composer install
docker exec -it discord-bot-api composer update

# PHPUnit
docker exec -it discord-bot-api vendor/bin/phpunit

# PHP CS Fixer
docker exec -it discord-bot-api vendor/bin/pint
```

### Container MySQL

```bash
# Acessar MySQL
docker exec -it {mysql-container-name} mysql -uroot -proot

# Backup
docker exec {mysql-container-name} mysqldump -uroot -proot he4rt_bot > backup.sql

# Restore
docker exec -i {mysql-container-name} mysql -uroot -proot he4rt_bot < backup.sql

# Ver logs
docker logs {mysql-container-name}
```

### Container Redis

```bash
# Acessar Redis CLI
docker exec -it {redis-container-name} redis-cli

# Com senha
docker exec -it {redis-container-name} redis-cli -a eYVX7EwVmmxKPCDmwMtyKVge8oLd2t81

# Ver keys
docker exec -it {redis-container-name} redis-cli KEYS '*'

# Flush cache
docker exec -it {redis-container-name} redis-cli FLUSHALL
```

## 📁 Estrutura de Arquivos

### docker-compose.yml

```yaml
version: '3.1'

services:
    mysql:
        image: mysql
        restart: unless-stopped
        ports:
            - '3306:3306'
        environment:
            MYSQL_USER: ${DB_USERNAME}
            MYSQL_PASSWORD: ${DB_PASSWORD}
            MYSQL_DATABASE: ${DB_DATABASE}
            MYSQL_ROOT_PASSWORD: root
        volumes:
            - mysql:/data
        networks:
            - app-network

    app:
        container_name: discord-bot-api
        build:
            context: ./
        networks:
            - app-network
        depends_on:
            - mysql
        volumes:
            - ./:/var/www/

    webserver:
        image: nginx:alpine
        container_name: webserver
        restart: unless-stopped
        ports:
            - "88:80"
            - "443:443"
        volumes:
            - ./:/var/www
            - ./docker/nginx/conf.d/:/etc/nginx/conf.d/
        networks:
            - app-network

    cache:
        image: redis:6.2-alpine
        restart: always
        ports:
            - '6379:6379'
        command: redis-server --requirepass eYVX7EwVmmxKPCDmwMtyKVge8oLd2t81
        volumes:
            - cache:/data
        networks:
            - app-network

networks:
    app-network:
        driver: bridge

volumes:
    cache:
        driver: local
    mysql:
        driver: local
```

### Dockerfile

```dockerfile
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . /var/www

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 9000

CMD ["php-fpm"]
```

### Nginx Config

```nginx
# docker/nginx/conf.d/default.conf
server {
    listen 80;
    index index.php index.html;
    root /var/www/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 🔧 Troubleshooting

### Container não inicia

```bash
# Ver logs detalhados
docker-compose logs app

# Rebuild forçado
docker-compose build --no-cache app
docker-compose up -d

# Verificar portas em uso
netstat -ano | findstr :88  # Windows
lsof -i :88                 # Linux/macOS
```

### Permissões de arquivo

```bash
# Dentro do container
chown -R www-data:www-data /var/www
chmod -R 755 /var/www/storage
chmod -R 755 /var/www/bootstrap/cache
```

### MySQL não conecta

```bash
# Verificar se MySQL está rodando
docker-compose ps mysql

# Ver logs do MySQL
docker logs {mysql-container-name}

# Testar conexão
docker exec discord-bot-api php artisan tinker
>>> DB::connection()->getPdo();
```

### Redis não conecta

```bash
# Verificar Redis
docker exec {redis-container-name} redis-cli ping

# Com senha
docker exec {redis-container-name} redis-cli -a {password} ping

# Testar do Laravel
docker exec discord-bot-api php artisan tinker
>>> Redis::ping();
```

### App container para inesperadamente

```bash
# Ver logs
docker logs discord-bot-api

# Comum: erro de sintaxe PHP
# Verificar últimas mudanças em código

# Reinstalar dependências
docker exec discord-bot-api composer install
```

### Performance lenta

```bash
# Otimizar cache do Laravel
docker exec discord-bot-api php artisan config:cache
docker exec discord-bot-api php artisan route:cache
docker exec discord-bot-api php artisan view:cache

# Otimizar autoload do Composer
docker exec discord-bot-api composer dump-autoload -o
```

## 🎯 Boas Práticas

### 1. Use .dockerignore

```
# .dockerignore
.git
.env
node_modules
vendor
storage/logs/*
storage/framework/cache/*
```

### 2. Multi-stage builds

```dockerfile
# Build stage
FROM php:8.3-fpm as builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts

# Runtime stage
FROM php:8.3-fpm
COPY --from=builder /app/vendor ./vendor
COPY . .
```

### 3. Variáveis de ambiente

```bash
# Nunca commite .env
# Use .env.example como template

# Para CI/CD, use secrets
```

### 4. Health checks

```yaml
services:
    app:
        healthcheck:
            test: ["CMD", "php", "artisan", "health:check"]
            interval: 30s
            timeout: 10s
            retries: 3
```

### 5. Logs

```bash
# Logs em tempo real
docker-compose logs -f app

# Últimas 100 linhas
docker-compose logs --tail=100 app

# Desde tempo específico
docker-compose logs --since 2025-10-26T14:00:00 app
```

## 🚀 CI/CD com Docker

### GitHub Actions Example

```yaml
name: CI

on: [push, pull_request]

jobs:
    test:
        runs-on: ubuntu-latest
        
        services:
            mysql:
                image: mysql:8.0
                env:
                    MYSQL_DATABASE: testing
                    MYSQL_ROOT_PASSWORD: root
                ports:
                    - 3306:3306
                    
        steps:
            - uses: actions/checkout@v2
            
            - name: Build Docker image
              run: docker build -t he4rt-bot-api .
              
            - name: Run tests
              run: docker run he4rt-bot-api vendor/bin/phpunit
```

## 📊 Monitoramento

### Docker Stats

```bash
# Uso de recursos
docker stats

# Específico
docker stats discord-bot-api
```

### Logs

```bash
# App logs
docker logs discord-bot-api

# Laravel logs
docker exec discord-bot-api tail -f storage/logs/laravel.log
```

## 🔐 Segurança

### 1. Não use root

```dockerfile
USER www-data
```

### 2. Scan de vulnerabilidades

```bash
docker scan he4rt-bot-api
```

### 3. Imagens oficiais

```yaml
services:
    mysql:
        image: mysql:8.0  # Tag específica, não 'latest'
```

### 4. Secrets

```bash
# Use Docker secrets ou variáveis de ambiente
# Nunca hardcode credenciais
```

## 📚 Recursos Adicionais

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/)
- [Laravel Docker](https://laravel.com/docs/sail)

---

> 💡 **Dica**: Use `docker-compose exec` para executar comandos em containers rodando, e `docker-compose run` para iniciar temporários.