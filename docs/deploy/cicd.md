---
title: CI/CD Pipeline
description: Integração e deploy contínuos
version: 1.0.0
last_updated: 2025-10-26
tags: [cicd, github-actions, automation, testing, deploy]
---

# 🔄 CI/CD Pipeline

## Visão Geral

O projeto utiliza GitHub Actions para CI/CD, automatizando testes, builds e deploys.

## 🎯 Workflows

### 1. Test Workflow (Pull Requests)

Executado em todo PR aberto.

**Arquivo:** `.github/workflows/test.yml`

```yaml
name: Tests

on:
  pull_request:
    branches: [main, staging]
  push:
    branches: [main, staging]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: secret
          MYSQL_DATABASE: he4rt_bot_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
      
      redis:
        image: redis:alpine
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, xml, bcmath, redis
          coverage: xdebug
      
      - name: Copy .env
        run: cp .env.testing .env
      
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Generate app key
        run: php artisan key:generate
      
      - name: Run migrations
        run: php artisan migrate --force
      
      - name: Run tests
        run: php artisan test --coverage --min=80
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
```

**Gatilhos:**
- Todo push em `main` ou `staging`
- Todo pull request para `main` ou `staging`

**Duração:** ~3-5 minutos

### 2. Deploy Staging Workflow

Deploy automático para staging ao fazer merge.

**Arquivo:** `.github/workflows/deploy-staging.yml`

```yaml
name: Deploy Staging

on:
  push:
    branches: [staging]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
      
      - name: Build assets
        run: |
          npm ci
          npm run build
      
      - name: Run tests
        run: php artisan test
      
      - name: Deploy to staging
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.STAGING_HOST }}
          username: ${{ secrets.STAGING_USER }}
          key: ${{ secrets.STAGING_SSH_KEY }}
          script: |
            cd /var/www/staging
            git pull origin staging
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
            sudo supervisorctl restart all
      
      - name: Health check
        run: |
          sleep 10
          curl --fail https://staging-api.heartdevs.com/health || exit 1
      
      - name: Notify Slack
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          text: 'Deploy para staging ${{ job.status }}'
          webhook_url: ${{ secrets.SLACK_WEBHOOK }}
        if: always()
```

**Gatilhos:**
- Push na branch `staging`

**Duração:** ~5-10 minutos

### 3. Deploy Production Workflow

Deploy para produção via tag de versão.

**Arquivo:** `.github/workflows/deploy-production.yml`

```yaml
name: Deploy Production

on:
  push:
    tags:
      - 'v*'

jobs:
  deploy:
    runs-on: ubuntu-latest
    environment: production
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
      
      - name: Run full test suite
        run: php artisan test --parallel
      
      - name: Build production assets
        run: |
          npm ci
          npm run build
        env:
          NODE_ENV: production
      
      - name: Create deployment
        uses: chrnorm/deployment-action@v2
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          environment: production
      
      - name: Deploy to production
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.PROD_HOST }}
          username: ${{ secrets.PROD_USER }}
          key: ${{ secrets.PROD_SSH_KEY }}
          script: |
            cd /var/www/production
            git fetch --tags
            git checkout ${{ github.ref_name }}
            composer install --no-dev --optimize-autoloader
            php artisan down
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
            php artisan up
            sudo supervisorctl restart all
      
      - name: Health check
        run: |
          sleep 15
          for i in {1..5}; do
            curl --fail https://api.heartdevs.com/health && break
            sleep 5
          done
      
      - name: Rollback on failure
        if: failure()
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.PROD_HOST }}
          username: ${{ secrets.PROD_USER }}
          key: ${{ secrets.PROD_SSH_KEY }}
          script: |
            cd /var/www/production
            git checkout $(git describe --tags --abbrev=0 HEAD^)
            composer install --no-dev --optimize-autoloader
            php artisan migrate:rollback --force
            php artisan config:cache
            php artisan up
            sudo supervisorctl restart all
      
      - name: Notify Slack
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          text: 'Deploy produção ${{ github.ref_name }}: ${{ job.status }}'
          webhook_url: ${{ secrets.SLACK_WEBHOOK }}
        if: always()
      
      - name: Create Sentry release
        uses: getsentry/action-release@v1
        env:
          SENTRY_AUTH_TOKEN: ${{ secrets.SENTRY_AUTH_TOKEN }}
          SENTRY_ORG: he4rt
          SENTRY_PROJECT: he4rt-bot-api
        with:
          environment: production
          version: ${{ github.ref_name }}
```

