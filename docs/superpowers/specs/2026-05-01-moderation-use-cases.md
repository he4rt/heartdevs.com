# Moderation System — Use Cases & Flows

## Actors

| Actor                | Descricao                                   | Permissoes                                                                   |
| -------------------- | ------------------------------------------- | ---------------------------------------------------------------------------- |
| **User**             | Membro da comunidade em qualquer plataforma | Reportar conteudo, contestar acoes (appeal), visualizar proprio historico    |
| **Moderator**        | Membro com papel de moderacao               | Tudo do User + revisar fila, tomar acoes, atribuir casos, escalar            |
| **Senior Moderator** | Moderador com nivel elevado                 | Tudo do Moderator + revisar appeals, configurar regras, ver metricas         |
| **Admin**            | Administrador da comunidade                 | Tudo + gerenciar moderadores, configurar sistema, audit log completo         |
| **System**           | Pipeline automatizado (AI + rules)          | Classificar, pontuar, sugerir, logar. NUNCA executa acoes autonomamente (v1) |

---

## Casos de Uso

### UC-01: Reportar Conteudo

**Actor:** User
**Plataformas:** Todas (Discord, Twitch, GitHub, Twitter, Web)
**Trigger:** Usuario identifica conteudo que viola regras da comunidade

**Pre-condicoes:**

- Usuario esta autenticado na plataforma
- Conteudo existe e esta visivel
- Usuario nao esta banido

**Fluxo Principal:**

```
 USER                              SYSTEM
  │                                   │
  │  👆 aciona "Report"               │
  │     (botao web / reaction Discord │
  │      / comando /report / menu)    │
  │ ──────────────────────────────►  │
  │                                   │  Validate: user can report? ✓
  │                                   │  Validate: content exists? ✓
  │                                   │  Validate: not self-report? ✓
  │                                   │
  │    "Qual o motivo do report?"     │
  │ ◄────────────────────────────────│
  │                                   │
  │    ┌──────────────────────────┐   │
  │    │ 🚫 Spam                  │   │
  │    │ 🔥 Discurso de odio      │   │
  │    │ 👊 Assedio               │   │
  │    │ 🔞 Conteudo inapropriado │   │
  │    │ 🎭 Impersonacao          │   │
  │    │ ❓ Outro                  │   │
  │    └──────────────────────────┘   │
  │                                   │
  │  👆 seleciona motivo              │
  │  ⌨️  (opcional) detalhes adicionais│
  │ ──────────────────────────────►  │
  │                                   │  PlatformAdapter::ingest()
  │                                   │  │
  │                                   │  ├─ Dedup check: same content reported?
  │                                   │  │   YES → add report to existing case
  │                                   │  │         priority += 10
  │                                   │  │   NO  → create new ModerationCase
  │                                   │  │
  │                                   │  ├─ dispatchSync(IngestContent)
  │                                   │  ├─ dispatchSync(ClassifyContent)
  │                                   │  └─ dispatchSync(RouteDecision)
  │                                   │
  │    "Report recebido. Nossa        │
  │     equipe vai analisar."         │
  │ ◄────────────────────────────────│
  │                                   │
```

**Fluxos Alternativos:**

| Cenario                         | Comportamento                                       |
| ------------------------------- | --------------------------------------------------- |
| User ja reportou mesmo conteudo | Rejeita com "Voce ja reportou este conteudo"        |
| Conteudo ja foi removido        | Rejeita com "Este conteudo ja foi moderado"         |
| User esta banido                | Rejeita silenciosamente (sem feedback)              |
| Rate limit (>10 reports/hora)   | Rejeita com "Muitos reports recentes, tente depois" |

**Pos-condicoes:**

- ModerationReport criado com reporter_id, reason, details
- ModerationCase criado ou atualizado (se dedup)
- AI scores calculados e armazenados
- Caso enfileirado com prioridade baseada em score + numero de reports
- AuditLog: `report_submitted`

---

### UC-02: Deteccao Automatica de Conteudo

**Actor:** System
**Plataformas:** Todas
**Trigger:** Conteudo novo criado em qualquer plataforma monitorada

**Pre-condicoes:**

- Platform adapter esta registrado e ativo
- Classifiers estao configurados e habilitados
- Conteudo passa pelo pipeline (message event, post creation, etc.)

**Fluxo Principal:**

```
 PLATFORM EVENT                    SYSTEM
  │                                   │
  │  📨 novo conteudo criado          │
  │     (mensagem, post, imagem,      │
  │      atualizacao de perfil)       │
  │ ──────────────────────────────►  │
  │                                   │  PlatformAdapter::ingest()
  │                                   │  → ModerationContentDTO
  │                                   │
  │                                   │  dispatchSync(IngestContent)
  │                                   │  ├─ Snapshot conteudo original
  │                                   │  ├─ Resolve author (ExternalIdentity → User)
  │                                   │  └─ ModerationCase(source: auto_detect)
  │                                   │
  │                                   │  dispatchSync(ClassifyContent)
  │                                   │  ├─ RuleBasedClassifier
  │                                   │  │   └─ keyword/regex match against rules
  │                                   │  ├─ OpenAiClassifier
  │                                   │  │   └─ API call → scores per category
  │                                   │  └─ AggregateClassifier
  │                                   │      └─ merge scores, pick highest
  │                                   │
  │                                   │  dispatchSync(RouteDecision)
  │                                   │  ├─ ALL scores < 0.3 → DISMISS (log only)
  │                                   │  ├─ ANY score >= 0.7 → FLAG (queue for review)
  │                                   │  └─ ANY score >= 0.9 → HIGH PRIORITY flag
  │                                   │
  │                                   │  [If flagged]:
  │                                   │  ├─ PenaltyAdvisor::suggest()
  │                                   │  ├─ Case.status = pending
  │                                   │  ├─ Case.suggested_action = advisor output
  │                                   │  └─ AuditLog: case_auto_flagged
  │                                   │
  │                                   │  [If dismissed]:
  │                                   │  ├─ Case.status = dismissed
  │                                   │  └─ AuditLog: case_auto_dismissed
  │                                   │
```

