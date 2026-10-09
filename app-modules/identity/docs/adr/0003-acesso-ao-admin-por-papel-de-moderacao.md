---
type: adr
title: 'Acesso ao /admin por papel de moderação'
module: identity
status: accepted
date: 2026-10-07
author: damacosta
related:
    adr: identity/0002-super-admin-via-spatie-permission
---

# ADR-0003: Acesso ao /admin por papel de moderação

Substitui a regra "o painel inteiro é super admin ou nada" da
[ADR-0002](0002-super-admin-via-spatie-permission.md). O resto da ADR-0002 continua valendo.

## Contexto

A ADR-0002 foi escrita na fase 1, quando o único papel era `super-admin`. Por isso ela diz que o
`/admin` é "super admin ou nada".

Com a He4rt Delas surgiram papéis que moderam alguma coisa (`delas-moderator` e `delas-lead`).
Moderação na He4rt mora no `/admin`, no cluster de Moderação (`/admin/mod`). Manter a moderação
da He4rt Delas no `/app` só para não abrir o `/admin` criaria dois lugares de moderação.

Abrir a porta do `/admin` tem duas armadilhas:

- no Filament, um Resource sem policy e uma Page sem `canAccess()` liberam acesso para qualquer
  pessoa que entrou no painel. Nenhum Resource, Page ou Cluster do `panel-admin` tinha
  `canAccess()`;
- o `PanelAdminServiceProvider::buildNavigation()` monta a sidebar com `getNavigationItems()`,
  que não checa `canAccess()`. O item aparecia na sidebar e dava 403 no clique.

## Decisão

- **Cada papel diz se entra no `/admin`**, em `UserRole::grantsAdminAccess()`, com `match` sem
  `default`: um papel novo sem essa decisão quebra no PHPStan antes de quebrar em produção.
  `UserRole::withAdminAccess()` lista os papéis que entram.
    - `super-admin`, `delas-moderator` e `delas-lead`: entram.
    - `streamer`: não entra.
- **`User::canAccessPanel('admin')`** em produção passa a ser
  `$this->hasAnyRole(UserRole::withAdminAccess())`. Fora de produção a porta continua aberta,
  como na ADR-0002.
- **Entrar no painel não dá acesso a nada por si só.** Cada Resource, Page e Cluster decide no
  `canAccess()`, e isso vale em **todo ambiente**:
    - o trait `He4rt\PanelAdmin\Concerns\SuperAdminOnly` vai em todo Resource, Page e Cluster
      do `panel-admin` que não é de um papel de moderação;
    - as páginas de moderação de um papel checam o gate do módulo dono (ex.: `moderate-delas` e
      `lead-delas`, definidos no `delas`);
    - o `ModerationCluster` fica sem o trait, porque é dividido entre papéis. A subnavegação do
      cluster já filtra por `canAccess()`;
    - o Dashboard continua acessível como página de entrada, mas os widgets dele são só para
      super admin (`canView()`).
- **A sidebar respeita o `canAccess()`.** O `buildNavigation()` monta os itens com um helper
  `itemsFor()`, que só devolve os itens de quem passa no `canAccess()` (clusters, no
  `canAccessClusteredComponents()`).
- **Um teste de varredura** garante que uma moderadora da He4rt Delas só acessa o Dashboard e as
  páginas da He4rt Delas. Resource ou Page novo sem o trait quebra esse teste.

## Consequências

- Papel novo em `UserRole` precisa decidir se entra no `/admin`, ou o PHPStan acusa o `match`.
- Todo Resource, Page ou Cluster novo no `panel-admin` precisa do `SuperAdminOnly` ou de um
  `canAccess()` próprio. O teste de varredura cobra isso.
- Fora de produção, quem não é super admin entra no `/admin` mas só vê o Dashboard (sem
  widgets). Para ver o resto localmente: `php artisan identity:grant-super-admin {username}`.
- A seção Papéis do `UserForm` continua visível só para super admin, por causa da porta aberta
  fora de produção.
- `Gate::before` continua liberando tudo para super admin, então as páginas de um papel de
  moderação também abrem para super admin.
