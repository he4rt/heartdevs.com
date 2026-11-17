# Product

## Platform

web

## Users

Pessoas em evolução profissional em tecnologia: entrantes, juniores, plenos e quem está migrando de carreira. Na v1 o foco é desenvolvimento; produto, design, QA e infraestrutura entram nas versões seguintes, dados depois delas, e marketing fica fora do escopo. Todos percorrem a mesma jornada, mudando apenas o ponto de partida; o que trava essa pessoa é visibilidade e preparo, não falta de conteúdo. Junto delas estão os membros que já chegaram e voltam para contribuir: validar trabalho, orientar e conduzir squads, com disponibilidade limitada e muitas vezes voluntária. Os dois lados dependem um do outro. A base que a comunidade já tem é suficiente para a v1: o Hub existe primeiro para valorizar quem já está, não para buscar volume. A conta vem de login social (Discord, GitHub, Google) ou de cadastro próprio, com redes vinculadas depois; quem quer métricas de código no perfil vincula o GitHub. Visitantes leem o perfil público sem login, e nenhuma ação é possível sem ele.

## Product Purpose

O He4rt Hub é a referência da trajetória dos membros da comunidade He4rt Developers. Reúne o que cada pessoa aprendeu, praticou, entregou e teve validado, e deixa o membro escolher como apresentar isso. O Hub não é dono dos cursos nem das vagas: a aprendizagem acontece na Basement e a preparação para candidaturas externas no Job Finder, produtos da própria comunidade que compartilham o mesmo cadastro com o Hub. O que o Hub produz é reputação verificável, e nenhuma etapa dele gera receita direta. Sucesso é o membro evoluir de forma demonstrável, com trajetória documentada e trabalho validado, e sair mais preparado do que entrou; ser contratado é o melhor desfecho, não o único.

## Positioning

- **Trajetória, não placar.** O Hub organiza a evolução sem transformar atividade em competição.
- **Reputação validada por pessoas.** A comunidade é a única que observa como alguém aprende, entrega e colabora. Plataforma de curso emite certificado e mural de vagas lista perfil declarado; nenhuma das duas tem esse dado.
- **Currículo vivo.** O perfil enriquece conforme o membro usa a plataforma, e o link dele é compartilhável como currículo.
- **Prática antes de vaga.** Squads dão projeto colaborativo próximo de situação real de trabalho, e é desse trabalho que sai a evidência.
- **O Hub é onde a participação deixa rastro.** Redes sociais e Discord são porta de entrada, o WhatsApp é a comunidade oficial que distribui, e o Hub registra e devolve a pessoa à comunidade. Não disputa atenção com rede social e não é mural de vagas de terceiros.

## Operating Context

- Superfícies: a aplicação web do Hub, o painel administrativo e a medição de uso (PostHog para eventos, Clarity para mapa de calor e fluxo).
- Produtos da comunidade: Basement (cursos) e Job Finder (preparação para candidaturas) são produtos separados do Hub, mas usam o mesmo cadastro. A conta criada em qualquer um deles entra nos outros, com pequenas complementações quando necessário, e os dados ficam sincronizados nos três ao mesmo tempo. GitHub é o ambiente de trabalho dos squads e fonte de evidência técnica.
- Canais: WhatsApp é a comunidade oficial e o distribuidor; Discord, LinkedIn, X, Instagram e Dev.to são portas de entrada. O Discord mantém as calls diárias e deixa de ser o centro; a estratégia própria dele vive fora deste arquivo.
- O Hub fica linkado em todos os pontos de contato da comunidade: site, descrição e mensagem fixada do grupo de WhatsApp, Discord e redes. Não existe fluxo dedicado de migração; a pessoa chega ao Hub por qualquer um desses meios.
- Visitantes leem o perfil público. Participar de squad, validar, se candidatar e editar perfil exigem login.
- A identidade tem uma fonte oficial única, compartilhada por Hub, Basement e Job Finder, e nenhuma funcionalidade mantém cópia divergente dela. Integrações preservam origem, integridade e histórico dos dados, e a indisponibilidade de um parceiro não compromete registros já consolidados.
- Papéis: membro, capitão de squad, validador e administração. A administração habilita validadores a partir de prova técnica, cadastra vagas, gere squads e concede conquistas manuais; o capitão responde apenas pelo próprio squad.
- Comportamento exato, limites e casos de exceção vivem nos documentos complementares (squads, validação, métricas, competências, privacidade e integrações) e no código; este arquivo registra apenas o que precisa continuar verdadeiro.

## Capabilities and Constraints