**Regras de Classificacao:**

| Classifier          | Tempo     | O que detecta                                         |
| ------------------- | --------- | ----------------------------------------------------- |
| RuleBasedClassifier | <1ms      | Keywords, regex, URL patterns, repetition             |
| OpenAiClassifier    | 100-500ms | Toxicidade, hate speech, sexual, violence, self-harm  |
| AggregateClassifier | —         | Combina scores, aplica pesos, retorna resultado final |

**Thresholds (configuraveis em `config/moderation.php`):**

| Score      | Acao                                                    |
| ---------- | ------------------------------------------------------- |
| < 0.3      | Dismiss (log only, nao aparece na fila)                 |
| 0.3 - 0.69 | Monitor (log, aparece em relatorios, nao na fila ativa) |
| 0.7 - 0.89 | Flag (enfileira para review humano, prioridade normal)  |
| >= 0.9     | High Priority Flag (enfileira com prioridade alta)      |

---

### UC-03: Revisar Caso na Fila de Moderacao

**Actor:** Moderator
**Plataforma:** Web (Filament Admin Panel)
**Trigger:** Moderador abre o painel e busca proximo caso

**Pre-condicoes:**

- Moderador autenticado com permissao de moderacao
- Existem casos com status `pending` ou `assigned` na fila
- Moderador nao excedeu limite diario de reviews (se configurado)

**Fluxo Principal:**

```
 MODERADOR                           SYSTEM
  │                                   │
  │  👆 acessa /admin/moderation      │
  │ ──────────────────────────────►  │
  │                                   │  Query: ModerationCase
  │                                   │  WHERE status IN (pending, assigned)
  │                                   │  AND tenant_id = current_tenant
  │                                   │  ORDER BY priority DESC, created_at ASC
  │                                   │
  │    ┌──────────────────────────────────────────────────┐
  │    │ MODERATION QUEUE                                  │
  │    ├───────┬──────────┬──────────┬────────┬──────────┤
  │    │ Prio  │ Type     │ Platform │ Author │ Score    │
  │    ├───────┼──────────┼──────────┼────────┼──────────┤
  │    │ 🔴 95 │ spam     │ Discord  │ @bot1  │ 0.97    │
  │    │ 🟠 80 │ toxicity │ Web      │ user2  │ 0.84    │
  │    │ 🟡 60 │ harassment│ Discord │ user3  │ 0.72    │
  │    │ 🟡 55 │ spam     │ Twitch   │ user4  │ 0.71    │
  │    └───────┴──────────┴──────────┴────────┴──────────┘
  │                                   │
  │  👆 clica no primeiro caso        │
  │ ──────────────────────────────►  │
  │                                   │  Lock: Case.assigned_to = moderator
  │                                   │  Case.status = assigned
  │                                   │  AuditLog: case_assigned
  │                                   │
  │    ┌──────────────────────────────────────────────────┐
  │    │ CASE #abc123                                      │
  │    │──────────────────────────────────────────────────│
  │    │                                                   │
  │    │ 📋 CONTENT SNAPSHOT                               │
  │    │ ┌────────────────────────────────────────────┐   │
  │    │ │ "Buy cheap followers at spam-link.com      │   │
  │    │ │  Best prices! DM me now!!!"                │   │
  │    │ └────────────────────────────────────────────┘   │
  │    │                                                   │
  │    │ 📊 AI SCORES                                      │
  │    │ spam:       ████████████████████░░ 0.97           │
  │    │ toxicity:   ███░░░░░░░░░░░░░░░░░░ 0.15           │
  │    │ harassment: ██░░░░░░░░░░░░░░░░░░░ 0.08           │
  │    │                                                   │
  │    │ 📍 CONTEXT                                        │
  │    │ Platform: Discord | Channel: #geral               │
  │    │ Author: @bot1 (account age: 2h)                   │
  │    │ Matched rules: [spam_url_pattern]                 │
  │    │                                                   │
  │    │ 📜 REPORTS (3)                                    │
  │    │ - @user5: "spam obvio" (2min ago)                 │
  │    │ - @user6: "bot" (5min ago)                        │
  │    │ - @user7: "spam link" (8min ago)                  │
  │    │                                                   │
  │    │ 👤 USER HISTORY                                   │
  │    │ Prior offenses (30d): 0                           │
  │    │ Account created: 2h ago                           │
  │    │ Platforms: Discord only                           │
  │    │                                                   │
  │    │ 💡 SUGGESTED ACTION: Ban (new account + spam)     │
  │    │ Reasoning: "Account com 2h, score 0.97 spam,     │
  │    │  3 reports de membros diferentes"                 │
  │    │                                                   │
  │    │──────────────────────────────────────────────────│
  │    │ TAKE ACTION:                                      │
  │    │                                                   │
  │    │ Action: [Warn ▼] [Mute ▼] [Kick ▼] [Ban ▼]     │
  │    │ Duration: [Permanent ▼] [7d] [30d] [Custom]      │
  │    │ Platforms: ☑ Discord  ☐ Twitch  ☐ Web            │
  │    │ Reason: [_________________________________]       │
  │    │                                                   │
  │    │ [💀 Execute Action]  [👋 Dismiss Case]            │
  │    └──────────────────────────────────────────────────┘
  │                                   │
  │  👆 seleciona Ban + Permanent     │
  │     platforms: Discord             │
  │     reason: "Spam bot account"     │
  │     clica "Execute Action"         │
  │ ──────────────────────────────►  │
  │                                   │  → UC-05 (Executar Acao)
  │                                   │
```

**Filtros disponiveis na fila:**

