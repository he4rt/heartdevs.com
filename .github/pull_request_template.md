---
name: Pull Request
about: Template para Pull Requests
title: "[TIPO] Breve descrição"
labels: ''
assignees: ''
---

## 📝 Descrição

<!-- Descreva claramente o que este PR faz. Seja específico e detalhado. -->

## 🎯 Tipo de Mudança

<!-- Marque o tipo de mudança com um "x" -->

- [ ] 🐛 **Bug fix** (mudança que corrige um problema)
- [ ] ✨ **Nova funcionalidade** (mudança que adiciona funcionalidade)
- [ ] 💥 **Breaking change** (fix ou feature que quebra compatibilidade)
- [ ] 📝 **Documentação** (atualização de documentação)
- [ ] 🎨 **Refatoração** (mudança de código sem alterar funcionalidade)
- [ ] ⚡ **Performance** (melhoria de performance)
- [ ] ✅ **Testes** (adição ou correção de testes)
- [ ] 🔧 **Chore** (mudanças em build, CI, dependências)

## 🔗 Issues Relacionadas

<!-- Link para issues relacionadas -->

Closes #
Fixes #
Relacionado a #

## 🧪 Como Testar?

<!-- Descreva os passos para testar suas mudanças -->

1. 
2. 
3. 

**Comando para testar:**
```bash
make test filter=NomeDaClasse
```

## 📸 Screenshots (se aplicável)

<!-- Adicione screenshots, GIFs ou vídeos demonstrando as mudanças -->

## ✅ Checklist

<!-- Marque os itens completados com "x" -->

### Code Quality
- [ ] Código segue os padrões PSR-12
- [ ] Código segue princípios DDD e Clean Architecture
- [ ] Nomes de variáveis e métodos são descritivos
- [ ] Código está comentado onde necessário
- [ ] Sem código comentado desnecessário
- [ ] Sem `dd()`, `dump()` ou `var_dump()` deixados no código

### Testing
- [ ] Testes unitários adicionados/atualizados
- [ ] Testes de feature adicionados/atualizados
- [ ] Todos os testes passam localmente
- [ ] Cobertura de testes mantida ou melhorada

### Documentation
- [ ] Documentação atualizada (se necessário)
- [ ] README atualizado (se necessário)
- [ ] PHPDoc adicionado aos métodos públicos
- [ ] Exemplos de uso adicionados (se aplicável)

### Git & CI
- [ ] Commits seguem Conventional Commits
- [ ] Branch atualizada com `main`
- [ ] Sem conflitos de merge
- [ ] CI/CD pipelines passando

### Security
- [ ] Sem dados sensíveis no código
- [ ] Validações de input implementadas
- [ ] Autorizações verificadas (se aplicável)
- [ ] SQL injection prevenido
- [ ] XSS prevenido (se aplicável)

## 🎨 Módulos Afetados

<!-- Marque os módulos afetados -->

- [ ] Authentication
- [ ] User
- [ ] Badges
- [ ] Character
- [ ] Ranking
- [ ] Season
- [ ] Meeting
- [ ] Message
- [ ] Feedback
- [ ] Provider
- [ ] Integrations
- [ ] Core

## 📊 Impacto

<!-- Descreva o impacto das mudanças -->

### Impacto no Banco de Dados
- [ ] Nenhum
- [ ] Nova migration adicionada
- [ ] Migration modifica tabela existente
- [ ] Seeds atualizados

### Breaking Changes
- [ ] Nenhum
- [ ] API endpoints modificados
- [ ] Estrutura de dados modificada
- [ ] Comportamento de funcionalidade existente alterado

<!-- Se houver breaking changes, descreva como migrar: -->
**Guia de Migração:**
```
```

## 🔍 Review Checklist (para Reviewers)

<!-- Para uso dos reviewers -->

- [ ] Código revisado e aprovado
- [ ] Testes executados e passando
- [ ] Documentação verificada
- [ ] Commits verificados (Conventional Commits)
- [ ] Sem code smells ou anti-patterns
- [ ] Performance considerada
- [ ] Segurança verificada

## 💬 Notas Adicionais

<!-- Qualquer informação adicional relevante -->

## 📚 Referências

<!-- Links úteis, artigos, documentação externa -->

- 

---

## 🤝 Informações para o Autor

### Conventional Commits

Certifique-se de que seus commits seguem o padrão:

```
<tipo>(<escopo>): <descrição>

[corpo opcional]

[rodapé opcional]
```

**Tipos:**
- `feat`: Nova funcionalidade
- `fix`: Correção de bug
- `docs`: Documentação
- `style`: Formatação (não altera funcionalidade)
- `refactor`: Refatoração
- `perf`: Melhoria de performance
- `test`: Testes
- `build`: Build ou dependências
- `ci`: CI/CD
- `chore`: Outras mudanças

**Exemplos:**
```bash
feat(user): add daily reward system
fix(ranking): correct season calculation
docs(api): update endpoints documentation
refactor(badges): extract claim logic to service
```

### Executar Testes Localmente

```bash
# Todos os testes
make test

# Testes específicos
make test filter=NomeDaClasse

# Com cobertura
docker exec -it discord-bot-api vendor/bin/phpunit --coverage-html coverage/
```

### Code Style

```bash
# Verificar
docker exec -it discord-bot-api vendor/bin/phpcs

# Corrigir automaticamente
docker exec -it discord-bot-api vendor/bin/phpcbf

# Laravel Pint
docker exec -it discord-bot-api vendor/bin/pint
```

---

<p align="center">
  Obrigado por contribuir! 💜
</p>
