---
title: Seu Primeiro Pull Request
order: 4
date: 2026-06-14
---

# Seu Primeiro Pull Request

Este guia descreve o fluxo de contribuição do começo ao fim: pegar uma issue,
criar a branch, commitar e abrir o PR. Se você ainda não rodou o projeto, comece
por [Rodando o Projeto](/docs/{{version}}/rodando-o-projeto).

- [Antes de começar](#antes-de-comecar)
- [Pegando uma issue](#pegando-uma-issue)
- [Criando a branch](#criando-a-branch)
- [Padrão de commits](#padrao-de-commits)
- [Abrindo o PR](#abrindo-o-pr)
- [Checklist final](#checklist-final)

<a name="antes-de-comecar"></a>

## Antes de começar

Leia [Convenções de Código](/docs/{{version}}/convencoes-de-codigo) e garanta
que o ambiente está de pé. Toda mudança precisa de teste, então tenha a suíte
rodando localmente:

```bash
php artisan test --compact
```

<a name="pegando-uma-issue"></a>

## Pegando uma issue

As issues vivem como GitHub issues no repositório `he4rt/he4rt-bot-api`. Use a
CLI `gh` para tudo:

```bash
# Listar issues abertas
gh issue list --state open

# Ler uma issue com comentários
gh issue view <numero> --comments
```

Procure as labels que indicam trabalho aberto para contribuidores:

- `good first issue` — bom ponto de partida.
- `ready-for-agent` / `ready-for-human` — totalmente especificada, pronta para
  implementar.
- `difficulty:trivial` / `difficulty:easy` — escopo pequeno e bem definido.

Comente na issue avisando que vai pegá-la antes de começar, para evitar trabalho
duplicado.

<a name="criando-a-branch"></a>

## Criando a branch

Crie uma branch a partir da branch padrão, com um nome que descreva a mudança.
Use o prefixo do conventional commit como tipo:

```bash
git switch -c feat/docs-portal
git switch -c fix/discord-timeout
git switch -c refactor/xp-system
```

Faça um commit por issue: termine e commite o trabalho de uma issue antes de
passar para a próxima.

<a name="padrao-de-commits"></a>

## Padrão de commits

As mensagens de commit e os títulos de PR seguem
[Conventional Commits](https://www.conventionalcommits.org/) com o módulo como
escopo:

```
<tipo>(<modulo>): descrição curta em português

feat(profile): página pública de perfil com domínio próprio
fix(bot-discord): timeout de slash command em guilds grandes
refactor(gamification): redesenho do sistema de XP
docs(docs): guia de primeiro pull request
```

Tipos disponíveis: `feat`, `fix`, `refactor`, `docs`, `chore`. O escopo é o nome
do diretório em `app-modules/` (`profile`, `bot-discord`, `economy`…).

<a name="abrindo-o-pr"></a>

## Abrindo o PR

Antes de abrir, rode a verificação completa que a CI também roda:

```bash
vendor/bin/pint --dirty     # formata o que você mudou
composer test               # Rector + Pint + PHPStan + testes
```

Com tudo verde, faça push e abra o PR:

```bash
git push -u origin sua-branch
gh pr create --fill
```

No corpo do PR, referencie a issue (`Closes #123`), descreva o que mudou e o
porquê, e inclua prints quando houver mudança visual.

<a name="checklist-final"></a>

## Checklist final

Antes de pedir revisão, confirme:

- [ ] `declare(strict_types=1);` em todo arquivo PHP novo.
- [ ] PHPDoc `@property` do Model atualizado se você mexeu em schema.
- [ ] Testes novos ou atualizados, e a suíte passando
      (`php artisan test --compact`).
- [ ] `vendor/bin/pint --dirty` rodado, sem diffs de formatação.
- [ ] `vendor/bin/phpstan analyse` passando.
- [ ] Título do PR no padrão `<tipo>(<modulo>): ...`.
- [ ] Issue referenciada no corpo do PR.

Em mudanças de domínio, vale conferir o `CONTEXT.md` do módulo e os ADRs em
`docs/adr/` (ou `app-modules/<modulo>/docs/adr/`) para usar o vocabulário certo e
não contradizer decisões já tomadas.
