---
title: Ambientes de Deploy
description: Configuração dos ambientes de deploy
version: 1.0.0
last_updated: 2025-10-26
tags: [deploy, environments, production, staging]
---

# 🚀 Ambientes de Deploy

## Visão Geral

O projeto utiliza três ambientes principais: Development, Staging e Production.

## 🏗️ Ambientes

### 1. Development (Local)

Ambiente de desenvolvimento local dos desenvolvedores.

**Características:**
- Docker Compose para serviços
- Dados mockados e seeders
- Debug habilitado
- Hot reload (se aplicável)
- SSL não obrigatório

**Configuração (.env.local):**
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=he4rt_bot
DB_USERNAME=root
DB_PASSWORD=secret

CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

LOG_LEVEL=debug
```

**Iniciar:**
```bash
docker-compose up -d
php artisan migrate:fresh --seed
php artisan serve
```

### 2. Staging (Homologação)

Ambiente de testes antes da produção.

**Características:**
- Réplica da produção
- Dados de teste (não produção)
- Debug limitado
- SSL obrigatório
- Testes de integração
- QA testing

**URL:** `https://staging-api.heartdevs.com`

**Configuração (.env.staging):**
```env
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging-api.heartdevs.com

DB_CONNECTION=mysql
DB_HOST=staging-db.internal
DB_PORT=3306
DB_DATABASE=he4rt_bot_staging
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

CACHE_DRIVER=redis
REDIS_HOST=staging-redis.internal

LOG_LEVEL=info
LOG_CHANNEL=stack
```

**Deploy:**
```bash
# Via CI/CD ao fazer merge em branch 'staging'
git push origin staging
```

### 3. Production (Produção)

Ambiente de produção com dados reais.

**Características:**
- Alta disponibilidade
- Backup automático
- Monitoramento 24/7
- SSL obrigatório
- Cache agressivo
- Rate limiting rigoroso

**URL:** `https://api.heartdevs.com`

**Configuração (.env.production):**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.heartdevs.com

DB_CONNECTION=mysql
DB_HOST=prod-db.internal
DB_PORT=3306
DB_DATABASE=he4rt_bot_prod
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

CACHE_DRIVER=redis
REDIS_HOST=prod-redis.internal

QUEUE_CONNECTION=redis

LOG_LEVEL=warning
LOG_CHANNEL=stack

SENTRY_DSN=${SENTRY_DSN}
```

**Deploy:**
```bash
# Via CI/CD ao criar tag de versão
git tag v1.2.3
git push origin v1.2.3
```

## 🔧 Configurações por Ambiente

### Banco de Dados

| Ambiente | Host | Database | Backup |
|----------|------|----------|--------|
| Development | localhost/mysql | he4rt_bot | Manual |
| Staging | staging-db.internal | he4rt_bot_staging | Diário |
| Production | prod-db.internal | he4rt_bot_prod | Horário + Diário |

### Cache/Redis

| Ambiente | Host | TTL Padrão | Persistência |
|----------|------|------------|--------------|
| Development | localhost/redis | 1 hora | Não |
| Staging | staging-redis | 6 horas | Sim |
| Production | prod-redis | 24 horas | Sim |

### Logs

| Ambiente | Nível | Destino | Rotação |
|----------|-------|---------|---------|
| Development | debug | Arquivo local | Nenhuma |
| Staging | info | Arquivo + Sentry | Semanal |
| Production | warning | Arquivo + Sentry | Diária |

### Rate Limiting

| Ambiente | Requisições/min | Burst |
|----------|-----------------|-------|
| Development | Ilimitado | - |
| Staging | 120 | 180 |
| Production | 60 | 100 |

## 🔐 Variáveis de Ambiente

### Obrigatórias em Todos os Ambientes

```env
APP_NAME="He4rtBot API"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://api.heartdevs.com

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
DISCORD_REDIRECT_URI=

BOT_TOKEN=
```

### Específicas de Produção

```env
# Monitoramento
SENTRY_DSN=https://...

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=

# AWS (se usar)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

