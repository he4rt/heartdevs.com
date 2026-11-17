---
title: Autenticação
icon: heroicon-o-lock-closed
order: 4
---

# Autenticação

Como a autenticação e o controle de acesso dos usuários funcionam no He4rt.

## Fazendo Login

Acesse o painel administrativo em `/admin/login`. Insira seu **e-mail** e **senha** para fazer login.

> **Nota:** Em ambientes de não-produção (desenvolvimento, homologação), o formulário de login pode vir pré-preenchido com credenciais de demonstração por conveniência.

## Login Social

Os usuários do app entram com GitHub, Google, Discord ou Twitch, nos providers ligados em
**System → Settings → Authentication**. O login do admin continua só com e-mail e senha.

- Uma conta do provider vinculada a um usuário entra nesse usuário.
- Uma conta do provider com o e-mail de um usuário que já existe se vincula a ele, mas só quando o
  provider verificou o e-mail. Senão, a página de login pede que a pessoa entre primeiro e conecte o
  provider nas configurações dela.
- Um e-mail novo cria um usuário sem senha enquanto o registro está aberto. Esse usuário define uma
  senha depois, ou usa o **Forgot password**.
- Um usuário com autenticação em dois fatores ainda digita o código depois do provider.

## Quem Pode Acessar o Painel

Apenas usuários com o cargo de **Super Administrador** podem acessar o painel administrativo. Se você não consegue fazer login, entre em contato com o administrador do sistema para verificar a atribuição do seu cargo.

## Sessões

Sua sessão de login é gerenciada automaticamente. Você pode visualizar e gerenciar suas sessões ativas do navegador na página **Meu Perfil**, na seção **Sessões do Navegador**. Se você vir uma sessão que não reconhece, pode revogá-la imediatamente.

## Segurança de Senha

As senhas devem atender aos requisitos mínimos de segurança. Você pode atualizar sua senha a qualquer momento na página do **Perfil**. Será necessário confirmar sua senha atual antes de definir uma nova.

## Personificação

Administradores podem fazer login temporariamente como outro usuário para solucionar problemas ou verificar o que esse usuário visualiza. Isso é feito a partir da lista de **Usuários**, clicando na ação **Personificar** em qualquer linha de usuário.

Durante a personificação:

- Um banner aparece no topo da página indicando que você está personificando alguém
- Clique no banner para **parar de personificar** e retornar à sua própria sessão
- Todas as ações realizadas são registradas na conta do usuário personificado
