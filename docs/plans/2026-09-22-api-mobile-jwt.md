---
type: plan
title: 'API mobile do He4rt App — autenticação JWT e endpoints por feature'
module: identity, events, activity, profile
status: proposed
date: 2026-09-22
author: tecrodrigocastro
related:
    prd: he4rt/heartdevs.com#531
---

# Plano — API mobile pro He4rt App (JWT)

**Goal:** nascer a API que o [he4rt/he4rt-app](https://github.com/he4rt/he4rt-app) (NativePHP Mobile, repo separado) vai consumir por HTTP, cobrindo as três áreas do `panel-app` que o PRD pede: Timeline, Eventos (com check-in) e Perfil — autenticada por JWT, não Sanctum.

**Por que JWT em vez de Sanctum:** o PoC anterior (documentado no PS da issue #531) já validou Sanctum funcionando (endpoint `/api/mobile/me` autenticado). A troca não é por limitação técnica do Sanctum — é para abrir a porta a **claims customizadas no próprio token** (role, tenant, capabilities) sem precisar de uma query extra por request, o que o Sanctum não oferece nativamente (seus tokens são opacos; abilities existem, mas não claims arbitrárias no payload). v1 usa claims mínimas (`sub`, `iat`, `exp`); o espaço pra crescer fica reservado via `JWTSubject::getJWTCustomClaims()`.

**Pacote:** [`php-open-source-saver/jwt-auth`](https://github.com/PHP-Open-Source-Saver/jwt-auth) `^2.9` — fork ativo do `tymon/jwt-auth` original (parado), com suporte confirmado a Laravel `^12|^13` e PHP `^8.3`. Compatível com o stack atual (Laravel 13.25, PHP 8.4).

**O que herdamos do PoC anterior e precisa ser desfeito:** a branch `poc/nativephp-mobile` (PR aberto em `tecrodrigocastro/heartdevs.com#1`) tem `HasApiTokens` no `User`, guard `sanctum` em `config/auth.php`, a migration de `personal_access_tokens` pra UUID, e `MobileMeController`/`api-mobile-routes.php` em `identity`. Esse trabalho vira **ponto de partida** da Feature 0 abaixo, mas o guard e o mecanismo de token trocam de Sanctum pra JWT — não dá pra só mergear como está.

---

## Onde a API vive

O PRD deixa isso como pergunta em aberto. Recomendação, com precedente já criado pelo próprio PoC:

**Cada módulo de domínio dono do recurso expõe sua própria fatia da API mobile — sem módulo novo.**

```
app-modules/{modulo}/
├── routes/api-mobile-routes.php     <- prefixo /api/mobile/{recurso}, middleware auth:api (JWT)
└── src/Http/Controllers/Mobile/     <- controllers finos, só serializam o retorno de Actions existentes
```

Por quê: nenhuma das quatro features abaixo precisa compor domínio de MAIS de um módulo dentro do mesmo endpoint — Timeline só usa `activity`, Eventos só usa `events`, Perfil só usa `profile`, Auth só usa `identity`. `auth()->user()`/`auth()->id()` (guard JWT) resolve a identidade em qualquer um deles sem import cruzado. Isso respeita a regra do `CONTEXT-MAP.md` ("a API depende do domínio, nunca o contrário") sem inventar uma categoria de módulo nova (nem Domain, nem Integration, nem Presentation encaixam perfeitamente num módulo "api-mobile" dedicado).

Se alguma feature futura precisar compor dois domínios num único payload (ex.: notificação cruzando Events + Activity), aí sim vale reabrir essa decisão.

---

## Arquitetura de autenticação (Feature 0)

### Guard novo

`config/auth.php` ganha:

```php
'guards' => [
    'api' => ['driver' => 'jwt', 'provider' => 'users'],
],
```

`User` (`app-modules/identity/src/User/Models/User.php`) implementa `Tymon\JWTAuth\Contracts\JWTSubject` (nome do namespace do pacote pode variar — conferir na doc do `php-open-source-saver/jwt-auth` no momento de implementar): `getJWTIdentifier()` retorna `$this->getKey()` (UUID), `getJWTCustomClaims()` retorna `[]` na v1.

### Fluxo OAuth → JWT

O login web hoje (`OAuthController::getAuthenticate`) termina em `Auth::login()` (sessão) + redirect pro painel Filament. Isso não serve pro mobile — o app não tem sessão, e o controller depende de `filament()->setCurrentPanel()`. Em vez de reescrever esse fluxo, ele nasce **paralelo**, reaproveitando a resolução de usuário:

1. App abre `Browser::auth()` (plugin do NativePHP) apontando pra `GET /api/mobile/auth/{provider}/redirect` (novo endpoint em `identity`, análogo ao `OAuthController::getRedirect` mas sem depender de painel Filament).
2. Provider (Discord/GitHub/Twitch — os três já suportados via `IdentityProvider::supportedProviders()`) redireciona pro callback do OAuth.
3. **Implementado diferente do rascunho inicial**: Discord/GitHub/Twitch só aceitam UMA `redirect_uri` fixa por app, cadastrada apontando pro callback web (`/auth/oauth/{provider}` → `OAuthController::getAuthenticate`) — não dava pra ter uma rota de callback mobile própria. O callback web compartilhado passou a distinguir por `OAuthIntent::MobileLogin` (lido do `state`) e, quando é esse o caso, gera um **código de troca de uso único** (curto, ~60s de TTL, guardado em cache) e redireciona pro deep link do app em vez de fazer `Auth::login()`.
4. App recebe o deep link (`he4rtapp://oauth/callback?code=...`), extrai o `code`, faz `POST /api/mobile/auth/exchange` com esse código.
5. Endpoint valida o código (uso único, expira, invalida-se após o uso — troca é atômica via `Cache::lock()`, não só `Cache::pull()`), emite `{ access_token, token_type, expires_in }` via `php-open-source-saver/jwt-auth`.

**Por que um código de troca em vez do JWT direto no deep link:** deep links (e o histórico de URLs do SO) não são um lugar seguro pra um token de longa duração passar. O código de troca é de uso único e vive segundos — se vazar, não serve pra nada depois do primeiro uso.

### Refresh

**Implementado diferente do rascunho inicial**: não existe um `refresh_token` separado — `php-open-source-saver/jwt-auth` renova o próprio access token via `JWTGuard::refresh()`, aceitando um token já expirado desde que dentro da janela `jwt.refresh_ttl` e fora da blacklist. `POST /api/mobile/auth/refresh` manda o token atual no header `Authorization` (sem middleware `auth:api`, que rejeitaria um token expirado antes mesmo do controller rodar) e devolve um novo `{ access_token, token_type, expires_in }`. `POST /api/mobile/auth/logout` invalida o token atual via blacklist.

### Endpoints da Feature 0

| Método | Rota                                   | Descrição                                                                       |
| ------ | -------------------------------------- | ------------------------------------------------------------------------------- |
| GET    | `/api/mobile/auth/{provider}/redirect` | Inicia OAuth (Discord/GitHub/Twitch)                                            |
| POST   | `/api/mobile/auth/exchange`            | Código de troca → token JWT                                                     |
| POST   | `/api/mobile/auth/refresh`             | Token atual (mesmo expirado, dentro da janela) → token novo                     |
| POST   | `/api/mobile/auth/logout`              | Invalida o token atual (blacklist)                                              |
| GET    | `/api/mobile/me`                       | Usuário autenticado (id, username, avatar) — já existe no PoC, só troca o guard |

Não existe rota de callback mobile própria — o callback do provider bate direto em `/auth/oauth/{provider}` (rota web já existente), ver passo 3 acima.

---

## Feature 1 — Timeline

Domínio: `He4rt\Activity\Timeline\*` (já existe, reaproveitado sem alteração).

| Método | Rota                                   | Action reaproveitada                                                                     |
| ------ | -------------------------------------- | ---------------------------------------------------------------------------------------- |
| GET    | `/api/mobile/timeline`                 | `TimelineFeed::builder()` (paginação simples, mesmo padrão do `Feed.php` do `panel-app`) |
| POST   | `/api/mobile/timeline`                 | `CreatePost` + `CreatePostDTO`                                                           |
| POST   | `/api/mobile/timeline/{post}/replies`  | `CreateReply` + `CreateReplyDTO`                                                         |
| DELETE | `/api/mobile/timeline/replies/{reply}` | `DeleteReply`                                                                            |

Sem gap de domínio — é serialização pura em cima do que já existe. O corpo de resposta precisa de um API Resource novo (`TimelinePostResource`) já que a UI mobile não usa view Blade; padrão Eloquent API Resource, conforme `laravel/core` guideline deste repo.

**Fora do v1, decisão explícita**: reações (`withCount('reactions')` aparece no `Feed.php`) — o PRD não menciona reagir como escopo v1 do app; incluir a contagem na resposta é grátis, mas o endpoint de reagir fica pra depois se a issue não abrir esse escopo.

---

## Feature 2 — Eventos

Domínio: `He4rt\Events\*`.

| Método | Rota                                  | Action reaproveitada                                                                                                                      |
| ------ | ------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| GET    | `/api/mobile/events`                  | `Event::query()->viewableByParticipant()` (scope já existe, usado no `EventDetail.php`)                                                   |
| GET    | `/api/mobile/events/{event}`          | idem + `enrollmentPolicy`, computa os mesmos booleans do `EventDetail.php` (`canApply`, `canConfirmPresence`, `isEventFull`) num Resource |
| POST   | `/api/mobile/events/{event}/enroll`   | `EnrollUserAction` + `EnrollUserDTO` (RSVP e Application, mesma Action cobre os dois)                                                     |
| GET    | `/api/mobile/events/{event}/qr`       | Serializa `$enrollment->qrToken->token` (dado cru — o app renderiza o QR nativamente, não precisa do SVG que o Livewire gera)             |
| POST   | `/api/mobile/events/{event}/check-in` | `NumericCodeCheckInAction` (ver gap abaixo pro método QR)                                                                                 |

### Gap de domínio encontrado: não existe self-check-in por QR

O PRD pede "check-in por QR code (via Scanner) com o código numérico como alternativa" — ou seja, os dois métodos alimentando a **mesma ação de self-check-in**, só mudando a forma de entrada (câmera vs teclado). Investigando o domínio (`app-modules/events/src/CheckIn/`):

- `NumericCodeCheckInAction` — **self-service** (`TriggeredBy::User`, ator = o próprio dono da enrollment), valida contra `CheckInCode` (código compartilhado por evento/dia, com expiração e limite de usos).
- `QrCheckInAction` — **existe, mas é outra coisa**: é o staff escaneando o QR pessoal do participante (`TriggeredBy::Admin` fixo no código, valida contra `QrToken`, token 1:1 com a enrollment). É provavelmente o que alimenta um scanner no `panel-admin`, não o app do participante.

Não existe hoje uma ação self-service equivalente à `NumericCodeCheckInAction` que aceite entrada por QR. Duas formas de fechar esse gap (decisão a bater com o time antes de codar a Feature 2):

1. **QR só codifica o mesmo código compartilhado** (`CheckInCode.code`) — o venue projeta o código como texto E como QR na mesma tela; o Scanner do app só preenche o campo automaticamente. Nesse caso não precisa de Action nova: o endpoint aceita o valor decodificado como se fosse digitado, chama `NumericCodeCheckInAction` do mesmo jeito, e o `method` gravado no `CheckIn` continua sendo `NumericCode` (ou passa a receber o `method` como parâmetro pra registrar `QrCode` de verdade — mudança pequena na Action/DTO).
2. **QR é uma entidade própria** (novo token, não o `CheckInCode` compartilhado) — precisa de uma `QrCodeSelfCheckInAction` nova, espelhando a `NumericCodeCheckInAction` mas validando um token diferente. Mais trabalho, mais uma tabela ou reuso do `QrToken` com uma regra nova de quem pode consumir (hoje `QrToken` é pensado pra ser consumido por um `Admin`, não pelo próprio dono).

Recomendação: opção 1 é bem mais barata e resolve o que o PRD pede ("QR como alternativa ao numérico", não "QR como terceiro sistema"). Fica registrado aqui pra não repetir a investigação — mas é uma decisão de produto, não só técnica, então deveria ser confirmada na issue antes da Feature 2 entrar em código.

---

## Feature 3 — Perfil

Domínio: `He4rt\Profile\*`.

| Método | Rota                  | Action/Model reaproveitado                                      |
| ------ | --------------------- | --------------------------------------------------------------- |
| GET    | `/api/mobile/profile` | `Profile::ensureExists(auth()->id())` — mesmo padrão usado hoje |

v1 é só leitura (o PRD explicitamente escopa "visualização do próprio perfil" — editar fica de fora). Um `ProfileResource` serializa os campos públicos do model (nickname, headline, seniority, social_links, etc.) — sem gap de domínio, `UpsertProfile`/`SyncProfileSkills` já existem se a edição entrar em escopo depois.

---

## Fora de escopo (herdado do PRD, sem mudança)

- Reaproveitar as classes Livewire do `panel-app` como UI — arquitetura do NativePHP Mobile não permite.
- Funcionalidade de domínio nova além do que já existe no `panel-app` (reações no feed, edição de perfil, etc.) — a não ser que a issue #531 seja atualizada pra abrir esse escopo.
- Notificações push — mesma pendência do PRD original, sem decisão ainda.
- Publicação nas lojas — fora do escopo desta API.

---

## Riscos e perguntas em aberto

1. **Gap do QR self-check-in (Feature 2)** — decisão de produto, não só técnica; ver seção acima.
2. **Deep link scheme** (`NATIVEPHP_DEEPLINK_SCHEME`) — precisa ser registrado no `he4rt-app` e o valor comunicado pra esta API configurar o redirect do passo 3 do fluxo OAuth.
3. **Rate limiting da API mobile** — os endpoints de escrita (postar, check-in) precisam de throttle próprio, análogo ao `RateLimiter` que `NumericCodeCheckIn.php` já usa no Livewire; replicar o mesmo limite na Action ou no middleware da rota.
4. **Blacklist do refresh token** — `php-open-source-saver/jwt-auth` precisa de um storage pra blacklist (cache/DB); confirmar qual driver de cache este projeto já usa em produção antes de habilitar `JWT_BLACKLIST_ENABLED`.
5. **Claims futuras** — o PRD não pede isso agora; só está sendo deixado como gancho arquitetural (`getJWTCustomClaims()`). Não implementar claims de role/tenant sem um caso de uso concreto puxando.

---

## Ordem de implementação sugerida

1. **Feature 0 (Auth)** — bloqueia todo o resto; sem token, nenhuma outra feature autentica.
2. **Feature 3 (Perfil)** — menor superfície, bom smoke test do guard `api` novo de ponta a ponta antes de features com escrita.
3. **Feature 1 (Timeline)** — leitura + escrita simples (post/reply), sem gap de domínio.
4. **Feature 2 (Eventos)** — a mais complexa (enrollment + dois métodos de check-in); decisão do gap de QR (seção acima) deveria estar fechada antes de começar.

Cada feature vira sua própria branch/PR neste repo (`heartdevs.com`), seguindo a convenção `feature/<slug>` ou `story/531-<slug>` já documentada em `.ai/rules`. O client (`he4rt-app`) consome cada endpoint conforme ele fica pronto — não precisa esperar a API inteira pra começar a integrar a Feature 0/3.

---

## PS: achados da revisão de segurança (CodeRabbit)

A implementação da Feature 0 passou por revisão automática de segurança antes do merge, que achou dois pontos reais no fluxo de troca de código:

- **Race condition na troca do código** (corrigido): `Cache::pull()` do Laravel é `get()` + `forget()` como duas chamadas separadas, não atômicas — duas requisições concorrentes com o mesmo código podiam ler o valor antes de qualquer uma apagar, mintando dois tokens da mesma autorização. `ExchangeMobileCodeAction` passou a usar `Cache::lock()` pra serializar leitura+remoção por código.
- **Deep link com custom scheme pode ser sequestrado** (débito técnico conhecido, não fechado nesta PR): `he4rtapp://oauth/callback?code=...` usa um esquema de URL customizado, que não é exclusivo do app — outro app instalado no mesmo aparelho pode registrar o mesmo scheme e interceptar o código antes do app legítimo (TTL curto e uso único não impedem isso, já que o atacante só precisa ser o primeiro a usar). A correção correta é **PKCE** (o app mobile gera um `code_verifier` local, manda só o hash `code_challenge` no redirect, e precisa do verifier original pra completar a troca depois) ou um HTTPS App Link verificado em vez do scheme customizado. Não implementado agora porque depende do `he4rt-app` (ainda só um scaffold) também mudar o lado dele — fica registrado aqui pra não ser esquecido antes do app mobile começar a consumir esse fluxo de verdade.
