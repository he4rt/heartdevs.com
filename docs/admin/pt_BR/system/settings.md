---
title: Configurações
order: 4
---

# Configurações

**Settings** muda como o He4rt funciona para todos os usuários. Abra em **System → Settings**.
Cada aba é um grupo de configurações.

## Permissões

Cada aba tem duas permissões. Dê as permissões a um papel em **Roles & Permissions**, no grupo
**Settings**.

| Permissão | O que libera                                          |
| --------- | ----------------------------------------------------- |
| View      | Ver a aba. Sem ela, a aba some.                       |
| Update    | Mudar e salvar a aba. Sem ela, a aba fica só leitura. |

Por padrão, só o papel **Super Admin** tem as duas.

## Autenticação

Esta aba liga e desliga os métodos de login.

| Configuração              | Ligada                                                                                       | Desligada                                                                                                       |
| ------------------------- | -------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| Two factor authentication | Todo usuário precisa de um app autenticador. Quem não tem configura antes da próxima página. | Ninguém precisa do código. A configuração dos usuários fica guardada e volta a valer quando você ligar de novo. |
| Passkeys                  | Os usuários entram com uma passkey e cadastram passkeys na página de segurança.              | O botão de passkey e a seção de passkeys somem. As passkeys salvas ficam.                                       |

> Quando você liga o two factor, você também precisa configurar, antes da próxima página do admin.

Uma instalação nova começa com os dois métodos desligados fora de produção, e ligados em produção.
Toda mudança vai para o **Activity log**.

### Login social

A seção **Social login** tem um switch por provider: GitHub, Google, Discord e Twitch. Um provider
ligado mostra o botão "Continue with" na página de login. Usuários novos se cadastram com ele
enquanto o registro está aberto.

Um provider precisa antes de um OAuth app e das credenciais no `.env`. Até lá, o switch fica
travado e mostra as chaves que faltam, por exemplo `GITHUB_CLIENT_ID` e `GITHUB_CLIENT_SECRET`. No
OAuth app, a callback URL é `https://<seu app>/auth/<provider>/callback`, por exemplo
`https://app.example.com/auth/github/callback`. Cada provider mostra a callback URL dele, com um
botão de copiar ao lado.

| Configuração | Ligada                                                       | Desligada                                                                            |
| ------------ | ------------------------------------------------------------ | ------------------------------------------------------------------------------------ |
| Um provider  | Os usuários entram, se cadastram e conectam a conta com ele. | O botão some. As contas conectadas ficam e voltam a valer quando você ligar de novo. |

> Desligar um provider não tranca ninguém do lado de fora. Quem não tem senha ainda usa o **Forgot
> password** da página de login.