| Filtro         | Opcoes                                                |
| -------------- | ----------------------------------------------------- |
| Status         | pending, assigned, resolved, escalated, dismissed     |
| Platform       | discord, twitch, github, twitter, web                 |
| Violation Type | spam, toxicity, harassment, nsfw, raid, impersonation |
| Severity       | low, medium, high, critical                           |
| Assigned To    | me, unassigned, specific moderator                    |
| Date Range     | today, last 7d, last 30d, custom                      |

---

### UC-04: Escalar Caso

**Actor:** Moderator
**Plataforma:** Web (Filament Admin Panel)
**Trigger:** Moderador nao tem certeza sobre a decisao ou caso e complexo

**Pre-condicoes:**

- Caso atribuido ao moderador
- Caso nao esta resolvido

**Fluxo Principal:**

```
 MODERADOR                           SYSTEM
  │                                   │
  │  👆 clica "Escalar" no caso       │
  │ ──────────────────────────────►  │
  │                                   │
  │    "Motivo da escalacao?"          │
  │ ◄────────────────────────────────│
  │                                   │
  │    ┌──────────────────────────┐   │
  │    │ Caso ambiguo             │   │
  │    │ Precisa de contexto      │   │
  │    │ Usuario influente        │   │
  │    │ Potencial impacto legal  │   │
  │    │ Decisao de policy        │   │
  │    └──────────────────────────┘   │
  │                                   │
  │  👆 "Caso ambiguo"                │
  │  ⌨️ "Nao sei se e sarcasmo ou..." │
  │ ──────────────────────────────►  │
  │                                   │  Case.status = escalated
  │                                   │  Case.assigned_to = NULL
  │                                   │  Case.priority += 20
  │                                   │  AuditLog: case_escalated
  │                                   │  Notify: senior moderators
  │                                   │
  │    "Caso escalado para            │
  │     moderacao senior."            │
  │ ◄────────────────────────────────│
  │                                   │
```

**Pos-condicoes:**

- Caso aparece com prioridade elevada na fila de Senior Moderators
- Moderador original liberado para pegar proximo caso
- Historico de escalacao registrado no audit log

---

### UC-05: Executar Acao de Moderacao

**Actor:** Moderator / Senior Moderator
**Plataformas:** Origina no Web (Filament), executa em todas selecionadas
**Trigger:** Moderador decide a acao e clica "Execute"

**Pre-condicoes:**

- Caso atribuido ao moderador
- Acao e plataformas selecionadas
- Plataformas selecionadas suportam a acao escolhida

**Fluxo Principal:**

```
 MODERADOR                           SYSTEM
  │                                   │
  │  👆 confirma acao (UC-03)          │
  │ ──────────────────────────────►  │
  │                                   │  Validate:
  │                                   │  ├─ action_type supported by all platforms? ✓
  │                                   │  ├─ moderator has permission? ✓
  │                                   │  └─ case still active? ✓
  │                                   │
  │                                   │  ModerationAction::create(
  │                                   │    case_id, moderator_id, action_type,
  │                                   │    target_platforms, duration, reason
  │                                   │  )
  │                                   │
  │                                   │  dispatchSync(ExecuteAction)
  │                                   │  │
  │                                   │  ├─ Resolve target User
  │                                   │  ├─ For each target_platform:
  │                                   │  │   ├─ Resolve adapter
  │                                   │  │   ├─ Check adapter.supports(action_type)
  │                                   │  │   ├─ adapter.execute(action, user)
  │                                   │  │   └─ Collect ExecutionResultDTO
  │                                   │  │
  │                                   │  ├─ Store execution_results in action
  │                                   │  ├─ Case.status = resolved
  │                                   │  ├─ Case.resolved_at = now()
  │                                   │  │
  │                                   │  ├─ Notify user (via platform adapters)
  │                                   │  │   "Voce recebeu [action] por [reason]"
  │                                   │  │   "Duracao: [duration]"
  │                                   │  │   "Voce pode contestar em ate 7 dias"
  │                                   │  │
  │                                   │  ├─ AuditLog: action_executed
  │                                   │  └─ Event: ActionExecuted
  │                                   │
  │    ┌──────────────────────────┐   │
  │    │ Acao executada:           │   │
  │    │ ✓ Discord: ban applied   │   │
  │    │ ✓ Web: suspended         │   │
  │    │ ✗ Twitch: user not found │   │
  │    │                          │   │
  │    │ User notified via DM.    │   │
  │    └──────────────────────────┘   │
  │ ◄────────────────────────────────│
  │                                   │
```

**Mapeamento de Acoes por Plataforma:**

| ActionType        | Discord                                | Twitch              | GitHub          | Twitter | Web                |
| ----------------- | -------------------------------------- | ------------------- | --------------- | ------- | ------------------ |
| **Warn**          | DM ao usuario                          | Whisper             | Issue comment   | DM      | Notificacao in-app |
| **Mute**          | Timeout (communication_disabled_until) | Chat ban temporario | N/A             | N/A     | Restrict posting   |
| **Kick**          | Remove from guild                      | N/A                 | Remove from org | N/A     | N/A                |
| **Ban**           | Guild ban                              | Channel ban         | Block user      | Block   | Account ban        |
| **Suspend**       | Timeout longo                          | Temp ban            | N/A             | N/A     | suspended_until    |
| **ContentRemove** | Delete message                         | Delete message      | Delete comment  | N/A     | Soft-delete        |

**Falha Parcial:**

Se execucao falha em uma plataforma mas sucede em outras:

- Acao registrada como `partial_success`
- execution_results mostra detalhes por plataforma
- Moderador e notificado sobre a falha
- Pode re-tentar plataformas com falha individualmente

---

### UC-06: Dismiss (Descartar) Caso

**Actor:** Moderator
**Plataforma:** Web (Filament Admin Panel)
**Trigger:** Moderador avalia que conteudo NAO viola regras

**Fluxo Principal:**

