# ADR-0001: Onboarding polimórfico por tipo, com etapas auditáveis

**Status:** Accepted
**Date:** 2026-06-15
**Deciders:** danielhe4rt

> **Atualização (2026-07-13):** o multi-tenancy foi removido do projeto (#413). As menções a
> `tenant_id` e a escopo por tenant abaixo refletem a decisão original e **não valem mais** para o
> schema atual: a chave da tabela `onboardings` deixou de ser `(tenant_id, user_id, type)` e passou
> a ser `UNIQUE (user_id, type)`. Mantido como registro histórico — o estado atual está no
> `CONTEXT.md` e nas migrations.

## Contexto

Os Squads da He4rt rodam hoje no informal (grupos de WhatsApp, sem liderança formal). A primeira
entrega de software ataca **governança** (capitão/subcapitão, eleição, etc.) e, antes dela, uma
camada de **Entrada** que filtra quem realmente quer integrar a comunidade e contribuir.

Essa Entrada — chamada no documento da P.O. de "pré-triagem" — é **universal e obrigatória**:
ninguém se candidata a um squad nem propõe um squad novo sem concluí-la. Duas perguntas de fronteira
apareceram:

1. **Esse onboarding é específico de um squad ou global da comunidade?** A pré-triagem é sobre pertencer à comunidade, não a um squad específico, e reusa
   pesado o `identity` (vínculo GitHub via `ExternalIdentity`). Colocá-la dentro de `squads`
   amarraria um conceito universal a um consumidor específico.
2. **Quantos formatos?** O time já enxerga **mais de um tipo de entrada** — `Welcome` (entrada na
   comunidade) e `Squads` (entrada no programa) — e quer outros no futuro, cada um com seu próprio
   contrato de payload e processamento.

Modelar a pré-triagem como uma máquina de estados fixa (form → desafio) resolveria o `Squads` de
hoje, mas não comportaria novos tipos sem refator.

## Decisão

**Criar um módulo de domínio novo, `onboarding`, dono de máquinas de onboarding polimórficas por
tipo.** `squads` (e futuros consumidores) apenas leem o gate de conclusão.

### Polimorfismo (enum → handler)

- `OnboardingType` (enum) discrimina o tipo e resolve o comportamento via `handler(): OnboardingFlow`
  — mesmo idioma que `IdentityProvider::getClient()` já usa no `identity`.
- `OnboardingFlow` (contrato) declara `steps()`, `prerequisites()`, `advance()`, `isComplete()`.
  Toda regra específica do tipo vive no handler; nenhum consumidor conhece os tipos concretos.

### Persistência (modelo + etapas)

Modelo único discriminado por `type` + tabela de etapas (opção "C" avaliada):

`onboardings` — uma linha por `(tenant_id, user_id, type)`:

| Coluna         | Tipo                         | Notas                                                                 |
| -------------- | ---------------------------- | --------------------------------------------------------------------- |
| `id`           | uuid (PK)                    | `HasUuids`                                                            |
| `tenant_id`    | uuid (FK)                    | tenant-scoped (convenção do repo, multi-tenant-ready)                 |
| `user_id`      | uuid (FK)                    |                                                                       |
| `type`         | string                       | `OnboardingType` (`welcome` \| `squads` \| …)                         |
| `status`       | string                       | ciclo de vida genérico: `in_progress`/`paused`/`completed`/`rejected` |
| `completed_at` | timestamptz?                 |                                                                       |
| `paused_at`    | timestamptz?                 |                                                                       |
| timestamps     | tz                           |                                                                       |
| UNIQUE         | `(tenant_id, user_id, type)` |                                                                       |

`onboarding_steps` — uma linha por etapa do fluxo:

| Coluna          | Tipo                        | Notas                                             |
| --------------- | --------------------------- | ------------------------------------------------- |
| `id`            | uuid (PK)                   |                                                   |
| `onboarding_id` | uuid (FK)                   |                                                   |
| `step_key`      | string                      | semântica do handler (`form`, `git_challenge`, …) |
| `status`        | string                      | `pending`/`done`/…                                |
| `data`          | jsonb                       | payload da etapa, validado pelo DTO do tipo       |
| `completed_at`  | timestamptz?                |                                                   |
| timestamps      | tz                          |                                                   |
| UNIQUE          | `(onboarding_id, step_key)` |                                                   |

A tabela de etapas (em vez de só um `payload` JSON no modelo) foi escolhida por dar **auditoria e
histórico por etapa de graça** — relevante pro desafio Git, que tem reenvio com evolução e
pausa/retoma.

### Cadeia entre tipos

`prerequisites()` declara dependências **inter-tipo**: `Squads` exige `Welcome` concluído para poder
iniciar. O gate que o `squads` consome é `Onboarding::isCompleted(user, tenant, Squads)` — apelidado
de **APTO** no domínio.

## Alternativas consideradas

- **Pré-triagem dentro de `identity`** (membership): conceitualmente limpo, mas mistura onboarding
  evolutivo com o núcleo de autenticação e força `identity` a depender de `integration-github`.
- **Pré-triagem dentro de `squads`**: entrega rápida, mas amarra um conceito universal a um consumidor
  e exigiria migração quando outro módulo (eventos, etc.) quiser o mesmo gate.
- **STI / modelo por tipo**: Laravel não tem STI nativo (exige pacote/boilerplate), foge do idioma
  `enum→resolve` do repo e incha o schema com colunas nuláveis por tipo. Descartado.
- **Modelo único só com `payload` JSON (sem tabela de etapas)**: mais simples, mas perde auditoria
  por etapa. É o passo anterior natural; promovido para a tabela de etapas por causa do desafio Git.

## Consequências

### Positivas

- Somar um tipo novo = +1 case no enum + 1 classe `Flow`. Consumidores intactos.
- Auditoria etapa-a-etapa nativa (início/fim de cada etapa, tentativas de reenvio).
- Pausa/retoma natural (estado vive na etapa + `status=paused`).
- Núcleo do jogo (futuro) pode virar mais um tipo, ou consumir o gate, sem acoplar.

### Negativas / diferidas

- `status`/`step_key` são strings genéricas — a disciplina de transição fica no handler, não no banco.
- Dois lugares de verdade (`onboardings` + `onboarding_steps`); o `isComplete()` precisa ser a única
  fonte que decide conclusão para não divergirem.
- Validação do payload é só de aplicação (DTO), não de schema.

## Review trigger

Revisitar quando (a) o Núcleo do jogo for refinado e a gente decidir se ele é um `OnboardingType` ou
um consumidor do gate; ou (b) surgir um tipo cujo estado de etapa não caiba no par modelo+steps.
