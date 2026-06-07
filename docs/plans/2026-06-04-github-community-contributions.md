# GitHub Community Contributions — Plano de implementação

> Substitui a abordagem provisória (`app/Retrospective/*` + `community:retrospective`, que rodava `gh` ao vivo e subia JSON no waifuvault) por uma ingestão persistente, modelada dentro da arquitetura modular do projeto.
>
> Origem das decisões: sessão de grilling (2026-06-04). Base e PR: **`4.x`** (o `3.x` não tem `activity` nem `integration-github`).

## Decisões cravadas

| #   | Decisão      | Escolha                                                                                   |
| --- | ------------ | ----------------------------------------------------------------------------------------- |
| 1   | Público      | Todos os contribuidores (por login GitHub); gamificação é enriquecimento opcional         |
| 2   | Modelagem    | `github_contributions` (normalizado) + `github_event_logs` (lake bruto) — espelha Discord |
| 3   | Credencial   | PAT fine-grained + webhook de org manual (secret verificado)                              |
| 4   | Escopo repos | Allowlist gerenciável no painel admin (`github_repositories`)                             |
| 5   | Tipos        | Core de código: PR, review, issue, comentário, commit (sem reactions)                     |
| 6   | Backfill     | Histórico completo, incremental/resumível via `last_backfilled_at`                        |
| 7   | Fluxo ETL    | Split: webhook→lake→ETL→contributions; backfill→upsert direto                             |
| 8   | Gamificação  | Só a seam agora (evento `GithubContributionRecorded`)                                     |
| 9   | Apresentação | Página pública no portal (Livewire), seletor de período, `?since=&until=`                 |
| 10  | Filtragem    | Grava tudo, filtra na leitura (bots, PR closed-unmerged)                                  |
| 11  | Limpeza/base | Dropar `dda959be`, base e PR no `4.x`                                                     |

---

## Arquitetura macro (sistema)

```
  ┌───────────────────────────────────────────────────────────────────┐
  │                   integration-github  (transport)                  │
  │                                                                    │
  │  Transport/                                                        │
  │   GitHubApiConnector  (+ PAT via defaultAuth)                      │
  │   Requests/  ListPullRequests · GetPullRequest · ListIssues ·      │
  │              ListReviews · ListIssueComments · ListPrComments ·    │
  │              ListCommits · GetUser                                 │
  │  Backfill/   BackfillRepository (action)  +  console command       │
  │  Webhook/    VerifyGithubSignature (mw) · GithubWebhookController   │
  │              ProjectGithubEvent (ETL action)                       │
  │  Models/     GithubRepository · GithubContribution · GithubEventLog│
  │  Events/     GithubContributionRecorded   ◄── seam p/ gamificação  │
  └───────────────┬─────────────────────────────────────┬─────────────┘
                  │ lê contributions                     │ CRUD allowlist
                  ▼                                       ▼
  ┌───────────────────────────────┐        ┌───────────────────────────────┐
  │   portal (Livewire)           │        │  panel-admin (Filament)        │
  │   /comunidade/retrospectiva   │        │  Github/Resources/             │
  │   seletor de período          │        │   GithubRepositoryResource     │
  │   read-model + filtros (leitura)│       │   (+ Contribution/EventLog ro) │
  └───────────────────────────────┘        └───────────────────────────────┘
```

### Regras de dependência (a registrar no CONTEXT-MAP.md)

- `integration-github` é **transport**: não depende de `activity`, `economy` nem `moderation`.
- `integration-github` depende de `identity` **apenas** para a seam futura (resolver `Character` via `ExternalIdentity`) — hoje só emite o evento `GithubContributionRecorded`, sem importar `activity`.
- `panel-admin` e `portal` dependem de `integration-github` (consomem os modelos). Nunca o contrário.

---

## Modelo de dados