```
 MODERADOR                           SYSTEM
  │                                   │
  │  👆 clica "Dismiss Case"          │
  │ ──────────────────────────────►  │
  │                                   │
  │    "Motivo do descarte?"          │
  │ ◄────────────────────────────────│
  │                                   │
  │    ┌──────────────────────────┐   │
  │    │ Nao viola regras         │   │
  │    │ Falso positivo (AI)      │   │
  │    │ Contexto justifica       │   │
  │    │ Conteudo ja removido     │   │
  │    │ Report abusivo           │   │
  │    └──────────────────────────┘   │
  │                                   │
  │  👆 "Falso positivo (AI)"         │
  │ ──────────────────────────────►  │
  │                                   │  Case.status = dismissed
  │                                   │  Case.resolved_at = now()
  │                                   │  AuditLog: case_dismissed
  │                                   │  ML Feedback: mark as false_positive
  │                                   │  (usado para melhorar classificador)
  │                                   │
  │    "Caso descartado."             │
  │ ◄────────────────────────────────│
  │                                   │
```

**Pos-condicoes:**

- Caso nao aparece mais na fila ativa
- Dismiss reason armazenado para analytics (taxa de falso positivo)
- Se motivo = "Report abusivo", reporter pode ser flaggado para review

---

### UC-07: Contestar Decisao (Appeal)

**Actor:** User (que recebeu acao de moderacao)
**Plataformas:** Web (formulario) ou Discord (DM ao bot com link)
**Trigger:** Usuario discorda da penalidade recebida

**Pre-condicoes:**

- Acao de moderacao existe e foi executada
- Dentro da janela de appeal (7 dias apos acao)
- Usuario nao ja tem appeal pendente para esta acao
- Acao nao e tipo "ContentRemove" isolada (nao contestavel)

**Fluxo Principal:**

```
 USER                              SYSTEM
  │                                   │
  │  👆 "Contestar esta decisao"      │
  │     (link na notificacao de       │
  │      penalidade ou /appeal cmd)   │
  │ ──────────────────────────────►  │
  │                                   │  Validate:
  │                                   │  ├─ Within 7-day window? ✓
  │                                   │  ├─ No existing appeal? ✓
  │                                   │  ├─ Appealable action type? ✓
  │                                   │  └─ User identity confirmed? ✓
  │                                   │
  │    "Selecione o motivo:"          │
  │ ◄────────────────────────────────│
  │                                   │
  │    ┌──────────────────────────┐   │
  │    │ Contexto mal interpretado│   │
  │    │ Pessoa errada            │   │
  │    │ Penalidade desproporcional│  │
  │    │ Regra nao se aplica      │   │
  │    │ Outro                    │   │
  │    └──────────────────────────┘   │
  │                                   │
  │  👆 seleciona motivo              │
  │  ⌨️ explica em detalhe            │
  │ ──────────────────────────────►  │
  │                                   │  ModerationAppeal::create(
  │                                   │    action_id, appellant_id,
  │                                   │    reason_category, reason_text,
  │                                   │    sla_deadline: now() + 48h
  │                                   │  )
  │                                   │
  │                                   │  Auto-assign reviewer:
  │                                   │  ├─ Query moderators WHERE
  │                                   │  │   id != original_moderator
  │                                   │  │   AND is_active = true
  │                                   │  │   AND current_load < max
  │                                   │  └─ Assign with lowest load
  │                                   │
  │                                   │  AuditLog: appeal_filed
  │                                   │  Notify: assigned reviewer
  │                                   │
  │    "Appeal registrado.            │
  │     Outro moderador revisara      │
  │     em ate 48 horas.              │
  │     Voce sera notificado          │
  │     do resultado."                │
  │ ◄────────────────────────────────│
  │                                   │
```

**Fluxos Alternativos:**

| Cenario                            | Comportamento                                  |
| ---------------------------------- | ---------------------------------------------- |
| Fora da janela de 7 dias           | Rejeita com "Prazo para contestacao expirado"  |
| Appeal ja existe                   | Rejeita com "Voce ja contestou esta decisao"   |
| ContentRemove sem outra penalidade | Rejeita com "Esta acao nao e contestavel"      |
| Usuario banido (sem acesso web)    | Permite appeal via email ou formulario externo |

---

### UC-08: Revisar Appeal

**Actor:** Senior Moderator (diferente do moderador original)
**Plataforma:** Web (Filament Admin Panel)
**Trigger:** Appeal atribuido ao reviewer

**Pre-condicoes:**

- Reviewer != moderador que tomou acao original
- Appeal status = pending
- Dentro do SLA (48h)

**Fluxo Principal:**

