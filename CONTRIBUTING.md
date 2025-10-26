---
title: Guia de Contribuição
description: Como contribuir para o He4rtBot Discord API
version: 1.0.0
tags: [contributing, guidelines, standards, workflow]
---

# Guia de Contribuição

Obrigado por considerar contribuir com o He4rtBot Discord API! 💜

Este documento fornece diretrizes e melhores práticas para contribuir com o projeto.

## 📋 Índice

- [Código de Conduta](#-código-de-conduta)
- [Como Posso Contribuir?](#-como-posso-contribuir)
  - [Reportando Bugs](#reportando-bugs)
  - [Sugerindo Melhorias](#sugerindo-melhorias)
  - [Contribuindo com Código](#contribuindo-com-código)
  - [Melhorando a Documentação](#melhorando-a-documentação)
- [Padrões de Desenvolvimento](#-padrões-de-desenvolvimento)
  - [Conventional Commits](#conventional-commits)
  - [Branches](#branches)
  - [Code Style](#code-style)
  - [Testes](#testes)
- [Processo de Pull Request](#-processo-de-pull-request)
- [Estrutura de Commits](#-estrutura-de-commits)

## 📜 Código de Conduta

Este projeto adere ao [Código de Conduta](./CODE_OF_CONDUCT.md). Ao participar, você concorda em manter este código. Por favor, reporte comportamentos inaceitáveis para os moderadores da comunidade He4rt.

## 🤝 Como Posso Contribuir?

### Reportando Bugs

Antes de criar um relatório de bug, verifique se o problema já não foi reportado. Se encontrar um issue existente, adicione um comentário ao invés de abrir um novo.

**Como reportar um bug:**

1. Use um título claro e descritivo
2. Descreva os passos para reproduzir o problema
3. Descreva o comportamento esperado e o atual
4. Inclua screenshots ou GIFs se aplicável
5. Mencione a versão do projeto e ambiente
6. Adicione qualquer informação adicional relevante

**Template de Issue para Bugs:**

```markdown
## Descrição
[Descrição clara do bug]

## Passos para Reproduzir
1. ...
2. ...
3. ...

## Comportamento Esperado
[O que deveria acontecer]

## Comportamento Atual
[O que está acontecendo]

## Ambiente
- OS: [ex: Ubuntu 22.04]
- Docker version: [ex: 24.0.0]
- PHP version: [ex: 8.3]
- Laravel version: [ex: 12.8.1]

## Informações Adicionais
[Logs, screenshots, etc]
```

### Sugerindo Melhorias

Sugestões de melhorias são sempre bem-vindas! Isso inclui novas funcionalidades, melhorias em funcionalidades existentes, ou mudanças de arquitetura.

**Template de Issue para Features:**

```markdown
## Descrição da Funcionalidade
[Descrição clara e concisa da funcionalidade]

## Problema que Resolve
[Qual problema esta funcionalidade resolve?]

## Solução Proposta
[Como você imagina que isso deveria funcionar?]

## Alternativas Consideradas
[Outras abordagens que você considerou]

## Contexto Adicional
[Qualquer outra informação relevante]
```

### Contribuindo com Código

1. **Fork** o repositório
2. **Clone** seu fork localmente
3. **Crie uma branch** seguindo nossos padrões
4. **Implemente** suas mudanças
5. **Adicione testes** para suas mudanças
6. **Execute os testes** existentes
7. **Commit** suas mudanças seguindo Conventional Commits
8. **Push** para seu fork
9. **Abra um Pull Request**

### Melhorando a Documentação

Documentação é crucial! Contribuições para melhorar a documentação são tão valiosas quanto código.

- Corrija erros de digitação
- Melhore explicações
- Adicione exemplos
- Traduza documentação
- Crie tutoriais

## 🎨 Padrões de Desenvolvimento

### Conventional Commits

Seguimos a especificação [Conventional Commits](https://www.conventionalcommits.org/) para mensagens de commit.

#### Formato

```
<tipo>[escopo opcional]: <descrição>

[corpo opcional]

[rodapé opcional]
```

#### Tipos

- **feat**: Nova funcionalidade
- **fix**: Correção de bug
- **docs**: Apenas documentação
- **style**: Formatação, missing semi colons, etc (sem mudança de código)
- **refactor**: Refatoração de código
- **perf**: Melhoria de performance
- **test**: Adição ou correção de testes
- **build**: Mudanças no build ou dependências
- **ci**: Mudanças em CI/CD
- **chore**: Outras mudanças que não modificam src ou test

#### Exemplos

```bash
# Adicionar nova funcionalidade
feat(user): add daily reward system

# Corrigir bug
fix(ranking): correct season calculation logic

# Documentação
docs(api): update endpoints documentation

# Refatoração
refactor(badges): extract claim logic to service

# Testes
test(meeting): add unit tests for meeting domain

# Breaking change
feat(auth)!: change OAuth flow to use PKCE

BREAKING CHANGE: OAuth now requires PKCE flow
```

#### Escopos Sugeridos

- `auth` / `authentication`
- `user`
- `badge` / `badges`
- `character`
- `feedback`
- `meeting`
- `message`
- `provider`
- `ranking`
- `season`
- `core`
- `api`
- `docker`
- `ci`

### Branches

#### Nomenclatura de Branches

```
<tipo>/<descrição-curta>
```

**Tipos:**
- `feat/` - Nova funcionalidade
- `fix/` - Correção de bug
- `docs/` - Documentação
- `refactor/` - Refatoração
- `test/` - Testes
- `chore/` - Manutenção

**Exemplos:**
```bash
feat/daily-reward-system
fix/ranking-calculation-bug
docs/api-endpoints
refactor/badge-service
test/meeting-domain
chore/update-dependencies
```

### Code Style

#### PHP

Seguimos o **PSR-12** como padrão de código PHP.

```bash
# Verificar code style
docker exec -it discord-bot-api vendor/bin/phpcs

# Corrigir automaticamente
docker exec -it discord-bot-api vendor/bin/phpcbf

# Laravel Pint (alternativa)
docker exec -it discord-bot-api vendor/bin/pint
```

**Regras Principais:**

- Use **type hints** sempre que possível
- Declare **return types** em todos os métodos
- Use **strict types**: `declare(strict_types=1);`
- Prefira **named arguments** quando apropriado
- Documente métodos complexos com PHPDoc
- Mantenha métodos pequenos e focados (< 20 linhas idealmente)

**Exemplo:**

```php
<?php

declare(strict_types=1);

namespace Heart\User\Application\UseCases;

use Heart\User\Domain\Entities\User;
use Heart\User\Domain\Repositories\UserRepositoryInterface;
use Heart\Core\DTO\UserDTO;

final class CreateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(UserDTO $userData): User
    {
        $user = User::create(
            discordId: $userData->discordId,
            username: $userData->username,
            discriminator: $userData->discriminator
        );

        return $this->userRepository->save($user);
    }
}
```

#### Arquitetura DDD

**Módulos devem seguir a estrutura:**

```
Heart/ModuleName/
├── Application/
│   ├── UseCases/
│   ├── Services/
│   └── DTOs/
├── Domain/
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Repositories/
│   ├── Events/
│   └── Exceptions/
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Models/
│   │   └── Repositories/
│   ├── Http/
│   └── External/
└── Presentation/
    ├── Controllers/
    ├── Resources/
    └── Requests/
```

**Princípios:**

- **Dependências sempre apontam para dentro** (Domain não conhece Infrastructure)
- **Use interfaces** para abstrair implementações
- **Entidades de domínio** são Plain Old PHP Objects (POPOs)
- **Use Value Objects** para conceitos importantes
- **Use Events** para comunicação entre módulos

### Testes

#### Cobertura Mínima

- **Feature Tests**: Testes de integração dos endpoints
- **Unit Tests**: Testes de lógica de negócio (Domain, UseCases)
- **Cobertura mínima**: 70% (ideal 80%+)

#### Estrutura de Testes

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Tests\TestCase;
use Heart\User\Domain\Entities\User;

final class CreateUserTest extends TestCase
{
    /** @test */
    public function it_can_create_a_new_user(): void
    {
        // Arrange
        $userData = [
            'discord_id' => '123456789',
            'username' => 'johndoe',
            'discriminator' => '1234'
        ];

        // Act
        $response = $this->postJson('/api/users', $userData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'discord_id' => '123456789',
                'username' => 'johndoe'
            ]);

        $this->assertDatabaseHas('users', [
            'discord_id' => '123456789'
        ]);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discord_id', 'username']);
    }
}
```

#### Boas Práticas

- Use **nomes descritivos**: `it_can_create_a_new_user` ao invés de `testCreate`
- Siga **AAA**: Arrange, Act, Assert
- **Isole testes**: use transactions ou refresh database
- **Mock externo**: APIs, services externos
- **Use factories** para criar dados de teste
- **Um assert principal por teste** (pode ter asserts auxiliares)

## 🔄 Processo de Pull Request

### Antes de Abrir o PR

- [ ] Código segue os padrões do projeto
- [ ] Testes foram adicionados/atualizados
- [ ] Todos os testes passam
- [ ] Documentação foi atualizada
- [ ] Commits seguem Conventional Commits
- [ ] Branch está atualizada com `main`

### Template de Pull Request

```markdown
## Descrição

[Descrição clara do que o PR faz]

## Tipo de Mudança

- [ ] 🐛 Bug fix (mudança que corrige um problema)
- [ ] ✨ Nova funcionalidade (mudança que adiciona funcionalidade)
- [ ] 💥 Breaking change (fix ou feature que quebra compatibilidade)
- [ ] 📝 Documentação
- [ ] 🎨 Refatoração
- [ ] ⚡ Performance
- [ ] ✅ Testes

## Como Testar?

1. ...
2. ...
3. ...

## Checklist

- [ ] Código segue os padrões do projeto
- [ ] Testes adicionados/atualizados
- [ ] Todos os testes passam
- [ ] Documentação atualizada
- [ ] Commits seguem Conventional Commits

## Issues Relacionadas

Closes #[número]

## Screenshots (se aplicável)

[Adicione screenshots]
```

### Revisão de Código

**O que esperamos dos revisores:**

- Seja construtivo e respeitoso
- Explique o "porquê" dos seus comentários
- Sugira alternativas quando possível
- Aprove quando estiver satisfeito

**O que esperamos dos autores:**

- Responda aos comentários
- Faça as mudanças solicitadas ou explique por que não
- Seja receptivo ao feedback
- Mantenha a discussão técnica e profissional

### Merge

Pull Requests serão merged quando:

- ✅ Aprovado por pelo menos 1 reviewer (2 para mudanças críticas)
- ✅ Todos os checks de CI passam
- ✅ Conflitos resolvidos
- ✅ Discussões resolvidas

## 📝 Estrutura de Commits

### Commit Atômico

Cada commit deve representar uma mudança lógica completa:

```bash
# ❌ Ruim
git commit -m "fix stuff"

# ✅ Bom
git commit -m "fix(ranking): correct XP calculation for voice channels"
```

### Rebase vs Merge

- **Use rebase** para manter histórico limpo em branches de feature
- **Use merge** para integrar features na main (squash se muitos commits)

```bash
# Atualizar sua branch com main
git fetch origin
git rebase origin/main

# Se houver conflitos
git rebase --continue
# ou
git rebase --abort
```

## 🎓 Recursos Adicionais

- [Laravel Documentation](https://laravel.com/docs)
- [Domain-Driven Design](https://martinfowler.com/tags/domain%20driven%20design.html)
- [Clean Code Principles](https://github.com/jupeter/clean-code-php)
- [SOLID Principles](https://github.com/wataridori/solid-php-example)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

## 💬 Dúvidas?

- Abra uma [Discussion](https://github.com/he4rt/he4rt-bot-api/discussions)
- Entre no [Discord He4rt](https://discord.gg/he4rt)
- Pergunte aos maintainers

---

**Obrigado por contribuir! 💜**
