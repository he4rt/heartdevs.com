---
title: Feature flags
order: 5
---

# Feature flags

Uma **feature flag** liga uma feature para algumas pessoas antes de todo mundo, e desliga de novo sem
deploy. Abra em **System → Feature flags**.

Os desenvolvedores declaram as flags no código. Esta tela só liga e desliga cada uma. O He4rt traz
uma flag, **Keep every log**, que guarda todo evento de um user em **System → Logs**.

## Como uma flag funciona

Toda flag tem um **escopo** e um **gate**.

| Escopo | Quem recebe a feature                                 |
| ------ | ----------------------------------------------------- |
| Global | Todo mundo, assim que o gate abre.                    |
| Team   | Os teams em que você ligou a flag, com o gate aberto. |
| User   | Os users em que você ligou a flag, com o gate aberto. |

O **gate** é a chave geral da flag. Com ele fechado, ninguém recebe a feature, nem os teams e users em
que você ligou a flag. Use o gate para parar uma feature que quebrou. As listas ficam, então quando
você abre o gate de novo, os mesmos teams e users voltam a receber a feature.

Uma flag nova começa desligada em todo lugar, com o gate fechado.

Uma flag nunca dá acesso sozinha. A pessoa também precisa das permissões da feature.

## Ligar uma flag

1. Abra o gate da flag na coluna **Gate**.
2. Para uma flag de team ou de user, clique na seta no fim da linha.
3. Busque o team ou o user e ligue o switch dele.

Use o filtro **Enabled** para ver só os teams ou users que têm a flag.

Quando o gate está fechado e algum team ou user tem a flag, a linha mostra um aviso: eles só recebem a
feature quando você abrir o gate.

Toda mudança vai para o **Activity log**.

## Permissões

As permissões ficam no grupo **Support** de **Roles & Permissions**, em **Stored Feature Flag**.

| Permissão | O que libera                                                            |
| --------- | ----------------------------------------------------------------------- |
| View      | Ver as flags e os teams e users que têm cada uma.                       |
| Update    | Abrir e fechar os gates, e ligar ou desligar uma flag para um registro. |

Por padrão, só o papel **Super Admin** tem as duas.
