---
title: Autenticação e Autorização
description: Sistema de autenticação OAuth e Bot Authentication
version: 1.0.0
last_updated: 2025-10-26
tags: [api, authentication, oauth, security]
---

# 🔐 Autenticação e Autorização

## Visão Geral

O He4rtBot API utiliza dois métodos de autenticação:

1. **OAuth 2.0** - Para autenticação de usuários via Discord
2. **Bot Authentication** - Para comunicação entre o bot Discord e a API

## 🎫 OAuth 2.0 Flow

### Discord OAuth

O sistema utiliza o fluxo OAuth do Discord para autenticar usuários.

#### 1. Iniciar Autenticação

```http
GET /api/auth/discord/redirect
```

**Response:**
```json
{
  "success": true,
  "data": {
    "authorization_url": "https://discord.com/api/oauth2/authorize?..."
  }
}
```

#### 2. Callback do Discord

Após o usuário autorizar, o Discord redireciona para:

```http
GET /api/auth/discord/callback?code=AUTHORIZATION_CODE
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "discord_id": "123456789",
      "username": "usuario",
      "discriminator": "1234",
      "avatar": "hash_do_avatar"
    }
  }
}
```

#### 3. Usar Token nas Requisições

Todas as requisições autenticadas devem incluir o token no header:

```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Exemplo:**

```bash
curl -X GET https://api.heartdevs.com/api/users/me \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

## 🤖 Bot Authentication

Para comunicação entre o bot Discord e a API.

### Headers Obrigatórios

```http
X-Bot-Token: secret_bot_token_aqui
Content-Type: application/json
```

### Exemplo de Requisição

```bash
curl -X POST https://api.heartdevs.com/api/messages \
  -H "X-Bot-Token: secret_bot_token_aqui" \
  -H "Content-Type: application/json" \
  -d '{
    "discord_id": "123456789",
    "content": "Mensagem do usuário",
    "channel_id": "987654321"
  }'
```

## 🔒 Middlewares de Autenticação

### auth:sanctum

Middleware Laravel Sanctum para rotas que requerem usuário autenticado.

**Uso:**
```php
Route::middleware('auth:sanctum')->get('/users/me', [UserController::class, 'me']);
```

**Validação:**
- Verifica token Bearer no header
- Valida expiração do token
- Carrega o usuário associado

### bot.auth

Middleware customizado para autenticação do bot.

**Uso:**
```php
Route::middleware('bot.auth')->post('/messages', [MessageController::class, 'store']);
```

**Validação:**
- Verifica X-Bot-Token no header
- Compara com token configurado em `.env`
- Bloqueia requisição se inválido

## 🎭 Roles e Permissões

### Roles Disponíveis

| Role | Descrição |
|------|-----------|
| `admin` | Acesso total ao sistema |
| `moderator` | Pode moderar conteúdo e usuários |
| `user` | Usuário padrão |
| `beta_tester` | Acesso a funcionalidades em beta |

### Verificação de Permissões

#### No Backend

```php
// Verificar se usuário tem role específica
if ($user->hasRole('admin')) {
    // Código para admins
}

// Verificar múltiplas roles
if ($user->hasAnyRole(['admin', 'moderator'])) {
    // Código para admins ou moderadores
}
```

#### Middleware de Role

```php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'users']);
});
```

## 🔑 Tokens

### Tipos de Token

#### 1. Access Token (JWT)
- **Duração:** 1 hora (configurável)
- **Uso:** Autenticação de requisições
- **Renovação:** Via refresh token

#### 2. Refresh Token
- **Duração:** 30 dias (configurável)
- **Uso:** Obter novo access token
- **Armazenamento:** Cookie httpOnly

#### 3. Bot Token
- **Duração:** Indefinida
- **Uso:** Autenticação do bot
- **Armazenamento:** `.env`

### Renovar Access Token

```http
POST /api/auth/refresh
Content-Type: application/json
```

**Request Body:**
```json
{
  "refresh_token": "refresh_token_aqui"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "novo_token_aqui",
    "expires_in": 3600
  }
}
```

