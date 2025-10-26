---
title: Formato de Respostas e Erros
description: Padrões de response da API
version: 1.0.0
last_updated: 2025-10-26
tags: [api, responses, errors, http, standards]
---

# 📨 Formato de Respostas e Erros

## Visão Geral

Todas as respostas da API seguem um formato padronizado JSON para facilitar o consumo e tratamento de erros.

## ✅ Respostas de Sucesso

### Formato Padrão

```json
{
  "success": true,
  "data": {
    // Dados da resposta
  },
  "meta": {
    // Metadados opcionais (paginação, timestamps, etc)
  }
}
```

### Exemplo: Buscar Usuário

```json
{
  "success": true,
  "data": {
    "id": 1,
    "discord_id": "123456789",
    "username": "danielhe4rt",
    "level": 42,
    "xp": 15750,
    "created_at": "2023-01-15T10:30:00.000000Z"
  }
}
```

### Exemplo: Lista com Paginação

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "username": "usuario1"
    },
    {
      "id": 2,
      "username": "usuario2"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "https://api.heartdevs.com/api/users?page=1",
    "last": "https://api.heartdevs.com/api/users?page=3",
    "prev": null,
    "next": "https://api.heartdevs.com/api/users?page=2"
  }
}
```

### Exemplo: Operação sem Retorno de Dados

```json
{
  "success": true,
  "message": "Operação realizada com sucesso"
}
```

## ❌ Respostas de Erro

### Formato Padrão

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensagem de erro legível",
    "details": {
      // Detalhes adicionais (opcional)
    }
  }
}
```

### Códigos HTTP Utilizados

| Código | Significado | Quando Usar |
|--------|-------------|-------------|
| `200` | OK | Requisição bem-sucedida |
| `201` | Created | Recurso criado com sucesso |
| `204` | No Content | Sucesso sem corpo de resposta |
| `400` | Bad Request | Dados inválidos ou malformados |
| `401` | Unauthorized | Token ausente ou inválido |
| `403` | Forbidden | Sem permissão para o recurso |
| `404` | Not Found | Recurso não encontrado |
| `422` | Unprocessable Entity | Erros de validação |
| `429` | Too Many Requests | Rate limit excedido |
| `500` | Internal Server Error | Erro interno do servidor |
| `503` | Service Unavailable | Serviço temporariamente indisponível |

## 🔍 Tipos de Erro

### 1. Erros de Validação (422)

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Os dados fornecidos são inválidos",
    "details": {
      "email": [
        "O campo email é obrigatório",
        "O email deve ser válido"
      ],
      "username": [
        "O username deve ter no mínimo 3 caracteres"
      ]
    }
  }
}
```

### 2. Erros de Autenticação (401)

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Token inválido ou expirado"
  }
}
```

**Códigos Possíveis:**
- `UNAUTHORIZED` - Token ausente, inválido ou expirado
- `TOKEN_EXPIRED` - Token expirado (renovável)
- `TOKEN_INVALID` - Token malformado
- `CREDENTIALS_INVALID` - Credenciais incorretas

### 3. Erros de Autorização (403)

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Você não tem permissão para acessar este recurso"
  }
}
```

**Códigos Possíveis:**
- `FORBIDDEN` - Sem permissão
- `INSUFFICIENT_ROLE` - Role insuficiente
- `RESOURCE_FORBIDDEN` - Recurso específico proibido

### 4. Recurso Não Encontrado (404)

```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Usuário não encontrado",
    "details": {
      "resource": "User",
      "identifier": "123456789"
    }
  }
}
```

### 5. Rate Limiting (429)

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Muitas requisições. Tente novamente em 60 segundos",
    "details": {
      "retry_after": 60,
      "limit": 60,
      "remaining": 0
    }
  }
}
```

**Headers de Rate Limit:**
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1698345600
Retry-After: 60
```

### 6. Erros do Servidor (500)

```json
{
  "success": false,
  "error": {
    "code": "INTERNAL_ERROR",
    "message": "Ocorreu um erro interno. Por favor, tente novamente mais tarde",
    "details": {
      "error_id": "uuid-do-erro",
      "timestamp": "2025-10-26T15:30:00Z"
    }
  }
}
```

> 💡 **Nota**: Em produção, detalhes técnicos são omitidos. Em desenvolvimento, o erro completo é retornado.

### 7. Erro de Negócio (400)

```json
{
  "success": false,
  "error": {
    "code": "BUSINESS_RULE_VIOLATION",
    "message": "Não é possível deletar um badge em uso",
    "details": {
      "rule": "badge_in_use",
      "affected_users": 15
    }
  }
}
```

## 📊 Códigos de Erro Customizados

### Autenticação
- `UNAUTHORIZED` - Não autenticado
- `TOKEN_EXPIRED` - Token expirado
- `TOKEN_INVALID` - Token inválido
- `CREDENTIALS_INVALID` - Credenciais incorretas
- `ACCOUNT_DISABLED` - Conta desativada

### Autorização
- `FORBIDDEN` - Sem permissão
- `INSUFFICIENT_ROLE` - Role insuficiente
- `RESOURCE_FORBIDDEN` - Recurso específico proibido

### Validação
- `VALIDATION_ERROR` - Erros de validação
- `INVALID_INPUT` - Input inválido
- `MISSING_FIELD` - Campo obrigatório ausente
- `INVALID_FORMAT` - Formato inválido

### Recursos
- `NOT_FOUND` - Recurso não encontrado
- `ALREADY_EXISTS` - Recurso já existe
- `DUPLICATE_ENTRY` - Entrada duplicada

### Rate Limiting
- `RATE_LIMIT_EXCEEDED` - Limite excedido
- `TOO_MANY_REQUESTS` - Muitas requisições

### Servidor
- `INTERNAL_ERROR` - Erro interno
- `SERVICE_UNAVAILABLE` - Serviço indisponível
- `DATABASE_ERROR` - Erro de banco de dados
- `EXTERNAL_API_ERROR` - Erro de API externa

### Negócio
- `BUSINESS_RULE_VIOLATION` - Violação de regra de negócio
- `INSUFFICIENT_XP` - XP insuficiente
- `LEVEL_REQUIREMENT_NOT_MET` - Nível insuficiente
- `COOLDOWN_ACTIVE` - Ação em cooldown
- `SEASON_ENDED` - Temporada encerrada

## 🎯 Headers Padrão

### Requisição

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer token_aqui
X-Request-ID: uuid-da-requisicao (opcional)
```

