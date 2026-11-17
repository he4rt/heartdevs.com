# Product Metrics

## Purpose

Como o He4rt Hub mede se está cumprindo o propósito do PRODUCT.md: membros evoluindo de forma demonstrável, com trajetória documentada e trabalho validado. Este arquivo define o que medir, como medir e as metas de partida. Toda meta aqui é hipótese v0 e é revisada com dado real 60 dias após o lançamento de cada etapa.

## North Star

**Membros com validação no período:** número de membros distintos que receberam pelo menos uma validação nos últimos 30 dias.

- É a métrica que só o Hub consegue produzir e que junta as três peças do produto: conta, prática e avaliação feita por pessoas.
- Não soma validações, conta pessoas. Um membro com cinco validações vale um.
- Hipótese v0: 100 membros com validação por mês ao fim dos primeiros 90 dias de squads e validação.

## Funnel

O funil do Hub tem três etapas próprias, depois do alcance que vem da marca.

| Etapa             | Definição                                                                                                             | Hipótese v0                               |
| ----------------- | --------------------------------------------------------------------------------------------------------------------- | ----------------------------------------- |
| Alcance da marca  | Cadastros na plataforma (~49 mil) e membros do Discord (26.161, media kit ago/2026). Bases diferentes, nunca somadas. | Referência, sem meta                      |
| Conta no Hub      | Membro com conta criada no Hub                                                                                        | 2.000 contas em 90 dias                   |
| Perfil ativado    | Conta com perfil completo e pelo menos um registro de trajetória                                                      | 50% das contas                            |
| Prática em squad  | Membro que passou no teste de entrada e está em um squad                                                              | 10% das contas                            |
| Trabalho validado | Membro com pelo menos uma validação registrada                                                                        | 50% de quem está em squad, em até 60 dias |

## Engagement

- **Membro ativo:** acessa o Hub diariamente ou passa a usar a plataforma com frequência. Medido como membro com acesso em pelo menos 3 dias distintos nos últimos 7.
- **Retenção:** percentual de contas novas que voltam em 7 e em 30 dias. Hipótese v0: 35% em D7 e 20% em D30.
- **Feed:** membros que publicaram no diário de aprendizado no mês. Hipótese v0: 15% dos membros ativos.
- **Squads ativos:** squads com projeto encaminhado no período. Squad inativo continua existindo e não conta aqui.

## Validation Operations

Métricas que protegem a sustentabilidade dos validadores.

- **Validadores habilitados:** total aprovado pela administração.
- **Ocupação:** validações abertas dividido pela capacidade total (validadores × 3). Acima de 80% por duas semanas seguidas é sinal para habilitar mais validadores.
- **Fila:** validações aguardando validador com espaço. Hipótese v0: fila nunca acima de 2× a capacidade total.
- **Tempo até validação:** dias entre o pedido e o registro. Hipótese v0: mediana de até 14 dias. É acompanhado, nunca prometido ao membro.
- **Evidência insuficiente:** percentual de validações registradas como evidência insuficiente. Alto indica problema no teste de entrada ou no squad, não no membro.

## Opportunities

- **Membros elegíveis:** membros com participação em squad ou validação registrada, que são os que veem o mural.
- **Candidaturas por vaga** e **vagas preenchidas por membro**, sempre com fonte e sem inventar contratação.
- **Consentimento:** percentual de candidaturas com envio de dados autorizado pelo membro. Envio sem consentimento é sempre zero.
- Hipótese v0: 20% dos membros elegíveis se candidatam a pelo menos uma vaga no primeiro trimestre do mural.

## XP and Achievements

XP é reconhecimento no perfil. Não libera acesso, não aparece em ranking e não é calculado por mensagem, voz ou presença.

| Ação apurada pela plataforma        | XP (hipótese v0)                    |
| ----------------------------------- | ----------------------------------- |
| Perfil completo                     | 50, uma vez                         |
| Curso concluído na Basement         | 30 por curso                        |
| Teste de entrada do squad aprovado  | 100, uma vez                        |
| Entrega aceita no squad             | 40 por entrega                      |
| Validação recebida                  | 80 por validação                    |
| Publicação no diário de aprendizado | 5 por publicação, até 20 por semana |

- Conquistas são marcos fixos (primeiro squad, primeira validação, primeiro projeto publicado) ou concedidas pela administração.
- Nenhum valor é editável pelo membro, e nenhuma ação manual gera XP.

## Usability

- PostHog registra os eventos do funil e de cada funcionalidade.
- Clarity registra mapa de calor e fluxo de navegação, com dados pessoais mascarados e campos de texto ocultos.
- Toda funcionalidade nova entra com evento definido antes do desenvolvimento.

## Core Events

| Evento                  | Quando dispara                           |
| ----------------------- | ---------------------------------------- |
| account_created         | Conta criada no Hub                      |
| profile_completed       | Perfil completo pela primeira vez        |
| trajectory_record_added | Registro novo na trajetória              |
| squad_test_submitted    | Pull request do teste de entrada enviado |
| squad_joined            | Membro entra em um squad                 |
| delivery_accepted       | Entrega aceita no squad                  |
| validation_requested    | Pedido de validação criado               |
| validation_assigned     | Validação distribuída a um validador     |
| validation_recorded     | Validação registrada                     |
| validation_published    | Membro publica uma validação no perfil   |
| feed_post_created       | Publicação no diário de aprendizado      |
| job_viewed              | Vaga aberta por membro elegível          |
| job_applied             | Candidatura enviada com consentimento    |

## Data Rules

- Números que mudam nunca ficam fixos em copy ou interface.
- Cadastros da plataforma e membros do Discord nunca são somados.
- Métrica apurada pela plataforma não é editada à mão.
- Dados de menores de idade seguem a LGPD e não entram em gravação de sessão sem mascaramento.
- Revisão das hipóteses v0: 60 dias após o lançamento de cada etapa do roadmap.