## 🚫 Logout

### Endpoint

```http
POST /api/auth/logout
Authorization: Bearer token_aqui
```

**Response:**
```json
{
  "success": true,
  "message": "Logout realizado com sucesso"
}
```

**Ações realizadas:**
- Revoga o token atual
- Remove refresh token
- Limpa cookies de sessão

## 🛡️ Segurança

### Rate Limiting

Todas as rotas de autenticação possuem rate limiting:

- **Login/Register:** 5 tentativas por minuto
- **Refresh Token:** 10 tentativas por minuto
- **Rotas autenticadas:** 60 requisições por minuto

### CORS

Configuração em `config/cors.php`:

```php
'allowed_origins' => [
    'https://heartdevs.com',
    'https://app.heartdevs.com',
],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
'allowed_headers' => ['Content-Type', 'Authorization', 'X-Bot-Token'],
```

### Headers de Segurança

Todas as respostas incluem:

```http
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

## ⚠️ Erros Comuns

### 401 Unauthorized

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Token inválido ou expirado"
  }
}
```

**Causas:**
- Token expirado
- Token inválido ou malformado
- Token revogado (logout)
- Header Authorization ausente

**Solução:**
- Renovar token via `/api/auth/refresh`
- Fazer login novamente

### 403 Forbidden

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Permissão negada"
  }
}
```

**Causas:**
- Usuário sem role necessária
- Tentativa de acessar recurso de outro usuário
- IP bloqueado

### 429 Too Many Requests

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Muitas requisições. Tente novamente em 60 segundos",
    "retry_after": 60
  }
}
```

## 📝 Configuração

### Variáveis de Ambiente

```env
# Discord OAuth
DISCORD_CLIENT_ID=your_client_id
DISCORD_CLIENT_SECRET=your_client_secret
DISCORD_REDIRECT_URI=https://api.heartdevs.com/api/auth/discord/callback

# Bot Authentication
BOT_TOKEN=secret_bot_token_here

# JWT
JWT_SECRET=your_jwt_secret
JWT_TTL=60  # minutos

# Sanctum
SANCTUM_STATEFUL_DOMAINS=heartdevs.com,app.heartdevs.com
SESSION_DOMAIN=.heartdevs.com
```

## 🧪 Testando Autenticação

### Obter Token de Teste

Para desenvolvimento, você pode criar um token manualmente:

```php
// Em tinker ou seeder
$user = User::find(1);
$token = $user->createToken('test-token')->plainTextToken;
echo $token;
```

### Exemplos de Requisições

#### Com cURL

```bash
# Usando OAuth token
curl -X GET https://api.heartdevs.com/api/users/me \
  -H "Authorization: Bearer seu_token_aqui"

# Usando Bot token
curl -X POST https://api.heartdevs.com/api/messages \
  -H "X-Bot-Token: bot_token_aqui" \
  -H "Content-Type: application/json" \
  -d '{"discord_id": "123", "content": "teste"}'
```

#### Com JavaScript (Fetch)

```javascript
// OAuth request
const response = await fetch('https://api.heartdevs.com/api/users/me', {
  headers: {
    'Authorization': `Bearer ${accessToken}`,
    'Content-Type': 'application/json'
  }
});

// Bot request
const response = await fetch('https://api.heartdevs.com/api/messages', {
  method: 'POST',
  headers: {
    'X-Bot-Token': botToken,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    discord_id: '123456789',
    content: 'Mensagem do bot'
  })
});
```

## 🔗 Recursos Relacionados

- [Endpoints](./endpoints.md) - Lista completa de endpoints
- [Responses](./responses.md) - Formato de respostas e erros
- [User Module](../architecture/modules.md#user) - Arquitetura do módulo de usuários
- [Authentication Module](../architecture/modules.md#authentication) - Arquitetura do módulo de autenticação

---

> 💡 **Dica**: Em desenvolvimento, use o token de teste gerado via tinker. Em produção, sempre use o fluxo OAuth completo.

> ⚠️ **Atenção**: Nunca commite tokens reais no código ou `.env`. Use variáveis de ambiente.