```
 REVIEWER                            SYSTEM
  │                                   │
  │  👆 abre ModerationAppealResource │
  │ ──────────────────────────────►  │
  │                                   │  Query: appeals WHERE
  │                                   │  reviewer_id = me
  │                                   │  AND status IN (pending, reviewing)
  │                                   │  ORDER BY sla_deadline ASC
  │                                   │
  │    ┌──────────────────────────────────────────────────┐
  │    │ APPEAL #def456                                    │
  │    │──────────────────────────────────────────────────│
  │    │                                                   │
  │    │ 📋 ORIGINAL CASE                                  │
  │    │ Content: "Voces sao todos incompetentes..."      │
  │    │ AI Score: toxicity 0.78                           │
  │    │ Reports: 2                                        │
  │    │                                                   │
  │    │ ⚖️ ACTION TAKEN                                   │
  │    │ Moderator: @mod1                                  │
  │    │ Action: Mute 7 days                               │
  │    │ Reason: "Toxicidade e desrespeito"               │
  │    │ Platforms: Discord, Web                           │
  │    │ Date: 2026-05-01 14:30                            │
  │    │                                                   │
  │    │ 📝 APPEAL                                         │
  │    │ Category: Contexto mal interpretado               │
  │    │ Text: "Eu estava falando sobre o codigo,         │
  │    │  nao sobre as pessoas. 'Voces' era sobre         │
  │    │  os metodos da classe, nao os devs."             │
  │    │                                                   │
  │    │ 👤 USER HISTORY                                   │
  │    │ Member since: 2024-03-15 (2 years)                │
  │    │ Prior offenses: 0                                 │
  │    │ Contributions: 47 messages/week avg               │
  │    │                                                   │
  │    │ 🕐 SLA: 36h remaining                            │
  │    │                                                   │
  │    │──────────────────────────────────────────────────│
  │    │ DECISION:                                         │
  │    │                                                   │
  │    │ [✓ Upheld (manter decisao)]                      │
  │    │ [↩ Overturn (reverter decisao)]                  │
  │    │                                                   │
  │    │ Notes: [_________________________________]        │
  │    └──────────────────────────────────────────────────┘
  │                                   │
  │  👆 seleciona "Overturn"           │
  │  ⌨️ "Membro antigo, contexto de    │
  │     code review, sem historico"    │
  │ ──────────────────────────────►  │
  │                                   │  Appeal.status = overturned
  │                                   │  Appeal.resolved_at = now()
  │                                   │
  │                                   │  REVERSAL:
  │                                   │  ├─ DiscordAdapter: remove mute
  │                                   │  ├─ WebAdapter: remove restriction
  │                                   │  └─ ExecutionResults recorded
  │                                   │
  │                                   │  Notify user:
  │                                   │  "Appeal aceito. Penalidade revertida."
  │                                   │
  │                                   │  AuditLog: appeal_overturned
  │                                   │  ML Feedback: overturned → adjust model
  │                                   │
  │    "Appeal processado.            │
  │     Decisao revertida.            │
  │     Usuario notificado."          │
  │ ◄────────────────────────────────│
  │                                   │
```

**Decisoes possiveis:**

| Decisao        | Efeito                                                                                                  |
| -------------- | ------------------------------------------------------------------------------------------------------- |
| **Upheld**     | Penalidade mantida. Usuario notificado: "Appeal negado". Caso encerrado.                                |
| **Overturned** | Penalidade revertida em todas plataformas. Usuario notificado. Acao original marcada como "overturned". |

**SLA Breach:**

- Se 48h expiram sem decisao: alert para Admin
- Appeal nao e auto-aprovado por SLA breach
- Admin pode re-atribuir ou decidir

---

### UC-09: Gerenciar Regras de Moderacao

**Actor:** Senior Moderator / Admin
**Plataforma:** Web (Filament Admin Panel)
**Trigger:** Necessidade de adicionar/modificar regras de deteccao

**Pre-condicoes:**

- Actor tem permissao de gerenciamento de regras

**Fluxo Principal:**

```
 ADMIN                               SYSTEM
  │                                   │
  │  👆 abre ModerationRuleResource   │
  │ ──────────────────────────────►  │
  │                                   │
  │    ┌──────────────────────────────────────────────────┐
  │    │ MODERATION RULES                                  │
  │    ├──────┬──────────┬──────────┬────────┬───────────┤
  │    │Active│ Name     │ Type     │Platform│ Severity  │
  │    ├──────┼──────────┼──────────┼────────┼───────────┤
  │    │ ✓    │ Spam URLs│ regex    │ all    │ high      │
  │    │ ✓    │ Slurs    │ keyword  │ all    │ critical  │
  │    │ ✗    │ Self-promo│keyword  │ discord│ low       │
  │    └──────┴──────────┴──────────┴────────┴───────────┘
  │                                   │
  │  👆 clica "New Rule"              │
  │ ──────────────────────────────►  │
  │                                   │
  │    ┌──────────────────────────┐   │
  │    │ Name: [Crypto Scam URLs ]│   │
  │    │ Type: [regex         ▼]  │   │
  │    │ Pattern: [https?://      │   │
  │    │   (crypto|nft|airdrop)   │   │
  │    │   .*\.(xyz|click|link)]  │   │
  │    │ Platform: [All       ▼]  │   │
  │    │ Violation: [Spam     ▼]  │   │
  │    │ Severity: [High      ▼]  │   │
  │    │ Action: [Ban         ▼]  │   │
  │    │ Active: [✓]              │   │
  │    │                          │   │
  │    │ TEST RULE:                │   │
  │    │ Input: [______________]  │   │
  │    │ [▶ Test]                  │   │
  │    │ Result: ✓ MATCH / ✗ NO   │   │
  │    │                          │   │
  │    │ [💾 Save]  [Cancel]       │   │
  │    └──────────────────────────┘   │
  │                                   │
  │  ⌨️  preenche campos               │
  │  👆 testa com sample content      │
  │  👆 clica "Save"                  │
  │ ──────────────────────────────►  │
  │                                   │  ModerationRule::create(...)
  │                                   │  Rule available immediately
  │                                   │  (hot-reload, no deploy needed)
  │                                   │  AuditLog: rule_created
  │                                   │
  │    "Regra criada e ativa."        │
  │ ◄────────────────────────────────│
  │                                   │
```

**Tipos de Regra:**

| Tipo          | Pattern Format                           | Exemplo                                    |
| ------------- | ---------------------------------------- | ------------------------------------------ |
| **keyword**   | Lista de palavras (case-insensitive)     | `scam, crypto free, airdrop`               |
| **regex**     | Expressao regular                        | `https?://(crypto\|nft).*\.(xyz\|click)`   |
| **threshold** | JSON config para thresholds customizados | `{"toxicity": 0.6, "platform": "discord"}` |

**Hot-Reload:** Regras sao carregadas do banco a cada execucao do `RuleBasedClassifier`. Sem necessidade de deploy ou restart.

---

### UC-10: Moderacao via Slash Command (Discord/Twitch)

