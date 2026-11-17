# He4rt Hub

O He4rt Hub é onde a trajetória dos membros da He4rt Developers fica registrada: o que você aprendeu, praticou, entregou e teve **validado por gente da comunidade**.

O Hub não é curso (isso é a Basement) e não é mural de vagas (isso é o Job Finder). O que ele produz é **reputação verificável**. A ideia é você sair daqui mais preparado do que entrou, e com prova disso.

> **Status:** branch `5.x`, fase de Fundação. Este README é a base e vai crescer junto com o produto.

Quem o Hub atende, o que nunca muda e o vocabulário oficial ficam no [`PRODUCT.md`](PRODUCT.md). Se você vai mexer em uma funcionalidade, leia ele antes. Sério, ele evita metade das discussões de PR.

---

## 1. Roadmap

O produto sai em quatro etapas:

1. **Fundação:** identidade, perfil público, trajetória, feed como diário de aprendizado, preparação para o squad e medição de uso.
2. **Squads e validação.**
3. **Vagas exclusivas.**
4. **Reconhecimento:** XP e conquistas.

Hoje o código tem o começo da Fundação: contas, login social, papéis e permissões, e os dois painéis.

---

## 2. Stack

- **Backend:** PHP 8.5, Laravel 13, PostgreSQL e Redis
- **Frontend:** Inertia v3 com React 19 e TypeScript, Tailwind CSS v4 e shadcn (Base UI)
- **Arquitetura:** monolito modular com [`internachi/modular`](https://github.com/InterNACHI/modular)
- **Qualidade:** Pest, Pint, PHPStan (Larastan) e Rector
- **Pacotes JS:** Bun

---

## 3. Rodando local

### Com lerd (o que o time usa)

O `.lerd.yaml` já declara a versão do PHP, os serviços (Postgres, Redis, Mailpit, RustFS) e os workers. Então é só:

```bash
lerd link
lerd setup
```

### Com Docker e Make

```bash
make env-up         # sobe Postgres, Redis e Mailpit
make setup          # dependências, .env e APP_KEY
make migrate-fresh  # migrations e seed
make dev            # servidor, fila, logs e Vite juntos
```

### Entrando no admin

Em ambiente local, o seed cria um super admin:

- **URL:** `/admin` (muda com `APP_ADMIN_PATH`)
- **E-mail:** `admin@admin.com`
- **Senha:** `password`

### Testes

O `make setup` já cria o `.env.testing` a partir do `.env.testing.example`. Depois é só:

```bash
make setup-test-db  # cria e migra o banco de teste
make test
```

---

## 4. Estrutura

O código de base fica em `app/` (Laravel padrão). Cada domínio e cada painel é um módulo em `app-modules/`, com namespace `He4rt\{Modulo}`.

| Módulo        | Tipo         | O que faz                                        |
| ------------- | ------------ | ------------------------------------------------ |
| `identity`    | Domínio      | Contas, login social, times, papéis e permissões |
| `panel-admin` | Apresentação | Painel de administração em `/admin`              |
| `panel-app`   | Apresentação | Painel de todo membro, na raiz                   |

A regra que segura tudo: **domínio nunca importa painel**. Regra de negócio mora no módulo de domínio, e o painel só cuida da tela. Os testes de arquitetura em `tests/Arch` quebram se alguém furar isso.

Cada funcionalidade de painel é uma fatia: `src/Features/<Grupo>/<Feature>/` no PHP, espelhada em `resources/js/features/<grupo>/<feature>/` no React. Os componentes compartilhados ficam em `resources/js` (alias `@`).

---

## 5. Comandos do dia a dia

| Comando             | Atalho | O que faz                                   |
| :------------------ | :----: | :------------------------------------------ |
| `make test`         |  `t`   | Roda a suíte inteira                        |
| `make test-feature` |        | Só os testes de feature                     |
| `make test-arch`    |        | Só os testes de arquitetura                 |
| `make format`       |  `f`   | Rector e Pint corrigindo o código           |
| `make check`        |  `c`   | Rector, Pint e PHPStan em modo de checagem  |
| `make phpstan`      |  `p`   | Análise estática                            |
| `make route-list`   |  `rl`  | Lista as rotas da aplicação                 |
| `bun run types`     |        | Gera os tipos TS das classes `Data` e enums |
| `bun run routes`    |        | Gera os helpers de rota do Wayfinder        |
| `bun run typecheck` |        | `tsc --noEmit`                              |

`make help` lista o resto.

---

## 6. Antes de abrir um PR

Rode o que a sua mudança toca:

```bash
vendor/bin/rector process
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=1G
bun run typecheck && bun run build
php artisan test --compact
```

Algumas convenções:

- **Branch:** `feature/<slug>`, `bugfix/<slug>`, `chore/<slug>` ou `story/<issue>-<slug>`, saindo da `5.x`.
- **Commit e título de PR:** conventional commits com o módulo no escopo, tipo `feat(identity): vincular conta do GitHub`.
- **Vocabulário:** a pessoa é **membro**, o time é **squad**, quem conduz é o **capitão**, a avaliação é uma **validação**. Nada de "talento", "job board" ou "certificado". A lista completa está no `PRODUCT.md`.

---

## 7. Onde está cada coisa

- [`PRODUCT.md`](PRODUCT.md): o produto, para quem é e o que precisa continuar verdadeiro
- [`PRODUCT_METRICS.md`](PRODUCT_METRICS.md): o que a gente mede e as metas de partida
- [`DESIGN.md`](DESIGN.md): o sistema visual dos painéis
- [`docs/admin/`](docs/admin): guias do admin em `en` e `pt_BR`, que aparecem em `/admin/docs`
- `app-modules/<modulo>/docs/adr/`: decisões de arquitetura de cada módulo
- [`.ai/rules/`](.ai/rules) e [`.ai/guidelines/`](.ai/guidelines): as regras de código de cada camada

---

## Licença

[MIT](LICENSE).

Da comunidade para a comunidade.