```
github_repositories            ── allowlist editável no painel
  id              uuid pk
  full_name       string  "he4rt/heartdevs.com"   (unique)
  enabled         bool    default true
  last_backfilled_at timestamp null
  timestamps

github_contributions           ── read-model da apresentação
  id              uuid pk
  repo            string  (index)          "he4rt/heartdevs.com"
  actor_login     string                   "@maria"
  actor_id        bigint  null (index)      perfil GitHub estável a rename
  type            string  (index)          pr | review | issue | comment | commit
  external_ref    string                   "pr:123" "review:456" "commit:<sha>"
  target_ref      string  null             a qual PR/issue pertence ("pr:123")
  occurred_at     timestamp (index)
  metadata        jsonb   null             title, additions, deletions, files,
                                           url, state, merged, is_bot, avatar, name
  timestamps
  unique (repo, type, external_ref)        ── idempotência backfill+webhook

github_event_logs              ── lake bruto, só webhook (replay/auditoria)
  id              bigint pk
  event_type      string (index)           "pull_request" "push" ...
  repo            string null (index)
  actor_login     string null
  delivery_id     string (index, unique)   X-GitHub-Delivery (dedup)
  payload         jsonb
  timestamps
```

### Esquema de `external_ref` / `target_ref`

| type    | external_ref     | target_ref             | fonte do `occurred_at` |
| ------- | ---------------- | ---------------------- | ---------------------- |
| pr      | `pr:{number}`    | —                      | `created_at` do PR     |
| review  | `review:{id}`    | `pr:{number}`          | `submitted_at`         |
| issue   | `issue:{number}` | —                      | `created_at` da issue  |
| comment | `comment:{id}`   | `pr:{n}`/`issue:{n}`   | `created_at`           |
| commit  | `commit:{sha}`   | `pr:{n}` (se via push) | `commit.author.date`   |

---

## Fatia 0 — Limpeza + base no 4.x

**Contexto.** A branch `feat/community-retrospective` é `origin/4.x` (`c5c45e07`) + um único commit provisório `dda959be`, que adicionou `app/Retrospective/*`, `app/Console/Commands/RetrospectiveCommand.php`, registro no `app/Providers/AppServiceProvider.php` (linhas 10 e 30‑33), testes e docs de design. A decisão #11 é apagar tudo isso e recomeçar do `4.x` limpo. Como `dda959be` é o HEAD e não há nada depois dele, basta resetar a branch para `origin/4.x`. A branch ainda não tem PR aberto; se já estiver no remoto, o reset exige `--force-with-lease`.

**Comportamento esperado.**

```
Given a branch feat/community-retrospective = origin/4.x + dda959be
When  reseto a branch para origin/4.x
Then  app/Retrospective/, RetrospectiveCommand, docs/retrospectivas e os planos
      provisórios somem do working tree
And   app/Providers/AppServiceProvider.php volta ao estado do 4.x (sem o bind
      de RetrospectiveHistory e sem o import)
And   git diff origin/4.x..HEAD fica vazio (base limpa para as próximas fatias)
```

- Edge: se a branch já foi pushada → `git push --force-with-lease`. Se houver stash/WIP não commitado → abortar e avisar antes.
- Backward compat: nenhuma — o command provisório nunca foi mergeado no `4.x`.

**Antes** (`app/Providers/AppServiceProvider.php`):

```php
use App\Retrospective\RetrospectiveHistory;
// ...
$this->app->bind(
    RetrospectiveHistory::class,
    fn(): RetrospectiveHistory => new RetrospectiveHistory(base_path('docs/retrospectivas/README.md')),
);
```

**Depois:** linhas removidas (volta ao `AppServiceProvider` do `4.x`).

Operação: `git reset --hard origin/4.x` (executar só após aprovação do plano).

---

## Fatia 1 — Fundação do módulo: allowlist + admin + docs

**Contexto.** O `integration-github` hoje só tem OAuth (`GetCurrentUser`) e um `GitHubApiConnector` **sem autenticação**, e **não tem `CONTEXT.md`**. Esta fatia entrega a menor vertical completa e útil sozinha: cadastrar/editar quais repos contam, pelo painel admin. Cria a tabela `github_repositories`, o modelo, o `GithubRepositoryResource` no `panel-admin` (espelhando `Twitch/Resources/`), e a documentação do módulo (`CONTEXT.md` + ADR) que registra as 11 decisões.

**Comportamento esperado.**

