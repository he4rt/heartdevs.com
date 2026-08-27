---
name: PRD / Módulo de Domínio
about: Documento de requisitos para uma nova feature ou módulo de domínio
title: ''
labels: prd
assignees: ''

---

# <Nome do Módulo / Feature>

> Uma linha resumindo o que este documento entrega.

---

## Descrição do problema
<!-- Descreva o estado atual e a dor. Como as coisas funcionam hoje (informalmente, manualmente)?
     O que está faltando? Que pergunta o sistema não consegue responder? Seja concreto sobre
     o gap, não sobre a solução ainda. -->


## Solução
<!-- O que este módulo/feature passa a ser (a "fonte da verdade" para o quê?).
     Deixe claro o limite de escopo: o que ele conduz ativamente vs. o que apenas registra.
     Se decisões são tomadas fora do sistema, diga que o módulo grava o resultado. -->


## User Stories
<!-- Formato: "Como <papel>, quero <ação>, para que <benefício>."
     Agrupe por ator quando fizer sentido. Cubra os casos felizes e os bloqueios/gates. -->

- Como **<papel>**, quero **<ação>**, para que **<benefício>**.
- Como **<papel>**, quero **<ação>**, para que **<benefício>**.
- Como **<plataforma>**, quero **<invariante/regra técnica>**, para que **<garantia>**.


## Decisões de implementação
<!-- As escolhas técnicas já fechadas. Referencie ADRs quando existirem. -->

### Módulo / Namespace
<!-- Onde vive o código? Namespace, ServiceProvider, escopo (ex: tenant-scoped). -->

### Autoridade & Autorização
<!-- Quem pode fazer o quê? Papéis, políticas (Policy), overrides. -->

### Modelo de dados
<!-- Liste tabelas, colunas relevantes, constraints (UNIQUE, partial unique, índices),
     chaves (UUID?), e o que é derivado vs. denormalizado. -->

| Tabela | Colunas-chave | Constraints / Índices | Observações |
|--------|---------------|-----------------------|-------------|
| `<tabela>` | `<colunas>` | `<unique / index>` | `<notas>` |

### Enums
<!-- Enumerações e seus valores possíveis. -->

### Gates / Pré-condições
<!-- Que estado precisa ser verdadeiro para uma ação acontecer? De onde o módulo lê isso? -->

### Actions
<!-- Os casos de uso públicos (verbos do domínio). Ex: CreateX, ApplyToY, DecideZ. -->

### Invariantes
<!-- Regras que sempre precisam valer (ex: "no máximo 1 ativo"). Onde são garantidas?
     Defense-in-depth: banco + camada de aplicação. -->


## Decisões de teste
<!-- O que será testado e por qual superfície (Actions/Policy públicas, estado persistido,
     eventos emitidos — não helpers privados). Liste os cenários confirmados em escopo. -->

- **<Grupo de invariantes>:** <o que asserir>
- **<Política/Autorização>:** <o que asserir>
- **<Fluxo principal>:** <casos felizes e de bloqueio>

**Prior art / referências:** <testes ou padrões existentes a seguir>


## Fora de escopo
<!-- O que explicitamente NÃO faz parte desta entrega. Evita scope creep e alinha expectativas. -->

- <item> — <por quê / onde é tratado>
- <item> — <por quê / onde é tratado>


## 📝 Notas adicionais
<!-- Decisões sutis, follow-ups de doc (não bloqueantes), caminho de evolução futura,
     perguntas em aberto que não travam a implementação. -->

- **Follow-up:** <pendência de documentação ou revisão>
- **Evolução:** <como isso pode crescer sem retrabalho>


## Subtarefas
<!-- Quebra em tracer bullets, em ordem de dependência. Vincule as tasks criadas no git aqui. -->

## Dúvidas/Incertezas
<!-- Liste aqui todas as dúvidas que ainda ficaram em aberto ou ainda precisam de maiores esclarecimentos  -->

---

### 🔗 Referências
<!-- ADRs, PRDs relacionados, issues, docs de arquitetura. -->

- ADR-XXXX — <título>
- <link / issue relacionada>
