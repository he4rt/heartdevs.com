# Sistema de Documentação

Este diretório contém a documentação do He4rt Bot API, organizada por versões.

## Estrutura

```
docs/
├── index.md          # Página inicial da documentação
├── 2.x/             # Documentação da versão 2.x
│   ├── intro.md
│   └── introduction.md
└── 3.x/             # Documentação da versão 3.x
    ├── installation.md
    ├── intro.md
    └── introduction.md
```

## Como Adicionar Nova Documentação

### 1. Front Matter

Cada arquivo markdown deve incluir metadados YAML no início (front matter):

```yaml
---
title: Título da Página
description: Descrição breve do conteúdo
order: 1 # Ordem de exibição (menor = primeiro)
group: Primeiros Passos # Grupo na sidebar (opcional)
icon: document-text # Ícone Flux (opcional)
---
```

### 2. Campos Disponíveis

- **title** (obrigatório): Título da página exibido na navegação
- **description** (opcional): Descrição da página
- **order** (opcional, padrão: 999): Ordem de exibição na navegação
- **group** (opcional, padrão: "Geral"): Nome do grupo na sidebar esquerda
- **icon** (opcional, padrão: "document-text"): Ícone do Flux UI

### 3. Ícones Disponíveis

Use qualquer ícone do [Heroicons](https://heroicons.com/). Exemplos:

- `document-text`
- `rocket-launch`
- `cloud-arrow-down`
- `shield-check`
- `user`
- `star`
- `trophy`
- `chat-bubble-left`
- `megaphone`
- `link`

### 4. Exemplo Completo

```markdown
---
title: Instalação
description: Guia completo de instalação do He4rt Bot API
order: 1
group: Primeiros Passos
icon: cloud-arrow-down
---

# Instalação

Siga estes passos para instalar...

## Pré-requisitos

- PHP 8.3+
- Composer
- PostgreSQL

## Passos

1. Clone o repositório
2. Instale as dependências
3. Configure o ambiente
```

## Sidebar Esquerda

A sidebar esquerda é gerada automaticamente:

- Lê todos os arquivos `.md` do diretório da versão
- Agrupa páginas pelo campo `group`
- Ordena dentro de cada grupo pelo campo `order`
- Exibe com o ícone especificado em `icon`

## Sidebar Direita (Table of Contents)

A sidebar direita é gerada automaticamente:

- Extrai todos os headings `##` e `###` do markdown
- Gera links âncora para navegação rápida
- Indenta headings de nível 3 (###)
- Só aparece se houver headings no conteúdo

## Adicionando Nova Versão

1. Crie um novo diretório: `docs/4.x/`
2. Adicione arquivos markdown com front matter
3. A versão aparecerá automaticamente na navegação

## Tecnologias Utilizadas

- **spatie/laravel-markdown**: Renderização de markdown com syntax highlighting
- **spatie/yaml-front-matter**: Parse do front matter YAML
- **spatie/shiki-php**: Syntax highlighting de código
- **Livewire Flux**: Componentes UI e ícones