O que existe, uma linha cada, em quatro domínios:

**Identidade e trajetória**

- **Perfil:** dados pessoais, áreas, stacks, experiência, redes vinculadas, histórico e validações que o membro escolheu publicar.
- **Trajetória:** cinco tipos de registro: conhecimento declarado, aprendizagem registrada (inclusive cursos vindos da Basement), experiência registrada, competência demonstrada e competência validada.
- **Competências:** por área e nível de domínio, sem senioridade geral; estrutura comum, critérios próprios de cada competência.

**Desenvolvimento**

- **Preparação para o squad:** antes de entrar em um squad o membro monta o perfil, registra a trajetória e tem acesso ao material que cobre o teste de entrada.
- **Squads:** times de projeto colaborativo em repositórios da organização He4rt no GitHub, conduzidos por um capitão. A entrada exige um teste simples (repositório público, commit e pull request), cujo conteúdo é publicado antes.
- **Validação:** registro da avaliação do trabalho individual, feito por validador habilitado de fora do squad, com contexto, contribuição, pontos fortes, pontos de melhoria, avaliador identificado e conflito de interesse declarado.
- **Histórico de participação:** projetos, responsabilidades, entregas, contribuições técnicas e não técnicas e validações.
- **Log do squad:** registro permanente do que cada squad fez, projeto a projeto, que serve de referência e de currículo para os membros.

**Oportunidades**

- **Vagas exclusivas:** mural curado de vagas por tempo determinado, visível a membros que tenham participado de squad ou recebido validação.

**Comunidade**

- **Feed:** diário de aprendizado, onde o membro registra o que aprendeu, como aplicou e o quanto isso o aproximou do objetivo.
- **Progresso e conquistas:** XP e conquistas como reconhecimento no perfil, derivados de atividade apurada pela plataforma.
- **Administração:** moderação, vagas, squads, validadores e conquistas manuais.

Regras que toda funcionalidade precisa manter:

- **Validação não é certificado.** Não é nota, aprovação ou selo: é registro de evidência, com avaliador identificado e conflito de interesse declarado, e só é registrada com pontos fortes e pontos de melhoria. Prevê o caso de evidência insuficiente. É imutável, e correção ou contestação acontece por novo registro que não oculta o anterior.
- **O capitão não valida o próprio squad.** Ele registra o contexto; quem valida é de fora do squad.
- **A validação é da pessoa, não do squad.** Todo histórico distingue o que foi o membro do que foi o time.
- **Validação é privada por padrão.** Só aparece no perfil público quando o membro escolhe publicar.
- **Quem valida não ganha com a contratação de quem validou.** Se houver retribuição ao validador, é pela validação feita, nunca pelo emprego de quem foi avaliado.
- **Validador é habilitado pela administração.** Quem quer validar procura a administração e apresenta prova técnica de capacidade: currículo, certificações, perfis, arquivos e contribuições, dentro e fora da comunidade. Sem bagagem técnica suficiente o pedido não é aprovado, mas a recusa não é permanente e a pessoa pode pedir de novo depois de evoluir.
- **O validador tem limite.** Cada validador tem uma janela de 3 validações abertas ao mesmo tempo. O que excede entra em uma fila única e é distribuído automaticamente para o validador que liberar espaço. A plataforma não promete prazo de atendimento que os validadores não conseguem cumprir.
- **Projeto de squad é da comunidade, o mérito é de quem fez.** Repositórios ficam sob a organização He4rt, e todos os membros que contribuíram são creditados no README, nos arquivos que assinaram e em toda divulgação do projeto, inclusive nas redes sociais.
- **Acesso a vaga vem de evidência.** O piso é ter participado de squad ou ter validação registrada; XP, tempo de comunidade e conquistas nunca são requisito.
- **XP é reconhecimento, não competência.** Não libera acesso, não aparece em ranking e não é calculado por mensagem, voz ou presença.
- **Nada sai da comunidade sem consentimento.** Perfil, histórico e validações só vão para uma empresa com consentimento ativo do membro, revogável para tudo que ainda não foi enviado.
- **Sair para o mercado é sucesso.** Nenhuma funcionalidade existe para reter o membro.
- **O que a plataforma apura, o membro não edita.** Métricas, progresso, validações e conquistas não se alteram à mão.
- **Nada contorna a moderação.** Conteúdo ofensivo é barrado, moderação automática apoia a manual, e não existe rota alternativa de publicação.
- **O feed serve ao aprendizado.** Recursos de feed cuja finalidade é disputar atenção ou tempo de permanência não entram.
- **Squad não é encerrado.** Um squad fica inativo quando não há projeto encaminhado ou necessidade momentânea de desenvolvimento, mas nunca é apagado nem encerrado, e o log e o histórico dos membros são preservados.
- **O mural é exclusivo.** Vaga restrita nunca fica acessível a quem não é membro, e o mural não agrega vagas de outros sites nem compete por volume.

