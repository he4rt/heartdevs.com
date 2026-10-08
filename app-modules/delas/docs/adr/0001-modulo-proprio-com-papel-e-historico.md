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
    adr: identity/0003-acesso-ao-admin-por-papel-de-moderacao
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
  histórico completo. _Vai além da #570_ ("atribuível apenas por admins do Hub"): dá autonomia à
  He4rt Delas sem depender de super admin no dia a dia.
- **Gates nomeados no `DelasServiceProvider`**: `moderate-delas` (moderadora ou líder) e
  `lead-delas` (líder). Super admins passam nos dois pelo `Gate::before` do identity.
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
- **Editar motivo sem apagar.** Cada edição é uma linha nova (`reason_corrected`, `corrects_id`
  apontando para a original), nunca uma alteração da original. O motivo vigente na solicitação ou
  no bloqueio passa a ser o editado. Quem escreveu edita até 24 horas depois; a líder, sempre. Só
  a liderança vê as versões anteriores na tela: quem edita pode ter tirado algo sensível.
    - _Alternativa descartada:_ editar o campo `reason` direto. Mais simples, mas apagaria o que
      foi escrito e quebraria o append-only.
- **Moderação no `/admin`, pedido e tag no `/app`.** Moderação na He4rt mora no `/admin`, no
  cluster de Moderação (`/admin/mod`). A He4rt Delas entra nele como o grupo "He4rt Delas" da
  subnavegação, com uma página por parte, cada uma com o próprio gate:
    - Fila (`moderate-delas`, badge de pendentes): aprovar, rejeitar e bloquear;
    - Membras (`moderate-delas`, badge de quem tem a tag): consulta; a líder remove a tag e pode
      bloquear novos pedidos no mesmo passo;
    - Bloqueios (`moderate-delas`, badge de bloqueios ativos): desbloquear;
    - Histórico (`moderate-delas`): só decisões para a moderadora, completo e com filtro para a
      líder;
    - Equipe (`lead-delas`): números, conceder e remover a tag, adicionar e revogar moderadora.

    No `/app` ficam só o pedido no perfil (`DelasProfileSection`) e a tag no card do perfil. A
    atribuição de `delas-lead` continua pela seção Papéis do `UserForm`, só para super admin.
    - _Antes:_ tudo ficava numa página He4rt Delas no `/app`, porque a ADR-0002 do `identity`
      dizia que o `/admin` era "super admin ou nada". A
      [ADR-0003 do `identity`](../../../identity/docs/adr/0003-acesso-ao-admin-por-papel-de-moderacao.md)
      substitui essa regra: papéis de moderação entram no `/admin` e cada Resource, Page e Cluster
      decide no `canAccess()`. Assim, a alternativa "abrir o `/admin` para moderadoras" deixa de
      ser descartada e vira a decisão.
    - _Alternativa descartada:_ manter a moderação no `/app`. Criaria um segundo lugar de
      moderação, fora do cluster que já existe.

- **Seletores de pessoa como consulta de domínio** (`DelasCandidates`): conceder lista quem não tem
  a tag, remover lista só quem tem, adicionar moderadora lista só quem tem a tag, sem super
  admins, moderadoras e líderes, e a própria pessoa que age nunca aparece. As telas só chamam a consulta.
- **Discord conectado para pedir a tag** (`delas.require_discord`, ligado por padrão). A He4rt
  Delas acontece no Discord: a moderação conhece a pessoa por lá, e a conta conectada é o que vai
  ligar a tag ao cargo `he4rt_delas`. Fica depois da espera e do bloqueio na ordem das
  verificações, para ninguém ser mandada conectar o Discord sem poder pedir.
    - _Alternativa descartada:_ exigir o perfil completo. Não prova nada sobre a pessoa, pede
      dados pessoais a mais (minimização da LGPD) e afasta quem está começando. Ficou só um
      lembrete no pop-up.
- **Moderadora precisa ter a tag.** Quem modera a He4rt Delas faz parte dela; para promover
  alguém de fora, a líder concede a tag antes.
- **Bloquear não tira a tag.** Tirar a tag é da líder, com motivo próprio ("Remover tag", com a
  opção de bloquear junto). Se o bloqueio tirasse a tag, a moderadora teria um caminho indireto
  para remover membras.
- **"Ver perfil" só leitura, com o mínimo.** A moderação vê o que ajuda a decidir (foto, título,
  senioridade, "Sobre", Discord), nunca e-mail, data de nascimento ou localização. Não abre a área
  de Perfis do admin para moderadoras: daria acesso ao perfil de todo mundo.
- **Marca no design system.** A logo, a tag e o ícone `he4rt-delas` moram no módulo `he4rt`
  (`x-he4rt::delas.logo`, `x-he4rt::delas.tag`), porque os dois painéis usam. A paleta
  `--color-delas-*` está no tema dos dois painéis.

## Consequências

- O `identity` ganha um case em `UserRole`, uma migration de role e um state de factory.
- O campo de roles do `UserForm` passa a ser visível só para super admin. Fora de produção o
  `/admin` é aberto, e sem isso qualquer pessoa autenticada se daria `delas-moderator`.
- `delas-moderator` e `delas-lead` entram no `/admin` (`UserRole::grantsAdminAccess()`), mas só
  acessam o Dashboard (sem widgets) e as páginas da He4rt Delas. Todo o resto do admin tem o
  trait `SuperAdminOnly`, e um teste de varredura cobra isso.
- Papéis alterados pelas actions do delas entram no histórico. Os alterados direto no `UserForm`
  não: essa auditoria é concern do `identity` e vale para todas as roles.
- Sincronizar a tag com o cargo `he4rt_delas` do Discord fica para depois. Os eventos de domínio
  deixam o gancho pronto.
- A tag revela identidade de gênero, dado sensível pela LGPD. Hoje só a própria pessoa e quem
  modera a veem; exibir a tag publicamente exige nova decisão.