### Resposta

```http
Content-Type: application/json
X-Request-ID: uuid-da-requisicao
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1698345600
```

## 🔄 Paginação

### Parâmetros Query String

```
?page=1          # Página atual (padrão: 1)
?per_page=15     # Items por página (padrão: 15, máx: 100)
?sort=created_at # Campo para ordenação
?order=desc      # Direção (asc/desc)
```

### Exemplo de URL

```
GET /api/users?page=2&per_page=20&sort=level&order=desc
```

### Resposta com Paginação

```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 2,
    "from": 21,
    "to": 40,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  },
  "links": {
    "first": "https://api.heartdevs.com/api/users?page=1",
    "last": "https://api.heartdevs.com/api/users?page=8",
    "prev": "https://api.heartdevs.com/api/users?page=1",
    "next": "https://api.heartdevs.com/api/users?page=3"
  }
}
```

## 🎨 Filtros e Busca

### Parâmetros de Filtro

```
?filter[username]=daniel   # Filtro por username
?filter[level_min]=10      # Nível mínimo
?filter[level_max]=50      # Nível máximo
?search=termo              # Busca geral
```

### Exemplo

```
GET /api/users?filter[level_min]=20&filter[level_max]=50&search=dev
```

## 📝 Formato de Datas

Todas as datas seguem o padrão **ISO 8601**:

```json
{
  "created_at": "2025-10-26T15:30:00.000000Z",
  "updated_at": "2025-10-26T15:30:00.000000Z"
}
```

**Timezone:** UTC (Z)

## 🧪 Exemplos de Consumo

### JavaScript (Fetch)

```javascript
async function getUser(userId) {
  try {
    const response = await fetch(`https://api.heartdevs.com/api/users/${userId}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    const result = await response.json();
    
    if (!result.success) {
      console.error('Erro:', result.error.message);
      return null;
    }
    
    return result.data;
  } catch (error) {
    console.error('Erro de rede:', error);
    return null;
  }
}
```

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'https://api.heartdevs.com']);

try {
    $response = $client->get('/api/users/1', [
        'headers' => [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ]
    ]);
    
    $result = json_decode($response->getBody(), true);
    
    if ($result['success']) {
        $user = $result['data'];
    }
} catch (\Exception $e) {
    // Tratar erro
}
```

### Python (Requests)

```python
import requests

headers = {
    'Authorization': f'Bearer {token}',
    'Content-Type': 'application/json'
}

response = requests.get('https://api.heartdevs.com/api/users/1', headers=headers)
result = response.json()

if result['success']:
    user = result['data']
else:
    print('Erro:', result['error']['message'])
```

## 🛡️ Tratamento de Erros

### Boas Práticas

1. **Sempre verifique `success`:**
```javascript
if (!response.success) {
  // Tratar erro
}
```

2. **Use o código do erro:**
```javascript
switch (response.error.code) {
  case 'UNAUTHORIZED':
    // Redirecionar para login
    break;
  case 'RATE_LIMIT_EXCEEDED':
    // Aguardar retry_after segundos
    break;
  default:
    // Erro genérico
}
```

3. **Mostre mensagens amigáveis:**
```javascript
const errorMessages = {
  'UNAUTHORIZED': 'Sessão expirada. Faça login novamente.',
  'FORBIDDEN': 'Você não tem permissão para esta ação.',
  'NOT_FOUND': 'Recurso não encontrado.',
  'VALIDATION_ERROR': 'Verifique os dados e tente novamente.'
};

const friendlyMessage = errorMessages[error.code] || error.message;
```

## 🔗 Recursos Relacionados

- [Endpoints](./endpoints.md) - Lista completa de endpoints
- [Autenticação](./authentication.md) - Sistema de autenticação
- [Development Setup](../development/setup.md) - Ambiente de desenvolvimento

---

> 💡 **Dica**: Use o header `X-Request-ID` para rastrear requisições específicas nos logs.

> ⚠️ **Atenção**: Em produção, erros 500 não expõem detalhes técnicos. Use o `error_id` para buscar nos logs.
