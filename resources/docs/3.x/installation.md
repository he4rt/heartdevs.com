---
title: Instalação
order: 1
---

# Installation

- [Versioning Scheme](#versioning-scheme)
- [Requirements](#requirements)
- [Local Setup](#local-setup)
- [Running the App](#running-the-app)

<a name="versioning-scheme"></a>

## Versioning Scheme

A documentação do portal é versionada por linha de release (por exemplo, `3.x`).
Os arquivos vivem em `resources/docs/{version}/` e os links internos usam o
placeholder `{{version}}` para apontar sempre para a versão corrente — assim a
mesma página funciona em qualquer linha de release.

<a name="requirements"></a>

## Requirements

- **PHP 8.5** com as extensões usuais do Laravel.
- **Composer 2** e **Node.js** (LTS) com **npm**.
- **PostgreSQL** para o ambiente padrão.

<a name="local-setup"></a>

## Local Setup

Clone o repositório e rode o script de setup, que instala dependências, copia o
`.env`, gera a chave da aplicação e cria o link de storage:

```bash
git clone git@github.com:he4rt/he4rt-bot-api.git
cd he4rt-bot-api

composer setup
php artisan migrate
```

<a name="running-the-app"></a>

## Running the App

Suba todos os processos de desenvolvimento (servidor, fila, logs e Vite) com um
único comando:

```bash
composer run dev
```

O passo a passo completo, com troubleshooting e o setup do bot do Discord, está
em [Rodando o Projeto](/docs/{{version}}/rodando-o-projeto).