**Gatilhos:**
- Push de tag com formato `v*` (ex: v1.2.3)

**Duração:** ~10-15 minutos

### 4. Code Quality Workflow

Análise de qualidade de código.

**Arquivo:** `.github/workflows/quality.yml`

```yaml
name: Code Quality

on:
  pull_request:
    branches: [main, staging]

jobs:
  phpstan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install dependencies
        run: composer install
      
      - name: Run PHPStan
        run: ./vendor/bin/phpstan analyse
  
  psalm:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install dependencies
        run: composer install
      
      - name: Run Psalm
        run: ./vendor/bin/psalm
  
  phpcs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install dependencies
        run: composer install
      
      - name: Run PHP_CodeSniffer
        run: ./vendor/bin/phpcs --standard=PSR12 app/ Heart/
```

## 🔐 Secrets do GitHub

### Obrigatórios

```
# Staging
STAGING_HOST=staging.server.com
STAGING_USER=deploy
STAGING_SSH_KEY=<private-key>

# Production
PROD_HOST=production.server.com
PROD_USER=deploy
PROD_SSH_KEY=<private-key>

# Notificações
SLACK_WEBHOOK=https://hooks.slack.com/services/...

# Monitoramento
SENTRY_AUTH_TOKEN=...
CODECOV_TOKEN=...
```

### Configurar Secrets

```bash
# Via GitHub CLI
gh secret set STAGING_HOST -b"staging.server.com"
gh secret set STAGING_SSH_KEY < ~/.ssh/staging_key

# Ou via interface web:
# Settings → Secrets and variables → Actions → New repository secret
```

## 📊 Pipeline de Validação

### Ordem de Execução (PR)

```
1. Lint (PHP_CodeSniffer, PHPStan, Psalm)
   ↓ (2-3 min)
2. Unit Tests
   ↓ (3-5 min)
3. Feature Tests
   ↓ (5-10 min)
4. Coverage Report
   ↓ (1 min)
5. Security Scan
   ↓ (2 min)
6. Build Check
   ✓ Total: ~15-20 min
```

### Critérios de Aprovação

- ✅ Todos os testes passando
- ✅ Coverage >= 80%
- ✅ PHPStan nível 5+ sem erros
- ✅ PSR-12 compliant
- ✅ Sem vulnerabilidades críticas
- ✅ Aprovação de pelo menos 1 reviewer

## 🔍 Monitoramento de Deploy

### Métricas Coletadas

- **Deploy time:** Tempo total do deploy
- **Success rate:** Taxa de sucesso
- **Rollback rate:** Taxa de rollback
- **Time to recover:** Tempo para recuperação

### Dashboards

- **GitHub Actions:** Status dos workflows
- **Sentry:** Releases e errors
- **Grafana:** Métricas de infraestrutura

## 🚨 Tratamento de Erros

### Falha em Staging

```yaml
- name: Notify on failure
  if: failure()
  run: |
    curl -X POST $SLACK_WEBHOOK \
      -H 'Content-Type: application/json' \
      -d '{
        "text": "❌ Deploy staging falhou!",
        "attachments": [{
          "color": "danger",
          "fields": [
            {"title": "Branch", "value": "${{ github.ref }}"},
            {"title": "Commit", "value": "${{ github.sha }}"}
          ]
        }]
      }'
```

### Falha em Production

1. **Rollback automático** para última versão estável
2. **Notificação urgente** via PagerDuty + Slack
3. **Post-mortem** obrigatório
4. **Root cause analysis**

## 📋 Checklist de CI/CD

### Setup Inicial
- [ ] Workflows configurados
- [ ] Secrets adicionados
- [ ] SSH keys configuradas
- [ ] Notificações configuradas
- [ ] Monitoramento ativo

### Manutenção
- [ ] Revisar logs semanalmente
- [ ] Atualizar dependências mensalmente
- [ ] Verificar coverage trends
- [ ] Otimizar tempos de build

## 🔗 Recursos Relacionados

- [Environments](./environments.md) - Ambientes de deploy
- [Testing Guide](../testing/guide.md) - Testes automatizados

---

> 💡 **Dica**: Use branch protection rules para garantir CI passa antes de merge.

> ⚠️ **Atenção**: Sempre teste workflows em branches de feature antes de aplicar em main.
