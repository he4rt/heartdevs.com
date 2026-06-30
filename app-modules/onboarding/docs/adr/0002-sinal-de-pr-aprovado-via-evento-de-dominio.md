# ADR-0002: Sinal de "PR aprovado" via evento de domínio do integration-github

**Status:** Accepted
**Date:** 2026-06-15
**Deciders:** danielhe4rt
**Relates to:** [ADR-0001](0001-onboarding-polimorfico-por-tipo.md); `integration-github`

## Contexto

O step `git_challenge` do `SquadsOnboarding` conclui quando um **revisor humano aprova, no GitHub, o
PR do candidato** num repo de desafio. A plataforma precisa reagir a essa aprovação.

O `integration-github` já é o **único** ponto que fala com o GitHub: `GithubWebhookController` recebe
todos os webhooks, grava no `GithubEventLog` (lake, dedup por `delivery_id`), e o `ProjectGithubEvent`
projeta eventos de repos da allowlist (`GithubRepository`) em `github_contributions`, emitindo
`GithubContributionRecorded`. A regra de fronteira do módulo é: ele **só emite eventos de domínio e
nunca importa de outros módulos**.

Duas decisões de acoplamento precisavam ser tomadas:

1. Como o sinal de aprovação chega ao `onboarding` sem duplicar a infra de webhook/HMAC/dedup nem
   acoplar os módulos.
2. Como distinguir um "repo de desafio" de um repo de contribuições — sem que o `git_challenge` vire
   XP de gamification.

## Decisão

### Sinal por evento de domínio

O `integration-github` passa a emitir um **segundo evento de domínio**,
`GithubPullRequestApproved` (`author_login`, `repo`, `pr_number`, `approved_at`), ao observar
`pull_request_review` com `state = approved`. O `onboarding` registra um **listener** que resolve o
`author_login` para um `User` (via `ExternalIdentity` github) e avança o step `git_challenge`.

O transporte continua centralizado num lugar só; HMAC e dedup são reusados; nenhum módulo passa a
falar com o GitHub além do `integration-github`. É a mesma costura do `GithubContributionRecorded`.

### Repo de desafio via `purpose` na allowlist

`GithubRepository` ganha um campo **`purpose`** (`contributions` | `challenge`):

- Repos `challenge` **não** geram `GithubContributionRecorded` (fazer o desafio não vira XP).
- O `onboarding` resolve o repo de desafio lendo `GithubRepository::query()->where('purpose', 'challenge')`
  (tenant-scoped), e ignora aprovações de repos que não sejam de desafio.

O `purpose` é uma **categoria de projeção** legítima do próprio `integration-github` (ele já decide o
que projetar). O `integration-github` nunca precisa conhecer a palavra "onboarding".

### Vínculo do GitHub é gate, não há reconciliação

O vínculo da conta GitHub (`ExternalIdentity` provider `github`) é **pré-requisito** (gate) para o
step `git_challenge`. Logo o `author_login` do webhook **sempre** casa um `User`, e o cenário
"aprovação de conta não vinculada → retém e reconcilia" **deixa de existir** — sem buffer, sem tabela
de pendências, sem reconciliação. Isso diverge do BDD original da P.O., que previa reconciliação.

## Alternativas consideradas

- **Webhook próprio do `onboarding`** só pro repo de desafio: desacoplaria 100%, mas duplicaria
  HMAC/dedup e criaria um segundo ponto que fala com o GitHub. Descartado.
- **`onboarding` relendo o lake (`GithubEventLog`)**: inverteria a dependência (`onboarding` →
  consulta interna do `integration-github`) e furaria o encapsulamento do lake. Descartado.
- **Config de repo de desafio dentro do `onboarding`** (em vez do `purpose` na allowlist): manteria o
  `integration-github` sem nenhuma categoria nova, mas duplicaria o cadastro de repos e perderia o
  benefício de excluir o repo de desafio da projeção de contribuições num lugar só. Trade-off aceito
  a favor do `purpose`.

## Consequências

### Positivas

- Um único ponto de integração com o GitHub; HMAC/dedup reusados.
- Repos de desafio ficam fora da gamification por construção (`purpose`).
- Sem reconciliação: a máquina de estados fica drasticamente mais simples.

### Negativas / diferidas

- `integration-github` ganha um rótulo (`purpose=challenge`) que existe por causa de um consumidor —
  acoplamento mínimo e consciente, mitigado por ser categoria de projeção, não lógica de onboarding.
- O repo de desafio precisa ter o webhook do GitHub instalado apontando pro endpoint do
  `integration-github` (setup de infra, não de código).
- Diverge do BDD original (reconciliação removida) — o documento da P.O. precisa ser atualizado.

## Review trigger

Revisitar se algum dia um onboarding precisar reagir a aprovações de conta ainda não vinculada
(reintroduziria o buffer/reconciliação), ou se mais de um repo de desafio por tenant exigir
roteamento por tipo de desafio.
