---
title: Documentação He4rtBot Discord API
description: Índice principal da documentação técnica
version: 1.0.0
last_updated: 2025-10-26
tags: [documentation, index, navigation]
---

# 📚 Documentação He4rtBot Discord API

Bem-vindo à documentação completa do He4rtBot Discord API! Este guia fornece informações detalhadas sobre arquitetura, desenvolvimento, testes e uso da API.

## 🗺️ Navegação Rápida

### 🏗️ Arquitetura
- **[Visão Geral da Arquitetura](./architecture/overview.md)**
  - Domain-Driven Design (DDD)
  - Estrutura de módulos
  - Princípios e padrões
  - Fluxo de dados
  
- **[Módulos do Sistema](./architecture/modules.md)**
  - Authentication
  - User
  - Badges & Characters
  - Ranking & Seasons
  - Meetings
  - Feedback
  - Messages
  - Providers & Integrations

### 🌐 API
- **[Endpoints](./api/endpoints.md)**
  - Autenticação
  - Usuários
  - Gamificação
  - Eventos
  - Rankings
  - Feedback
  
- **[Autenticação e Autorização](./api/authentication.md)**
  - OAuth Flow
  - Bot Authentication
  - Tokens e Middlewares

- **[Responses e Errors](./api/responses.md)**
  - Formato de resposta padrão
  - Códigos de erro
  - Rate limiting

### 🗄️ Banco de Dados
- **[Schema](./database/schema.md)**
  - Diagrama ER
  - Tabelas principais
  - Relacionamentos
  
- **[Migrations](./database/migrations.md)**
  - Ordem de execução
  - Seeders
  - Dados de teste

### 🛠️ Desenvolvimento
- **[Setup do Ambiente](./development/setup.md)**
  - Requisitos
  - Instalação
  - Configuração
  - Troubleshooting
  
- **[Docker](./development/docker.md)**
  - Containers
  - Docker Compose
  - Comandos úteis
  - Debugging

- **[Padrões de Código](./development/code-standards.md)**
  - PSR-12
  - Laravel Best Practices
  - DDD Guidelines
  - Code Review Checklist

### 🧪 Testes
- **[Guia de Testes](./testing/guide.md)**
  - Estrutura de testes
  - Feature vs Unit
  - Mocks e Factories
  - Cobertura

- **[Exemplos de Testes](./testing/examples.md)**
  - Testes de API
  - Testes de Domínio
  - Testes de Integração

### 🚀 Deploy
- **[Ambientes](./deploy/environments.md)**
  - Development
  - Staging
  - Production

- **[CI/CD](./deploy/cicd.md)**
  - GitHub Actions
  - Automated Testing
  - Deployment Pipeline

## 📖 Guias Por Contexto

### 👨‍💻 Para Desenvolvedores

**Iniciando no Projeto:**
1. Leia a [Visão Geral da Arquitetura](./architecture/overview.md)
2. Configure o [Ambiente de Desenvolvimento](./development/setup.md)
3. Entenda os [Padrões de Código](./development/code-standards.md)
4. Explore os [Módulos](./architecture/modules.md)
5. Execute os [Testes](./testing/guide.md)

**Adicionando uma Nova Feature:**
1. Identifique o módulo apropriado em [Módulos](./architecture/modules.md)
2. Siga os padrões DDD da [Arquitetura](./architecture/overview.md)
3. Implemente seguindo [Code Standards](./development/code-standards.md)
4. Adicione testes conforme [Guia de Testes](./testing/guide.md)
5. Documente os novos endpoints em [API](./api/endpoints.md)

### 🔌 Para Integradores

**Consumindo a API:**
1. Leia a documentação de [Endpoints](./api/endpoints.md)
2. Entenda o fluxo de [Autenticação](./api/authentication.md)
3. Veja exemplos de [Responses](./api/responses.md)
4. Consulte o [Schema do Banco](./database/schema.md) para entender os dados

### 🎨 Para Arquitetos

**Entendendo o Sistema:**
1. [Visão Geral da Arquitetura](./architecture/overview.md) - DDD e padrões
2. [Módulos](./architecture/modules.md) - Bounded contexts
3. [Schema do Banco](./database/schema.md) - Modelo de dados
4. [Fluxos de Integração](./architecture/integrations.md)

## 🔍 Recursos por Tópico

### 🎮 Gamificação
- [User Module](./architecture/modules.md#user)
- [Ranking System](./architecture/modules.md#ranking)
- [Seasons](./architecture/modules.md#season)
- [Badges](./architecture/modules.md#badges)
- [Endpoints de Gamificação](./api/endpoints.md#gamification)

### 🏆 Sistema de Badges
- [Badge Module](./architecture/modules.md#badges)
- [Character Module](./architecture/modules.md#character)
- [Badge Endpoints](./api/endpoints.md#badges)
- [Badge Schema](./database/schema.md#badges)

### 👥 Gestão de Usuários
- [User Module](./architecture/modules.md#user)
- [Authentication](./api/authentication.md)
- [User Endpoints](./api/endpoints.md#users)
- [User Schema](./database/schema.md#users)

### 📅 Eventos e Meetings
- [Meeting Module](./architecture/modules.md#meeting)
- [Event Endpoints](./api/endpoints.md#events)
- [Meeting Schema](./database/schema.md#meetings)

## 📝 Convenções da Documentação

Esta documentação segue alguns padrões para facilitar a navegação:

### Frontmatter
Todos os documentos incluem metadados YAML no início:
```yaml
---
title: Título do Documento
description: Breve descrição
version: 1.0.0
tags: [tag1, tag2]
---
```

### Ícones
- 🏗️ Arquitetura
- 🌐 API
- 🗄️ Banco de Dados
- 🛠️ Desenvolvimento
- 🧪 Testes
- 🚀 Deploy
- 💡 Dica
- ⚠️ Atenção
- ❌ Erro Comum
- ✅ Boa Prática

### Blocos de Código
```php
// Exemplo de código PHP
public function example(): void
{
    // código aqui
}
```

### Notas e Avisos

> 💡 **Dica**: Informações úteis e sugestões

> ⚠️ **Atenção**: Pontos importantes a considerar

> ❌ **Erro Comum**: Erros frequentes a evitar

> ✅ **Boa Prática**: Recomendações e melhores práticas

## 🔄 Atualizações

Esta documentação é viva e está em constante atualização. Contribuições são bem-vindas!

- **Última atualização**: 26 de outubro de 2025
- **Versão**: 1.0.0
- **Contribuidores**: [Ver no GitHub](https://github.com/he4rt/he4rt-bot-api/graphs/contributors)

## 📞 Suporte

- **Issues**: [GitHub Issues](https://github.com/he4rt/he4rt-bot-api/issues)
- **Discussions**: [GitHub Discussions](https://github.com/he4rt/he4rt-bot-api/discussions)
- **Discord**: [He4rt Developers](https://discord.gg/he4rt)

---

<p align="center">
  Feito com 💜 pela comunidade <a href="https://discord.gg/he4rt">He4rt Developers</a>
</p>