Excluído de propósito: ser rede social, ser portal de recrutamento, agregar vagas externas, ter chat próprio ou caixa de entrada unificada de redes, ter streaming próprio ou player de transmissão na v1, emitir certificado ou declaração, ranking de XP, vender curso dentro do Hub, e dar a empresas acesso a perfis e validações sem consentimento.

## Data and Trust

- Todo registro tem origem identificável, e correções preservam o histórico.
- Validações identificam avaliador, competência, evidências e contexto.
- Informação privada nunca é publicada automaticamente.
- A medição de uso (PostHog e Clarity) existe desde o primeiro dia, com dados pessoais mascarados, campos de texto ocultos na gravação de sessão e aviso de coleta ao membro.
- O tratamento de dados segue a LGPD, com proteção específica para membros menores de idade.

## Brand Commitments

- Nome: **He4rt**, sempre com o número 4. O produto é o **He4rt Hub**; os produtos integrados são a **Basement** e o **Job Finder**.
- Voz: direta, honesta e de comunidade, jovem e profissional ao mesmo tempo, para um público entre 15 e 30 anos preocupado com o próprio futuro. Fala com o membro como colega, não como cliente.
- O lema "da comunidade para a comunidade" é a âncora.
- Vocabulário, na interface e no código: a pessoa é **membro**, o time de projeto é um **squad**, quem o conduz é o **capitão**, o registro de avaliação é uma **validação**, quem a registra é o **validador**, a publicação do feed é uma **publicação**, a insígnia é uma **conquista**, o registro de evolução é a **trajetória** e o que sustenta uma validação é a **evidência**. **Vacancy** designa squad sem capitão, nunca vaga de emprego. Sem sinônimos para esses termos; o membro nunca é chamado de talento, o Hub nunca é chamado de job board e a validação nunca é chamada de certificado, aprovação ou nota. Os nomes Ferramenta de IA e Jobs Academy não são usados.

## Evidence on Hand

- Dados reais podem ser citados sempre com a fonte junto: a comunidade existe desde 2018, o Discord tinha 26.161 membros em agosto de 2026 pelo media kit, e a plataforma tem cerca de 49 mil cadastrados. Os dois números medem bases diferentes e nunca são somados nem usados um pelo outro.
- O funil do Hub é medido em três etapas: conta criada, prática em squad e trabalho validado.
- Membro ativo é quem acessa o Hub diariamente ou passa a usar a plataforma com frequência.
- Dados de pesquisa externa só com a fonte citada.
- Números que mudam (membros, cadastros, vagas) nunca ficam fixos em copy, interface ou documentação.
- Nunca inventar contratação, depoimento, vaga, empresa parceira ou validação. Dado semeado existe só para desenvolvimento.

## Product Principles

1. **O membro é o beneficiário.** Uma funcionalidade se justifica se empurra o membro no percurso de aprender, praticar, ser validado e sair mais preparado.
2. **Trajetória antes de pontuação.** Registro e evidência valem mais que volume de atividade.
3. **Reputação vem de evidência.** Declarações, cursos e métricas não equivalem a competência; a validação feita por pessoas é o diferencial que nenhuma outra plataforma consegue oferecer.
4. **Participação não é competência.** Atividade comunitária gera reconhecimento, nunca acesso.
5. **Reconhecimento sem competição.** Nada estimula disputa por volume ou popularidade.
6. **Sustentabilidade dos voluntários.** A plataforma cabe na disponibilidade de quem valida e conduz squads.
7. **O membro controla a vitrine.** Ele decide o que é público e o que é compartilhado.
8. **Monetização responsável.** Receita é da Basement e do Job Finder; parcerias e sponsors não compram progresso, privilégio ou acesso a dados.
9. **Nem rede social, nem portal de recrutamento.** Nenhuma funcionalidade transforma o Hub em disputa de atenção nem o reduz a intermediário de contratação.
10. **Medir antes de crescer.** Funcionalidade nova entra com evento de medição definido.

## Release Strategy

1. Fundação: identidade, perfil público, trajetória, feed como diário de aprendizado, preparação para o squad e medição de uso.
2. Squads e validação.
3. Vagas exclusivas.
4. Reconhecimento: XP e conquistas.