```
# Happy path
Given um admin logado no panel-admin
When  ele cria um repositório "he4rt/4noobs" e marca enabled
Then  surge uma linha em github_repositories com enabled=true, last_backfilled_at=null
And   ela passa a ser candidata a backfill e a filtro de webhook

# Edge — duplicado
Given já existe "he4rt/4noobs"
When  tentam criar de novo
Then  a unique(full_name) barra e o Filament mostra erro de validação

# Edge — formato inválido
When  informam "4noobs" (sem owner)
Then  validação "owner/repo" rejeita antes de salvar

# Backward compat
Given nenhuma allowlist cadastrada
Then  backfill e webhook simplesmente não processam nada (no-op seguro)
```

**Depois** (migration nova `…_create_github_repositories_table.php`):

```php
Schema::create('github_repositories', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->string('full_name')->unique(); // owner/repo
    $table->boolean('enabled')->default(true);
    $table->timestamp('last_backfilled_at')->nullable();
    $table->timestamps();
});
```

**Depois** (`integration-github/src/Models/GithubRepository.php`):

```php
/**
 * @property string $id
 * @property string $full_name
 * @property bool $enabled
 * @property Carbon|null $last_backfilled_at
 */
final class GithubRepository extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'last_backfilled_at' => 'datetime'];
    }

    /** @return Builder<self> */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }
}
```

**Depois** (`panel-admin/src/Github/Resources/GithubRepositoryResource.php` — esqueleto, espelhando `TwitchSubscriptionResource`):

```php
public static function form(Schema $schema): Schema
{
    return $schema->components([
        TextInput::make('full_name')
            ->required()->unique(ignoreRecord: true)
            ->rule('regex:/^[\w.-]+\/[\w.-]+$/'),   // owner/repo
        Toggle::make('enabled')->default(true),
    ]);
}
// table(): full_name, enabled (toggle), last_backfilled_at, ação "Backfill agora"
```

CONTEXT.md/ADR: criar `integration-github/CONTEXT.md` (estilo `integration-discord`) + `integration-github/docs/adr/0001-github-community-contributions.md` registrando as decisões; atualizar `CONTEXT-MAP.md`.

---

## Fatia 2 — Auth PAT + store de contribuições + backfill de PRs (tracer bullet)

**Contexto.** Primeira vertical de dados ponta-a-ponta: autenticar o `GitHubApiConnector` com o PAT, criar a tabela/modelo `github_contributions`, e um backfill que lista os PRs de **um** repo da allowlist e faz upsert idempotente. Escolhemos PR como tracer porque exercita paginação, dedup e o enriquecimento de tamanho (additions/deletions/files) — que exige uma sub-chamada `GET /repos/{repo}/pulls/{n}` por PR (a list endpoint não traz esses campos, como o provisório já fazia via `gh pr view`).

**Antes** (`GitHubApiConnector` — sem auth):

```php
final class GitHubApiConnector extends Connector
{
    use HasTimeout;
    public function resolveBaseUrl(): string
    {
        return 'https://api.github.com';
    }
}
```

**Depois** (PAT via `defaultAuth`, mesmo padrão de `GetCurrentUser`):

```php
final class GitHubApiConnector extends Connector
{
    use HasTimeout;
    public function resolveBaseUrl(): string
    {
        return 'https://api.github.com';
    }

    protected function defaultAuth(): ?TokenAuthenticator
    {
        $token = config('integration-github.api_token');
        return $token ? new TokenAuthenticator($token) : null;
    }
}
```

**Depois** (`config/services.php`):

```php
'github' => [
    // ...OAuth existente...
    'api_token'      => env('GITHUB_API_TOKEN'),       // PAT fine-grained (backfill)
    'webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),  // usado na Fatia 3
],
```

