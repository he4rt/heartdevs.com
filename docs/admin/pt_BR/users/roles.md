---
title: Cargos e Permissões
icon: heroicon-o-shield-check
order: 2
---

# Cargos e Permissões

A plataforma utiliza **controle de acesso baseado em cargos (RBAC)**. Cada usuário pode receber um ou mais cargos, e cada cargo concede um conjunto específico de permissões. Apenas **Super Administradores** podem gerenciar cargos.

Navegue até **Gestão de Usuários > Cargos** para visualizar, criar ou editar cargos.

## Lista de Cargos

A tabela de cargos exibe:

- **Nome** — O nome do cargo (pesquisável)
- **Guard** — O guard de autenticação ao qual o cargo se aplica (geralmente `web`)
- **Permissões** — A quantidade de permissões atribuídas a este cargo
- **Última Atualização** — Quando o cargo foi modificado pela última vez

## Criando um Cargo

Clique em **Novo Cargo** e preencha:

- **Nome** — Um nome descritivo para o cargo (ex.: "Editor", "Visualizador"). Deve ser único.
- **Guard Name** — O guard de autenticação (padrão `web`; geralmente não é necessário alterar)

### Atribuindo Permissões

Mude para a aba **Permissões** para ver todas as permissões disponíveis organizadas por categoria. Use as caixas de seleção para escolher quais permissões este cargo deve conceder. Você pode usar a opção **alternar todos** para selecionar ou desmarcar grupos inteiros rapidamente.

Clique em **Criar** para salvar o cargo.

## Editando um Cargo

Clique em **Editar** em qualquer linha de cargo para modificar seu nome ou permissões. As alterações entram em vigor imediatamente para todos os usuários que possuem este cargo.

## Excluindo um Cargo

Use a ação **Excluir** em uma linha de cargo ou na página de edição. Será solicitada confirmação antes da remoção.

> **Atenção:** Excluir um cargo revogará suas permissões de todos os usuários que o possuíam.

## Como as Permissões Funcionam

As permissões controlam o acesso a recursos e ações específicas em toda a plataforma. Os tipos comuns de permissão incluem:

- **Visualizar** — Pode ver registros em um recurso
- **Criar** — Pode criar novos registros
- **Atualizar** — Pode editar registros existentes
- **Excluir** — Pode remover registros

As permissões efetivas de um usuário são o **conjunto combinado** de todas as permissões de todos os seus cargos atribuídos. Se qualquer cargo concede uma permissão, o usuário a possui.

Uma feature flag (**System → Feature flags**) nunca substitui uma permissão. Uma feature atrás de uma flag precisa da flag ligada e da permissão.

## Atribuindo Cargos a Usuários

Os cargos são atribuídos a partir do **formulário de edição do usuário**. Navegue até a página de edição de um usuário para adicionar ou remover seus cargos.