**Actor:** Moderator
**Plataforma:** Discord ou Twitch (via chat command)
**Trigger:** Moderador quer agir rapido sem abrir o painel web

**Pre-condicoes:**

- Moderador tem role de moderacao na plataforma
- Target user existe na plataforma
- Moderador autenticado no sistema (linked account)

**Fluxo Principal:**

```
 MODERADOR                           SYSTEM
  │                                   │
  │  ⌨️ /warn @user reason:"spam"     │
  │     OU                            │
  │  ⌨️ /mute @user duration:"7d"     │
  │     reason:"toxicidade"           │
  │     OU                            │
  │  ⌨️ /ban @user reason:"raid bot"  │
  │ ──────────────────────────────►  │
  │                                   │  SlashCommand received
  │                                   │  │
  │                                   │  ├─ Resolve moderator (ExternalIdentity)
  │                                   │  ├─ Validate: has mod permission? ✓
  │                                   │  ├─ Resolve target user
  │                                   │  │
  │                                   │  ├─ Create ModerationCase(source: manual_flag)
  │                                   │  ├─ Create ModerationAction(
  │                                   │  │    action_type, duration, reason,
  │                                   │  │    target_platforms: [current_platform]
  │                                   │  │  )
  │                                   │  │
  │                                   │  ├─ dispatchSync(ExecuteAction)
  │                                   │  │   └─ PlatformAdapter::execute()
  │                                   │  │
  │                                   │  ├─ AuditLog: action_via_command
  │                                   │  └─ Notify target user
  │                                   │
  │    "✓ @user banned.               │
  │     Reason: raid bot              │
  │     Duration: permanent           │
  │     Case #abc123 created."        │
  │ ◄────────────────────────────────│
  │                                   │
```

**Comandos disponíveis:**

| Comando  | Parametros               | Acao                            |
| -------- | ------------------------ | ------------------------------- |
| `/warn`  | @user, reason            | Warn (DM + registro)            |
| `/mute`  | @user, duration, reason  | Mute/Timeout                    |
| `/kick`  | @user, reason            | Kick (Discord only)             |
| `/ban`   | @user, duration?, reason | Ban (permanent se sem duration) |
| `/case`  | @user                    | Mostra historico do usuario     |
| `/queue` | —                        | Mostra quantos casos pendentes  |

**Comportamento especial:**

- Acao via slash command cria caso E resolve em um passo (nao passa pela fila)
- Registrado com `source: manual_flag` para diferenciar de pipeline
- Todas metricas e audit log iguais ao fluxo via Filament
- Target user recebe mesma notificacao com link para appeal

---

### UC-11: Visualizar Historico de Usuario

**Actor:** Moderator / Senior Moderator
**Plataforma:** Web (Filament) ou Discord (/case command)
**Trigger:** Moderador quer contexto antes de decidir

**Fluxo:**

```
 MODERADOR                           SYSTEM
  │                                   │
  │  👆 clica no username ou          │
  │  ⌨️ /case @user                   │
  │ ──────────────────────────────►  │
  │                                   │
  │    ┌──────────────────────────────────────────────────┐
  │    │ USER MODERATION PROFILE                           │
  │    │──────────────────────────────────────────────────│
  │    │                                                   │
  │    │ 👤 @user123 (Daniel)                              │
  │    │ Member since: 2024-01-15                          │
  │    │ Platforms: Discord ✓, Web ✓, GitHub ✓            │
  │    │                                                   │
  │    │ 📊 STATS (last 30 days)                           │
  │    │ Cases: 3 | Actions: 2 | Appeals: 1 (overturned) │
  │    │                                                   │
  │    │ 📜 TIMELINE                                       │
  │    │ ┌────────────────────────────────────────────┐   │
  │    │ │ 2026-04-28 | Warn | spam | @mod2          │   │
  │    │ │ 2026-04-15 | Mute 24h | toxicity | @mod1  │   │
  │    │ │   └─ Appeal: overturned by @mod3           │   │
  │    │ │ 2026-03-01 | Warn | harassment | @mod1    │   │
  │    │ │ 2025-12-10 | Warn | spam | System         │   │
  │    │ └────────────────────────────────────────────┘   │
  │    │                                                   │
  │    │ 💡 PENALTY SUGGESTION (if new violation):        │
  │    │ Next offense → Mute 7d (based on 2 active warns)│
  │    │                                                   │
  │    └──────────────────────────────────────────────────┘
  │                                   │
```

---

### UC-12: Dashboard de Metricas

**Actor:** Admin / Senior Moderator
**Plataforma:** Web (Filament Admin Panel)
**Trigger:** Visualizar saude do sistema de moderacao

**Metricas exibidas:**