# NewRelic (opcional)
NEW_RELIC_LICENSE_KEY=
```

## 📊 Recursos por Ambiente

### Development
- **CPU:** Compartilhado
- **RAM:** 2GB
- **Storage:** Local
- **Uptime:** N/A

### Staging
- **CPU:** 2 cores
- **RAM:** 4GB
- **Storage:** 50GB SSD
- **Uptime:** 99%

### Production
- **CPU:** 4+ cores
- **RAM:** 8GB+
- **Storage:** 100GB SSD
- **Uptime:** 99.9%
- **Backup:** Automático
- **CDN:** Cloudflare
- **Load Balancer:** Sim

## 🛡️ Segurança por Ambiente

### Development
- ✅ Debug habilitado
- ✅ CORS permissivo
- ⚠️ SSL opcional
- ⚠️ Secrets em .env

### Staging
- ✅ Debug limitado
- ✅ CORS configurado
- ✅ SSL obrigatório
- ✅ Secrets em vault

### Production
- ❌ Debug desabilitado
- ✅ CORS restritivo
- ✅ SSL obrigatório (HSTS)
- ✅ Secrets em vault
- ✅ WAF habilitado
- ✅ Rate limiting
- ✅ IP whitelist (admin)

## 🔄 Processo de Deploy

### Development → Staging

```bash
# 1. Criar PR para branch staging
git checkout -b feature/nova-funcionalidade
git push origin feature/nova-funcionalidade

# 2. Após aprovação, merge automático dispara CI/CD
# GitHub Actions executará:
# - Testes
# - Build
# - Deploy para staging
```

### Staging → Production

```bash
# 1. Testar em staging
# 2. Criar tag de versão
git tag -a v1.2.3 -m "Release v1.2.3"
git push origin v1.2.3

# 3. CI/CD automático:
# - Testes finais
# - Build de produção
# - Deploy com zero downtime
# - Rollback automático se falhar
```

## 🔍 Monitoramento

### Métricas Monitoradas

**Application:**
- Response time
- Error rate
- Request rate
- Cache hit rate

**Infrastructure:**
- CPU usage
- Memory usage
- Disk usage
- Network I/O

**Database:**
- Query time
- Connections
- Slow queries
- Deadlocks

### Ferramentas

- **APM:** Sentry, New Relic
- **Logs:** Papertrail, CloudWatch
- **Uptime:** UptimeRobot
- **Metrics:** Prometheus + Grafana

### Alertas

| Tipo | Threshold | Ação |
|------|-----------|------|
| Error Rate | > 5% | Slack + PagerDuty |
| Response Time | > 2s | Slack |
| CPU Usage | > 80% | Email |
| Disk Usage | > 90% | Email + Slack |
| Downtime | > 1min | PagerDuty |

## 🔙 Rollback

### Rollback Automático

CI/CD faz rollback automático se:
- Health check falhar após deploy
- Error rate > 10% nos primeiros 5 minutos
- Response time > 5s

### Rollback Manual

```bash
# Via CI/CD - Deploy de tag anterior
git tag
git push origin v1.2.2  # Tag anterior

# Ou via comando direto (emergência)
ssh production-server
cd /var/www/app
git checkout v1.2.2
php artisan migrate:rollback
php artisan config:cache
supervisorctl restart all
```

## 📋 Checklist de Deploy

### Pre-Deploy
- [ ] Todos os testes passando
- [ ] Code review aprovado
- [ ] Migrations testadas
- [ ] Rollback plan definido
- [ ] Stakeholders notificados
- [ ] Backup recente verificado

### Deploy
- [ ] Deploy executado com sucesso
- [ ] Migrations executadas
- [ ] Cache limpo
- [ ] Filas reiniciadas
- [ ] Health check OK

### Post-Deploy
- [ ] Monitoramento verificado
- [ ] Error rate normal
- [ ] Performance OK
- [ ] Features funcionando
- [ ] Documentação atualizada
- [ ] Changelog atualizado

## 🔗 Recursos Relacionados

- [CI/CD](./cicd.md) - Pipeline de integração contínua
- [Setup](../development/setup.md) - Configuração local

---

> 💡 **Dica**: Sempre teste em staging antes de produção.

> ⚠️ **Atenção**: Nunca commite arquivos `.env` com dados reais.
