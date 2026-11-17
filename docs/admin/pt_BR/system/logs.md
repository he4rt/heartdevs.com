---
title: Logs
order: 6
---

# Logs

**Logs** mostra o que o sistema fez: um evento para cada request, job, comando e tarefa agendada. Abra em
**System → Logs**. O **Activity log** mostra quem mudou qual registro. Logs mostra como o trabalho correu.

## O que um evento tem

Todo evento tem a rota ou o job, o user, o team, quanto tempo levou, quantas queries rodou, as feature flags que leu
e o resultado:

| Resultado    | O que quer dizer                                                                    |
| ------------ | ----------------------------------------------------------------------------------- |
| Success      | O trabalho terminou sem erro.                                                       |
| Client error | O request foi recusado com um status 4xx: não encontrado, validação, sem permissão. |
| Error        | O trabalho falhou: um status 5xx, uma exceção ou um comando que não terminou com 0. |

Quando o trabalho falhou, o evento também tem a exceção e as linhas de código onde ela aconteceu.

Um evento nunca tem senha, token, e-mail, IP ou o corpo de um request. Uma rota aparece pelo template
(`reset-password/{token}`), nunca com os valores da URL.

## Por que faltam alguns requests

O app não guarda todo evento. Ele sempre guarda:

- todo evento que falhou,
- todo evento que levou mais de um segundo,
- todo comando, tarefa agendada e chamada de tool MCP,
- todo evento de um team ou user que tem uma feature flag em rollout,
- todo evento de um user com a flag **Keep every log**.

Do resto, ele guarda uma amostra (5% por padrão). O badge **Kept** de um evento diz por que ele está lá.

Um evento **MCP tool** é uma chamada que um agente de código fez ao servidor MCP de desenvolvimento. O servidor só
roda na máquina de um dev, então esses eventos nunca aparecem em produção.

## Investigar um problema que um user reporta

1. Abra **System → Feature flags** e abra o gate de **Keep every log**.
2. Clique na seta da flag, ache o user e ligue o switch dele.
3. Peça para o user fazer de novo. Todo request dele passa a aparecer em **Logs**.
4. Desligue o switch quando terminar.

## Achar um evento

Escolha o dia e filtre por tipo, por resultado, por uma duração mínima, ou busque um request ID, uma rota, um job ou
um comando. Clique na seta no fim da linha para ver o evento.

A página de um evento lista os outros eventos com o mesmo request ID (os jobs que um request despachou, ou o request
que despachou um job) e as entradas do **Activity log** desse request.

Um filtro que acha pouco para depois de ler uma parte do arquivo. Clique em **Keep searching** para ler mais.

## Quanto tempo os eventos ficam

O app guarda os eventos dos últimos 14 dias. Os arquivos mais antigos são apagados todo dia.

Só super admins abrem esta tela.
