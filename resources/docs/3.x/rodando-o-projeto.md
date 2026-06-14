---
title: Rodando o Projeto
date: 2026-06-14
---

# Rodando o Projeto

Este guia leva você do clone até a aplicação rodando localmente. O projeto é um
monorepo Laravel modular (`internachi/modular`): cada feature mora em
`app-modules/`, com namespace `He4rt\*`.

- [Pré-requisitos](#pre-requisitos)
- [Setup inicial](#setup-inicial)
- [Subindo a aplicação](#subindo-a-aplicacao)
- [Rodando os testes](#rodando-os-testes)
- [Discord bot](#discord-bot)
- [Problemas comuns](#problemas-comuns)

<a name="pre-requisitos"></a>

## Pré-requisitos

Garanta que as versões abaixo estão instaladas antes de começar. Elas seguem o
que o `composer.json` declara:

- **PHP 8.5** com as extensões usuais do Laravel (`mbstring`, `pdo`, `intl`, `gd`).
- **Composer 2**.
- **Node.js** (LTS atual) e **npm** — o frontend é empacotado com Vite 7.
- **PostgreSQL** para o ambiente de desenvolvimento padrão (o `.env.example`
  vem com `DB_CONNECTION=sqlite` para facilitar o primeiro boot, mas a aplicação
  roda em PostgreSQL em produção).

<a name="setup-inicial"></a>

## Setup inicial

Clone o repositório e rode o script de setup. Ele instala dependências PHP e
Node, copia o `.env`, gera a chave da aplicação, cria o link de storage e roda os
helpers de IDE de uma só vez:

```bash
git clone git@github.com:he4rt/he4rt-bot-api.git
cd he4rt-bot-api

composer setup
```

O `composer setup` é equivalente a executar, em ordem:

```bash
composer install
npm install
cp .env.example .env          # se ainda não existir
php artisan key:generate
php artisan storage:link
```

Depois rode as migrations (e seeders, se quiser dados de exemplo):

```bash
php artisan migrate
php artisan migrate --seed     # opcional, popula dados de desenvolvimento
```

<a name="subindo-a-aplicacao"></a>

## Subindo a aplicação

Use o script `dev` do Composer. Ele sobe quatro processos em paralelo — servidor
HTTP, worker de fila, logs ao vivo (Pail) e o Vite — para que você não precise
abrir vários terminais:

```bash
composer run dev
```

| Processo | Comando por baixo          | Para quê                          |
| -------- | -------------------------- | --------------------------------- |
| server   | `php artisan serve`        | Servidor HTTP em `localhost:8000` |
| queue    | `php artisan queue:listen` | Processa jobs e eventos           |
| logs     | `php artisan pail`         | Stream de logs no terminal        |
| vite     | `npm run dev`              | Hot reload de CSS/JS              |

Se você só precisa do frontend recompilando (por exemplo, ajustando Blade/CSS),
rode o Vite isolado:

```bash
npm run dev      # desenvolvimento, com hot reload
npm run build    # build de produção (gera o manifesto)
```

> Se uma mudança de frontend não aparecer na tela, normalmente é o manifesto do
> Vite desatualizado: rode `npm run dev` ou `npm run build`.

A aplicação expõe múltiplos painéis Filament além do portal público:

- `/` — portal público (módulo `landing`/`portal`).
- `/admin` — painel administrativo.
- `/app` — painel do usuário autenticado (tenant-scoped).
- `/docs` — este portal de documentação.

<a name="rodando-os-testes"></a>

## Rodando os testes

A suíte usa Pest 4. Para rodar tudo de forma compacta:

```bash
php artisan test --compact
```

Filtre por um teste específico durante o desenvolvimento — é mais rápido:

```bash
php artisan test --compact --filter=NomeDoTeste
```

Você também pode rodar apenas os testes de um módulo apontando para o diretório:

```bash
php artisan test --compact app-modules/docs
```

O alvo `composer test` roda a verificação completa (Rector, Pint e PHPStan)
além da suíte — é o que a CI executa. Veja
[Convenções de Código](/docs/{{version}}/convencoes-de-codigo) para os detalhes
de cada ferramenta.

<a name="discord-bot"></a>

## Discord bot

O bot do Discord vive em `app-modules/bot-discord/` e usa o framework Laracord.
Para subi-lo localmente:

```bash
php artisan bot:boot
```

Você precisa de um token de bot válido configurado no `.env` e dos IDs de canal/
cargo em `config/bot-discord.php`. Se for trabalhar só na aplicação web, o bot é
opcional.

<a name="problemas-comuns"></a>

## Problemas comuns

- **`Unable to locate file in Vite manifest`** — rode `npm run dev` ou
  `npm run build` para regenerar o manifesto.
- **Página em branco / assets sem estilo** — o Vite não está rodando; suba
  `composer run dev`.
- **Erros de conexão com o banco** — confira `DB_*` no `.env` e se o PostgreSQL
  está de pé.
- **`Class not found` num módulo novo** — rode `composer dump-autoload`; o
  `internachi/modular` registra os namespaces via autoload PSR-4.
