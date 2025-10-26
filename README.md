<!-- 
---
title: He4rtBot Discord API
description: Sistema de gamificação e gestão da comunidade He4rt Developers no Discord
version: 1.0.0
author: He4rt Developers
license: MIT
tags: [discord, bot, laravel, gamification, api, ddd]
--- 
-->

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
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php"></a>
  <a href="https://www.docker.com"><img src="https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker"></a>
</p>

## 📋 Índice

- [Sobre o Projeto](#-sobre-o-projeto)
- [Funcionalidades](#-funcionalidades)
- [Arquitetura](#-arquitetura)
- [Requisitos](#-requisitos)
- [Instalação e Configuração](#-instalação-e-configuração)
  - [Com Make](#com-make)
  - [Sem Make (Comandos Diretos)](#sem-make-comandos-diretos)
- [Executando Testes](#-executando-testes)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Documentação](#-documentação)
- [Contribuindo](#-contribuindo)
- [Licença](#-licença)

## 🎯 Sobre o Projeto

O **He4rtBot Discord API** é o backend da solução de gamificação e gestão da comunidade [He4rt Developers](https://discord.gg/he4rt) no Discord. Construído com Laravel 12 e seguindo princípios de **Domain-Driven Design (DDD)**, o sistema oferece:

- Sistema completo de gamificação (XP, níveis, daily rewards)
- Gestão de badges e conquistas
- Sistema de ranking por temporadas (seasons)
- Gerenciamento de meetings e eventos
- Sistema de feedback da comunidade
- Integração com provedores OAuth (Discord, GitHub, etc.)
- Rastreamento de mensagens e atividades de voz

## ✨ Funcionalidades

### 🎮 Gamificação
- **Sistema de XP e Níveis**: Ganhe experiência por participação
- **Daily Rewards**: Recompensas diárias para usuários ativos
- **Voice Rewards**: XP por tempo em canais de voz
- **Rankings**: Competições por mensagens, nível e atividades
- **Seasons**: Temporadas de ranking com reset periódico

### 🏆 Badges e Conquistas
- Sistema de badges personalizáveis
- Claim automático e manual de badges
- Vinculação com characters temáticos

### 👥 Gestão de Usuários
- Perfis completos com informações e endereços
- Integração OAuth multi-provider
- Histórico de mensagens e atividades

### 📅 Eventos e Meetings
- Gestão de tipos de meetings
- Registro de participantes
- Acompanhamento de assuntos discutidos

### 💬 Feedback
- Sistema de feedback da comunidade
- Review e aprovação de feedbacks
- Rastreamento de melhorias

## 🏗️ Arquitetura

O projeto segue uma arquitetura **Domain-Driven Design (DDD)** com módulos isolados no diretório `Heart/`:

```
Heart/
├── Authentication/    # Autenticação OAuth
├── Badges/           # Sistema de badges
├── Character/        # Personagens e temáticas
├── Core/             # Classes compartilhadas (DTOs, Contracts, Traits)
├── Feedback/         # Sistema de feedback
├── Integrations/     # Integrações externas
├── Meeting/          # Gestão de meetings
├── Message/          # Rastreamento de mensagens
├── Provider/         # Provedores OAuth
├── Ranking/          # Sistema de rankings
├── Season/           # Gestão de temporadas
├── Shared/           # Recursos compartilhados
└── User/             # Domínio de usuários
    ├── Application/      # Use Cases
    ├── Domain/           # Entidades e Lógica de Negócio
    ├── Infrastructure/   # Implementações (Repositories, etc)
    └── Presentation/     # Controllers e Recursos
```

Cada módulo segue a estrutura:
- **Application**: Casos de uso e lógica de aplicação
- **Domain**: Entidades, Value Objects, Repositories (interfaces)
- **Infrastructure**: Implementações concretas (Eloquent, APIs externas)
- **Presentation**: Controllers, Resources, Requests

## 🔧 Requisitos

- **Docker** e **Docker Compose**
- **Make** (opcional, mas recomendado)
- **Git**

### Para desenvolvimento sem Docker:
- PHP 8.3+
- Composer
- MySQL 8.0+
- Redis 6.2+
- Node.js 18+ e npm

## 🚀 Instalação e Configuração

### Com Make

```bash
# 1. Clone o repositório
git clone https://github.com/he4rt/he4rt-bot-api.git
cd he4rt-bot-api

# 2. Configure as variáveis de ambiente
cp .env.example .env

# 3. Build e configure o ambiente
make build

# 4. Execute as migrations e seeds
make migrate

# 5. Acesse a aplicação
# API: http://localhost:88
# Swagger (dev): http://localhost:88/swagger
```

### Sem Make (Comandos Diretos)

#### Linux/macOS/WSL
```bash
# 1. Build e subir containers
docker-compose up -d --build

# 2. Gerar chave da aplicação
docker exec -it discord-bot-api php artisan key:generate

# 3. Executar migrations
docker exec -it discord-bot-api php artisan migrate --database=testing --seed
docker exec -it discord-bot-api php artisan migrate --seed
```

#### Windows PowerShell
```powershell
# 1. Build e subir containers
docker-compose up -d --build

# 2. Gerar chave da aplicação
docker exec -it discord-bot-api php artisan key:generate

# 3. Executar migrations
docker exec -it discord-bot-api php artisan migrate --database=testing --seed
docker exec -it discord-bot-api php artisan migrate --seed
```

### Configuração Adicional

#### Variáveis de Ambiente Importantes

```env
# Database
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=he4rt_bot
DB_USERNAME=root
DB_PASSWORD=root

# Redis
REDIS_HOST=cache
REDIS_PASSWORD=eYVX7EwVmmxKPCDmwMtyKVge8oLd2t81
REDIS_PORT=6379

# Discord OAuth
DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
DISCORD_REDIRECT_URI=

# GitHub OAuth
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URI=
```

## 🧪 Executando Testes

O projeto utiliza **PHPUnit** com suporte a testes de Feature e Unit.

### Com Make
```bash
# Todos os testes
make test

# Filtrar por classe
make test filter=GetSeasonsTest

# Filtrar por módulo
make test filter=User

# Filtrar por método específico
make test filter=testCanUpdateProfile
```

### Sem Make
```bash
# Todos os testes
docker exec -it discord-bot-api vendor/bin/phpunit --stop-on-failure --testdox

# Com filtro
docker exec -it discord-bot-api vendor/bin/phpunit --filter=GetSeasonsTest --testdox

# Apenas Feature tests
docker exec -it discord-bot-api vendor/bin/phpunit --testsuite=Feature

# Apenas Unit tests
docker exec -it discord-bot-api vendor/bin/phpunit --testsuite=Unit
```

### Dentro do Container
```bash
# Acessar o container
make bash
# ou
docker exec -it discord-bot-api sh

# Executar testes
vendor/bin/phpunit
vendor/bin/phpunit --coverage-html coverage/
```

## 📁 Estrutura do Projeto

```
he4rt-bot-api/
├── Heart/                    # Módulos DDD
│   ├── Authentication/
│   ├── Badges/
│   ├── Character/
│   ├── Core/
│   ├── Feedback/
│   ├── Meeting/
│   ├── Message/
│   ├── Provider/
│   ├── Ranking/
│   ├── Season/
│   └── User/
├── app/                      # Laravel App
│   ├── Console/
│   ├── Exceptions/
│   ├── Http/
│   └── Providers/
├── config/                   # Configurações
├── database/
│   ├── migrations/          # Migrations do banco
│   └── seeders/             # Seeders
├── docker/                   # Configurações Docker
│   └── nginx/
├── docs/                     # Documentação detalhada
│   ├── api/
│   ├── architecture/
│   ├── database/
│   ├── development/
│   └── testing/
├── routes/
│   ├── api.php              # Rotas da API (nova estrutura)
│   ├── old-api.php          # Rotas legadas (Lumen)
│   └── web.php
├── tests/
│   ├── Feature/             # Testes de integração
│   └── Unit/                # Testes unitários
├── docker-compose.yml
├── Dockerfile
├── Makefile                 # Comandos Make
└── phpunit.xml              # Configuração PHPUnit
```

## 📚 Documentação

A documentação completa está disponível no diretório [`docs/`](./docs/):

- **[Arquitetura](./docs/architecture/overview.md)**: Visão geral da arquitetura DDD
- **[Módulos](./docs/architecture/modules.md)**: Documentação de cada módulo
- **[API](./docs/api/endpoints.md)**: Referência completa de endpoints
- **[Database](./docs/database/schema.md)**: Schema e relacionamentos
- **[Testes](./docs/testing/guide.md)**: Guia completo de testes
- **[Desenvolvimento](./docs/development/setup.md)**: Setup detalhado
- **[Docker](./docs/development/docker.md)**: Configuração e troubleshooting

### Swagger/OpenAPI

Em ambiente de desenvolvimento, a documentação interativa está disponível em:
```
http://localhost:88/swagger
```

## 🤝 Contribuindo

Contribuições são sempre bem-vindas! Por favor, leia nosso [Guia de Contribuição](./CONTRIBUTING.md) e o [Código de Conduta](./CODE_OF_CONDUCT.md).

### Fluxo de Contribuição

1. Fork o projeto
2. Crie uma branch seguindo o padrão: `feat/nova-funcionalidade`, `fix/correcao-bug`, `docs/documentacao`
3. Commit suas mudanças seguindo [Conventional Commits](https://www.conventionalcommits.org/)
4. Push para a branch
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](./LICENSE) para mais detalhes.

## 🔗 Links Úteis

- [Discord He4rt Developers](https://discord.gg/he4rt)
- [Documentação Laravel](https://laravel.com/docs)
- [Docker Documentation](https://docs.docker.com/)

---

<p align="center">
  Feito com 💜 pela comunidade <a href="https://discord.gg/he4rt">He4rt Developers</a>
</p>