```
┌─────────────────────────────────────────────────────────────────────────┐
│ MODERATION DASHBOARD                                                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │
│  │ Pending     │  │ Resolved    │  │ Avg Time    │  │ Appeal Rate │  │
│  │    12       │  │   847       │  │   23min     │  │    4.2%     │  │
│  │ cases       │  │ this month  │  │ to resolve  │  │ of actions  │  │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘  │
│                                                                          │
│  ┌─────────────────────────────┐  ┌──────────────────────────────────┐ │
│  │ CASES BY STATUS (donut)     │  │ CASES BY PLATFORM (bar)          │ │
│  │                             │  │                                  │ │
│  │     ████ Pending: 12       │  │ Discord  ████████████████ 68%   │ │
│  │   ██████ Resolved: 847     │  │ Web      ████████ 22%           │ │
│  │     ████ Dismissed: 234    │  │ Twitch   ███ 7%                 │ │
│  │       ██ Escalated: 3      │  │ GitHub   █ 3%                   │ │
│  │                             │  │                                  │ │
│  └─────────────────────────────┘  └──────────────────────────────────┘ │
│                                                                          │
│  ┌─────────────────────────────┐  ┌──────────────────────────────────┐ │
│  │ TOP VIOLATION TYPES         │  │ MODERATOR PERFORMANCE            │ │
│  │                             │  │                                  │ │
│  │ 1. Spam         45%        │  │ @mod1: 156 cases, 12min avg     │ │
│  │ 2. Toxicity     28%        │  │ @mod2: 134 cases, 18min avg     │ │
│  │ 3. Harassment   15%        │  │ @mod3:  89 cases, 25min avg     │ │
│  │ 4. NSFW          8%        │  │                                  │ │
│  │ 5. Other         4%        │  │ Appeals overturned: 8%           │ │
│  │                             │  │ (healthy: <15%)                  │ │
│  └─────────────────────────────┘  └──────────────────────────────────┘ │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │ APPEALS SLA TRACKER                                               │   │
│  │                                                                   │   │
│  │ ⚠️ 2 appeals approaching SLA deadline:                           │   │
│  │   - Appeal #xyz: 6h remaining (reviewer: @mod3)                  │   │
│  │   - Appeal #abc: 12h remaining (reviewer: @mod1)                 │   │
│  │                                                                   │   │
│  │ ✓ 14 appeals resolved within SLA this month (100%)              │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │ FALSE POSITIVE RATE (classifier accuracy)                         │   │
│  │                                                                   │   │
│  │ Cases dismissed as false_positive: 12%                           │   │
│  │ Trend: ↓ improving (was 18% last month)                         │   │
│  │                                                                   │   │
│  │ By classifier:                                                    │   │
│  │   OpenAI: 8% FP rate                                            │   │
│  │   Rules:  15% FP rate (review rule "self-promo")                │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

### UC-13: Auditoria (Audit Log)

**Actor:** Admin
**Plataforma:** Web (Filament Admin Panel)
**Trigger:** Investigacao, compliance, ou revisao de conduta de moderador

**Eventos registrados:**

| Event Type            | Actor     | Dados                                 |
| --------------------- | --------- | ------------------------------------- |
| `report_submitted`    | user      | reporter_id, content_id, reason       |
| `case_created`        | system    | case_id, source, platform             |
| `case_auto_flagged`   | system    | case_id, scores, suggested_action     |
| `case_auto_dismissed` | system    | case_id, scores (all below threshold) |
| `case_assigned`       | moderator | case_id, assigned_to                  |
| `case_escalated`      | moderator | case_id, reason, from_moderator       |
| `case_dismissed`      | moderator | case_id, dismiss_reason               |
| `action_executed`     | moderator | action_id, type, platforms, results   |
| `action_via_command`  | moderator | action_id, command, platform          |
| `appeal_filed`        | user      | appeal_id, action_id, reason          |
| `appeal_assigned`     | system    | appeal_id, reviewer_id                |
| `appeal_upheld`       | moderator | appeal_id, notes                      |
| `appeal_overturned`   | moderator | appeal_id, notes, reversal_results    |
| `rule_created`        | admin     | rule_id, name, pattern                |
| `rule_updated`        | admin     | rule_id, changes                      |
| `rule_toggled`        | admin     | rule_id, is_active                    |

**Propriedades de todo registro:**

- Imutavel (INSERT only, nunca UPDATE/DELETE)
- Particionado por mes (performance em queries de range)
- Inclui: actor_id, actor_type, timestamp, tenant_id, platform, details (JSONB)

---

## Acoes de Moderacao — Referencia Completa

### Warn (Aviso)

| Propriedade              | Valor                                          |
| ------------------------ | ---------------------------------------------- |
| **Efeito**               | Registro no historico + notificacao ao usuario |
| **Reversivel**           | Sim (appeal pode remover do historico)         |
| **Duração**              | Permanente no historico (nao expira)           |
| **Conta para escalacao** | Sim                                            |
| **Plataformas**          | Todas                                          |
| **Execucao**             | DM/notificacao ao usuario com motivo           |

### Mute (Silenciar)

| Propriedade              | Valor                                                |
| ------------------------ | ---------------------------------------------------- |
| **Efeito**               | Usuario nao pode enviar mensagens/posts              |
| **Reversivel**           | Sim (expira automaticamente ou via appeal)           |
| **Duração**              | 1h, 24h, 7d, 30d (obrigatorio)                       |
| **Conta para escalacao** | Sim                                                  |
| **Plataformas**          | Discord (timeout), Web (restrict), Twitch (chat ban) |
| **Execucao**             | API call por plataforma + timer para expirar         |

### Kick (Remover)

| Propriedade              | Valor                                     |
| ------------------------ | ----------------------------------------- |
| **Efeito**               | Usuario removido do servidor/organizacao  |
| **Reversivel**           | Parcial (pode re-entrar com invite)       |
| **Duração**              | Imediato (one-time action)                |
| **Conta para escalacao** | Sim                                       |
| **Plataformas**          | Discord (guild kick), GitHub (org remove) |
| **Execucao**             | API call, usuario pode voltar             |

### Ban (Banir)

| Propriedade              | Valor                                                        |
| ------------------------ | ------------------------------------------------------------ |
| **Efeito**               | Usuario impedido de acessar plataforma                       |
| **Reversivel**           | Sim (via appeal ou admin)                                    |
| **Duração**              | 7d, 30d, permanent (obrigatorio)                             |
| **Conta para escalacao** | Sim (mas geralmente e o fim da escala)                       |
| **Plataformas**          | Todas                                                        |
| **Execucao**             | API call por plataforma + agendamento de unban se temporario |

### Suspend (Suspender)

| Propriedade              | Valor                                            |
| ------------------------ | ------------------------------------------------ |
| **Efeito**               | Conta suspensa temporariamente (acesso limitado) |
| **Reversivel**           | Sim (expira ou appeal)                           |
| **Duração**              | 7d, 30d (obrigatorio)                            |
| **Conta para escalacao** | Sim                                              |
| **Plataformas**          | Web (suspended_until), Discord (long timeout)    |
| **Execucao**             | DB flag + middleware check                       |

### ContentRemove (Remover Conteudo)

| Propriedade              | Valor                                                            |
| ------------------------ | ---------------------------------------------------------------- |
| **Efeito**               | Conteudo especifico removido/ocultado                            |
| **Reversivel**           | Sim (restore from snapshot)                                      |
| **Duração**              | Permanente (conteudo soft-deleted)                               |
| **Conta para escalacao** | Nao (isoladamente)                                               |
| **Plataformas**          | Discord (delete msg), Web (soft-delete), GitHub (delete comment) |
| **Execucao**             | API call + DB soft-delete                                        |

---

## Regras de Negocio

### Deduplicacao de Reports

```
IF report.content_id == existing_case.content_id
   AND existing_case.status IN (pending, assigned)
