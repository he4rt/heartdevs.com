---
title: Convenções de Código
date: 2026-06-14
---

# Convenções de Código

Estas convenções valem para todo o monorepo. Antes de abrir um PR, garanta que o
seu código passa nas mesmas verificações que a CI roda — assim a revisão foca no
que importa, não em formatação.

- [PHP](#php)
- [Models e migrations](#models-e-migrations)
- [Arquitetura modular](#arquitetura-modular)
- [Camada de apresentação](#camada-de-apresentacao)
- [Ferramentas de qualidade](#ferramentas-de-qualidade)
- [Testes](#testes)

<a name="php"></a>

## PHP

- `declare(strict_types=1);` no topo de **todo** arquivo PHP.
- Classes concretas são `final` (Models inclusive).
- Type hints e tipos de retorno explícitos em todos os métodos:
  `function isAccessible(User $user, ?string $path = null): bool`.
- Use property promotion no construtor:
  `public function __construct(public GitHub $github) {}`.
- Sempre use chaves em estruturas de controle, mesmo em corpo de uma linha.
- Prefira blocos PHPDoc a comentários inline; reserve comentários para lógica
  realmente complexa.
- Nomes descritivos: `isRegisteredForDiscounts`, não `discount()`.
- Chaves de Enum em `TitleCase`: `FavoritePerson`, `Monthly`.

```php
<?php

declare(strict_types=1);

namespace He4rt\Economy\Actions;

final readonly class CreditWalletAction
{
    public function __construct(private WalletRepositoryContract $wallets) {}

    public function execute(CreditWalletData $data): void
    {
        // ...
    }
}
```

<a name="models-e-migrations"></a>

## Models e migrations

- Casts são definidos pelo método `casts()`, não pela propriedade `$casts`.
- Relations têm `@return` com generics no PHPDoc.
- **Regra obrigatória:** ao adicionar, remover, renomear ou mudar o tipo de uma
  coluna, atualize o bloco `@property` do Model no **mesmo commit**.

| Tipo da coluna           | Tipo no PHPDoc               |
| ------------------------ | ---------------------------- |
| `uuid`, `string`, `text` | `string`                     |
| `integer`, `bigInteger`  | `int`                        |
| `boolean`                | `bool`                       |
| `timestamp`, `datetime`  | `Carbon\|null`               |
| `json`, `jsonb`          | `array<string, mixed>\|null` |
| `enum` (backed)          | `EnumClass`                  |

Adicione `|null` quando a coluna for nullable.

<a name="arquitetura-modular"></a>

## Arquitetura modular

Cada feature vive em `app-modules/{kebab-case}/` com namespace
`He4rt\{PascalCase}\` (exceção: o módulo `he4rt` usa `He4rt\Core`). Crie módulos e
componentes com os comandos do `internachi/modular` em vez de mover arquivos na
mão.

Regras de dependência entre camadas:

- Módulos de **domínio** (`identity`, `moderation`, `economy`…) nunca importam de
  apresentação ou integração.
- Módulos de **integração** (`integration-*`, `bot-discord`) podem depender de
  domínio.
- Módulos de **apresentação** (`panel-*`, `portal`) importam de domínio e
  integração — nunca o contrário.

O ServiceProvider fica sempre em `src/{ModuleName}ServiceProvider.php`, nunca
dentro de `Providers/`.

<a name="camada-de-apresentacao"></a>

## Camada de apresentação

- Lógica de domínio (Actions, Models, DTOs, regras de negócio) mora em módulos de
  domínio, **nunca** em módulos `panel-*` ou `portal`.
- Em views, prefira componentes Flux (`<flux:*>`) a HTML nativo quando houver
  equivalente.
- Componentes Livewire devem ter um único elemento raiz.
- Cuide do dark mode: evite `bg-white` hardcoded; use as classes que respeitam o
  tema.
- Reutilize os componentes do design system (`x-he4rt::*`) antes de escrever um
  novo.

<a name="ferramentas-de-qualidade"></a>

## Ferramentas de qualidade

Três ferramentas guardam a qualidade do código. Rode-as antes de finalizar:

```bash
# Formatação (Pint) — sempre rode depois de editar PHP
vendor/bin/pint --dirty

# Análise estática (PHPStan / Larastan)
vendor/bin/phpstan analyse

# Análise estática de um módulo específico (mais rápido)
vendor/bin/phpstan analyse app-modules/docs

# Refactors automáticos (Rector) — em modo dry-run
vendor/bin/rector --dry-run
```

Para rodar tudo de uma vez, como a CI faz:

```bash
composer test
```

Esse alvo encadeia `test:rector`, `test:pint`, `test:phpstan` e a suíte de
testes. Um PR só está pronto quando `composer test` passa limpo.

> Ao adicionar entradas em `ignoreErrors` num `phpstan.neon`, use o estilo de
> bloco indentado (um `-` sozinho, chaves abaixo) e sempre escope por `path`.
> Prefira corrigir a causa raiz a ignorar.

<a name="testes"></a>

## Testes

- Toda mudança precisa de teste: escreva um novo ou atualize um existente e rode
  os testes afetados.
- A maioria dos testes é de feature; use unit para Actions e Entities isoladas.
- Use factories (com seus states) para montar dados de teste, e
  `->recycle($tenant)` para propagar o tenant em cadeias de factory.
- Não delete testes sem aprovação.

```bash
php artisan test --compact --filter=CreditWallet
```

Veja [Rodando o Projeto](/docs/{{version}}/rodando-o-projeto) para o setup do
ambiente e [Seu Primeiro Pull Request](/docs/{{version}}/primeiro-pull-request)
para o fluxo de contribuição.
