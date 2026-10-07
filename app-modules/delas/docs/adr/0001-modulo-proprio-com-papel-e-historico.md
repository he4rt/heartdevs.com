---
type: adr
title: 'Módulo próprio, papel do Spatie e histórico de decisões'
module: delas
status: accepted
date: 2026-10-06
deciders:
    - damacosta
related:
    spec: 2026-10-06-he4rt-delas-tag-e-moderacao
---

# ADR-0001: Módulo próprio, papel do Spatie e histórico de decisões

## Contexto

As issues #570 e #571 pedem que uma pessoa solicite a tag He4rt Delas no perfil e que líderes
ou moderadoras da He4rt Delas aprovem ou rejeitem o pedido dentro do Hub, com registro de quem
decidiu e quando. No Discord isso já existe como cargo (`/cargo-delas`), mas no Hub não há nada.

A feature tem estado (pedido, decisão, espera, bloqueio), papéis próprios e histórico. Ela
precisa morar em algum lugar e ter uma forma de autorizar quem decide.

## Alternativas consideradas

- **Tag como role do Spatie (`he4rt-delas`).** Espelha o cargo do Discord, mas uma role não guarda
  pedido, decisão, espera nem bloqueio, e mistura autorização com identidade.
- **Badge do `gamification`.** Badges são progressão por XP ou claim, sem fluxo de aprovação.
- **Tipo dentro do `onboarding`.** O onboarding é a camada de entrada obrigatória. A tag é
  opcional e pode ser pedida a qualquer momento.
- **Subdomínio do `profile`.** Acoplaria o perfil a papéis e moderação.
- **Permissions granulares do Spatie.** A ADR-0002 do `identity` adota só roles nesta fase.

## Decisão

- **Módulo de domínio `delas`**, dependente só do `identity`. Os painéis dependem dele.
- **Papéis `delas-moderator` e `delas-lead`** como cases de `UserRole` no `identity`, criados por
  migration, como a role `streamer`. A líder gerencia moderadoras, concede e remove a tag e vê o
  histórico completo, tudo no Hub. _Vai além da #570_ ("atribuível apenas por admins do Hub"):
  dá autonomia à He4rt Delas sem abrir o `/admin`.
- **Gates nomeados no `DelasServiceProvider`**: `moderate-delas` (moderadora ou líder),
  `lead-delas` (líder) e `administer-delas` (sempre `false`; só `super-admin` passa, pelo
  `Gate::before` do identity).
  O código checa o gate, nunca o nome da role.
    - _Diverge do `use-streamer-tools`, que fica no `IdentityServiceProvider`._ Aqui o gate é
      vocabulário do delas, e o identity não deve conhecer regras de outro módulo.
- **Solicitação com máquina de estados em enum** (`pending → approved | rejected`,
  `approved → revoked`), como a ADR-0001 do `events`.
- **Espera de 15 dias** depois de `rejected` ou `revoked`, configurável.
- **Bloqueio** como registro próprio, com motivo obrigatório, feito por moderadoras e desfeito por
  quem bloqueou ou por super admin.
- **Histórico append-only** em `delas_transitions`, gravado pelas actions, sem trigger (ADR-0003
  do `events`).
- **Dois espaços de interface.** Moderadoras decidem numa página do Hub (`/app`), como a #570
  pede, porque não têm acesso ao `/admin`. Super admins têm um **cluster He4rt Delas** no
  `/admin` (dashboard, solicitações, bloqueios, histórico e moderadoras), no formato do cluster de
  Moderação. A tabela de Usuários só exibe as roles.
    - _Alternativa descartada:_ abrir o `/admin` para moderadoras, limitado ao cluster. Contradiz a
      ADR-0002 do `identity` ("o painel inteiro é super admin ou nada").

## Consequências

- O `identity` ganha um case em `UserRole`, uma migration de role e um state de factory.
- O campo de roles do `UserForm` passa a ser visível só para super admin. Fora de produção o
  `/admin` é aberto, e sem isso qualquer pessoa autenticada se daria `delas-moderator`.
- Papéis alterados pelas actions do delas entram no histórico. Os alterados direto no `UserForm`
  não: essa auditoria é concern do `identity` e vale para todas as roles.
- Sincronizar a tag com o cargo `he4rt_delas` do Discord fica para depois. Os eventos de domínio
  deixam o gancho pronto.
- A tag revela identidade de gênero, dado sensível pela LGPD. Hoje só a própria pessoa e quem
  modera a veem; exibir a tag publicamente exige nova decisão.