**Depois** (migration `…_create_github_contributions_table.php`): conforme o [modelo de dados](#modelo-de-dados), com `unique(['repo','type','external_ref'])` e índices em `repo`, `actor_id`, `type`, `occurred_at`.

**Depois** (`Backfill/BackfillRepository.php` — núcleo do upsert):

```php
GithubContribution::query()->updateOrCreate(
    ['repo' => $repo, 'type' => 'pr', 'external_ref' => "pr:{$pr['number']}"],
    [
        'actor_login' => $pr['user']['login'],
        'actor_id' => $pr['user']['id'] ?? null,
        'occurred_at' => $pr['created_at'],
        'metadata' => [
            'title' => $pr['title'],
            'state' => $pr['state'],
            'merged' => $pr['merged_at'] !== null,
            'url' => $pr['html_url'],
            'is_bot' => str_ends_with($pr['user']['login'], '[bot]'),
            'additions' => $detail['additions'],
            'deletions' => $detail['deletions'],
            'changed_files' => $detail['changed_files'],
        ],
    ],
);
```

**Comportamento esperado.**

```
# Happy path
Given "he4rt/heartdevs.com" enabled e GITHUB_API_TOKEN configurado
When  rodo o backfill de PRs do repo
Then  cada PR vira 1 linha em github_contributions (type=pr) com tamanho no metadata

# Idempotência (re-run)
When  rodo o backfill de novo
Then  updateOrCreate atualiza as mesmas linhas (0 duplicadas), graças à unique

# Edge — PR de bot
Given um PR aberto por dependabot[bot]
Then  a linha é gravada com metadata.is_bot=true (filtragem fica na leitura)

# Edge — sem token
Given GITHUB_API_TOKEN ausente
Then  defaultAuth retorna null, a API responde 401/rate-limit e o command falha
      com mensagem clara (não grava lixo)

# Edge — rate limit (403/429)
Then  respeita Retry-After / X-RateLimit-Reset e continua de onde parou
```

Depois desta fatia, replicar o mesmo padrão de Request+upsert para **issues, reviews, comentários e commits** (cada um com seu `external_ref`/`target_ref`).

---

## Fatia 3 — Webhook ao vivo (lake + ETL + seam)

**Contexto.** Captura das mudanças (crítico #3). Espelha o Twitch EventSub: rota `routes/github-webhook-routes.php` (auto-carregada pelo `internachi/modular`), middleware `VerifyGithubSignature` (HMAC `X-Hub-Signature-256`), controller que grava o payload bruto em `github_event_logs` (dedup por `X-GitHub-Delivery`) e dispara `ProjectGithubEvent`, que filtra pela allowlist e faz o mesmo upsert da Fatia 2, emitindo `GithubContributionRecorded` ao final.

```
  GitHub org ──POST──► VerifyGithubSignature ──► GithubWebhookController
   (assinado)           (HMAC sha256, secret)      │ grava lake (delivery_id)
                                                    ▼
                                            ProjectGithubEvent (ETL)
                                            ├─ repo ∈ allowlist? senão ignora
                                            ├─ mapeia event_type → type/ref
                                            ├─ upsert github_contributions
                                            └─ event GithubContributionRecorded
```

**Antes** (Twitch — referência de assinatura):

```php
$expectedSignature = 'sha256=' . hash_hmac('sha256', $hmacMessage, $secret);
abort_unless(hash_equals($expectedSignature, $signature), 403, 'Invalid signature');
```

**Depois** (`VerifyGithubSignature`):

```php
$signature = $request->header('X-Hub-Signature-256');
abort_if(!$signature, 403, 'Missing X-Hub-Signature-256');
$secret = config()->string('integration-github.webhook_secret');
$expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
abort_unless(hash_equals($expected, $signature), 403, 'Invalid signature');
```

**Depois** (`routes/github-webhook-routes.php`):

```php
Route::prefix('api/webhooks/github')
    ->middleware([VerifyGithubSignature::class])
    ->group(fn() => Route::post('/', GithubWebhookController::class)->name('github.webhook'));
```

**Comportamento esperado.**

```
# Happy path
Given webhook de org configurado com o secret correto
When  alguém abre um PR em repo da allowlist
Then  github_event_logs ganha 1 linha (payload bruto, delivery_id)
And   github_contributions ganha/atualiza a linha pr:{n}
And   GithubContributionRecorded é emitido

# Dedup de entrega
Given o GitHub reenvia a mesma entrega (mesmo X-GitHub-Delivery)
Then  a unique(delivery_id) evita reprocessar (idempotente)

# Convergência backfill↔webhook
Given pr:42 já veio do backfill
When  chega o webhook pull_request.synchronize do pr:42
Then  o upsert atualiza a MESMA linha (sem duplicar), pela unique(repo,type,ref)

# Edge — repo fora da allowlist
Then  grava no lake (auditoria) mas NÃO projeta para contributions

# Edge — assinatura inválida / secret errado
Then  403 e nada é gravado

# Reactions
Then  não há evento de webhook; permanecem fora de escopo (decisão #5)
```

Eventos assinados: `pull_request`, `pull_request_review`, `issues`, `issue_comment`, `push`.

---

## Fatia 4 — Backfill completo e resumível (todos os tipos, todos os repos)

**Contexto.** Consolida a Fatia 2 num command que percorre **todos** os repos `enabled`, faz backfill de **todos** os tipos desde a criação do repo, trata paginação e rate limit, e grava `last_backfilled_at`. Re-rodar é seguro (idempotente) e incremental (usa `since`/`last_backfilled_at` para encurtar).

**Comportamento esperado.**

```
# Happy path
When  rodo `php artisan github:backfill`
Then  para cada repo enabled, importa PRs/issues/reviews/comments/commits do histórico
And   grava last_backfilled_at = now() ao concluir cada repo

# Resumível
Given last_backfilled_at preenchido
When  rodo de novo
Then  usa `since` para buscar só o delta (menos páginas/rate limit)

# Edge — repo único
When  `php artisan github:backfill he4rt/4noobs`
Then  processa só esse repo

# Edge — rate limit no meio
Then  respeita o reset e retoma; commits (mais pesados) não estouram a janela
```

---

## Fatia 5 — Apresentação no portal (Livewire) — secundária

**Contexto.** Página pública lendo de `github_contributions`, com seletor de período e URL compartilhável. As regras de filtragem (sem bots, sem PR closed-unmerged, issue contada pelo opened) vivem **aqui, na leitura** (decisão #10). Reaproveita o design da apresentação "Quem fez a He4rt bater" (paleta `#782bf1`, anel de avatar, badges +/−).

```
  ┌─────────────────────────────────────────────────────────┐
  │  Quem fez a He4rt bater       [◄ Semana ►]  [since–until]│
  ├─────────────────────────────────────────────────────────┤
  │  pessoas · PRs · reviews · issues · comentários · commits│   meta cards
  ├─────────────────────────────────────────────────────────┤
  │  ┌────────┐  Top contribuidores (anel avatar, badges +/−)│
  │  │ avatar │  @login · N interações · +adds/−dels          │
  │  └────────┘  lista de PRs/reviews/issues com link         │
  ├─────────────────────────────────────────────────────────┤
  │  Pull Requests por frente (scope do título)               │
  └─────────────────────────────────────────────────────────┘
```

**Comportamento esperado.**

```
# Happy path
Given contribuições no período [since, until]
When  acesso /comunidade/retrospectiva?since=2026-05-26&until=2026-06-01
Then  vejo meta agregada e o ranking por pessoa (occurred_at na janela)

# Filtragem na leitura
Then  bots (metadata.is_bot) são excluídos
And   PR com state=closed e merged=false não conta
And   issue conta pelo occurred_at (opened)

# Edge — período vazio
Then  estado vazio amigável ("ninguém bateck nessa janela")

# Default
Given sem querystring
Then  janela padrão = segunda passada → hoje (igual ao provisório)
```

---

## Estratégia de testes

- **Fatia 1**: Pest Feature do Filament Resource (criar/duplicar/validar formato) + Unit do scope `enabled`.
- **Fatia 2**: Saloon `MockClient` para as Requests; teste de idempotência do upsert; teste de `is_bot`.
- **Fatia 3**: Feature da rota (assinatura válida/inválida), dedup por delivery, convergência backfill↔webhook, filtro de allowlist; assert no evento `GithubContributionRecorded`.
- **Fatia 4**: Unit do paginador/rate-limit (mock de headers) + `last_backfilled_at`.
- **Fatia 5**: Livewire test do seletor de período e das regras de filtragem na leitura.

## Riscos / pontos abertos

- **Commits sem usuário GitHub vinculado** (só email no `commit.author`): `actor_id` nulo, `actor_login` = melhor esforço (username do push ou email). Decidir se entram no ranking ou só na contagem macro.
- **Custo do enriquecimento de PR** (1 `GET /pulls/{n}` por PR): aceitável no histórico, monitorar rate limit.
- **Webhook de org cobre todos os repos**; a allowlist filtra na ETL — repos não-allowlistados ainda geram tráfego no lake (auditoria). Avaliar TTL/limpeza do lake no futuro.
- **Config de env** (`GITHUB_API_TOKEN`, `GITHUB_WEBHOOK_SECRET`) e o registro do webhook na org são passos manuais de operação (fora do código).