THEN
   → Add report to existing case
   → case.priority += 10 (per unique reporter)
   → Do NOT create new case
ELSE
   → Create new case
```

### Priority Scoring

```
base_priority = ai_score * 100                    (0-100)
report_boost  = count(reports) * 10               (+10 per report, max +50)
history_boost = count(prior_offenses_30d) * 5     (+5 per offense, max +25)
severity_boost = severity.critical ? 20 : 0       (+20 if critical)
account_age   = account_age < 24h ? 15 : 0       (+15 if new account)

final_priority = min(base_priority + report_boost + history_boost
                     + severity_boost + account_age, 100)
```

### Escalacao Automatica de Prioridade

| Condicao                     | Efeito                           |
| ---------------------------- | -------------------------------- |
| Caso pending > 2h            | priority += 5                    |
| Caso pending > 6h            | priority += 10, alert moderators |
| Caso pending > 24h           | priority += 20, alert admin      |
| 5+ reports no mesmo conteudo | auto-escalate to senior          |

### Appeal Rules

| Regra                         | Valor                                            |
| ----------------------------- | ------------------------------------------------ |
| Janela para appeal            | 7 dias apos acao                                 |
| SLA de resposta               | 48 horas                                         |
| Reviewer                      | OBRIGATORIAMENTE diferente do mod original       |
| Max appeals por acao          | 1                                                |
| Acoes appealable              | warn, mute, kick, ban, suspend                   |
| Acoes NAO appealable          | content_remove (isolado, sem penalidade ao user) |
| Ban permanente durante appeal | Mantido (nao suspende ban pending appeal)        |

### Notificacao ao Usuario

Toda acao de moderacao gera notificacao com:

```
Voce recebeu uma [ACTION] na comunidade He4rt Developers.

Motivo: [REASON]
Duracao: [DURATION]
Plataformas afetadas: [PLATFORMS]
Data: [TIMESTAMP]

Se voce discorda desta decisao, pode contestar em ate 7 dias:
[APPEAL_LINK]

Codigo do caso: [CASE_ID]
```

### Protecoes contra Abuso

| Cenario                          | Protecao                                           |
| -------------------------------- | -------------------------------------------------- |
| Report flood (mesmo user)        | Rate limit: max 10 reports/hora                    |
| Report abusivo repetido          | Flag reporter para review apos 3 reports dismissed |
| Moderador bane sem motivo        | Audit trail + metricas de overturn rate            |
| Appeal abusivo                   | 1 appeal por acao, texto obrigatorio               |
| Auto-moderacao (report si mesmo) | Bloqueado                                          |
| Moderador modera amigo/inimigo   | Metricas de distribuicao por moderador             |

---

## Integracao entre Plataformas

### Resolucao de Identidade

O sistema usa a tabela `providers` (ExternalIdentity) existente para mapear usuarios entre plataformas:

```
User #abc123
├── Discord: 123456789 (@danielhe4rt)
├── Twitch: 987654321 (danielhe4rt)
├── GitHub: 555666777 (danielhe4rt)
└── Web: abc123 (daniel@he4rt.com)
```

Quando uma acao e executada com fan-out, o sistema:

1. Busca todas ExternalIdentities do target user
2. Filtra pelas plataformas selecionadas pelo moderador
3. Executa em cada adapter disponivel
4. Registra resultado individual por plataforma

### Conteudo Cross-Platform

Se usuario e reportado no Discord E na Web pelo mesmo comportamento:

- Reports consolidados no mesmo Case (se dentro de janela temporal de 24h)
- Priority boost por ser multi-platform
- Moderador ve contexto de ambas plataformas no Case detail

---

## Permissoes (Authorization Matrix)

| Acao                    | User | Moderator   | Senior Mod | Admin |
| ----------------------- | ---- | ----------- | ---------- | ----- |
| Submit report           | ✓    | ✓           | ✓          | ✓     |
| View own history        | ✓    | ✓           | ✓          | ✓     |
| File appeal             | ✓    | ✓           | ✓          | ✓     |
| View moderation queue   | ✗    | ✓           | ✓          | ✓     |
| Assign case to self     | ✗    | ✓           | ✓          | ✓     |
| Execute warn            | ✗    | ✓           | ✓          | ✓     |
| Execute mute            | ✗    | ✓           | ✓          | ✓     |
| Execute kick            | ✗    | ✗           | ✓          | ✓     |
| Execute ban (temp)      | ✗    | ✓           | ✓          | ✓     |
| Execute ban (permanent) | ✗    | ✗           | ✓          | ✓     |
| Escalate case           | ✗    | ✓           | ✓          | ✓     |
| Review appeal           | ✗    | ✗           | ✓          | ✓     |
| Create/edit rules       | ✗    | ✗           | ✓          | ✓     |
| View dashboard metrics  | ✗    | ✓ (limited) | ✓          | ✓     |
| View audit log          | ✗    | ✗           | ✗          | ✓     |
| Manage moderators       | ✗    | ✗           | ✗          | ✓     |
| Configure system        | ✗    | ✗           | ✗          | ✓     |
