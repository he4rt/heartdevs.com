# Contexto do Delas

Este módulo é a fonte da verdade sobre **quem faz parte da He4rt Delas no Hub**: a tag que a
pessoa pede no perfil, a decisão das moderadoras, os bloqueios de quem não pode pedir e o
histórico de cada uma dessas decisões.

É um **módulo de domínio puro**: não tem rota, view nem Filament.

- O `panel-app` (`/app`) mostra a solicitação no perfil e a tag no card do perfil.
- O `panel-admin` (`/admin`) tem a moderação, no grupo He4rt Delas do cluster de Moderação
  (`/admin/mod`): Fila, Bloqueios, Histórico e Equipe (números, conceder e remover a tag,
  moderadoras). Moderadoras e líderes entram no `/admin` só para isso
  ([ADR-0003 do identity](../identity/docs/adr/0003-acesso-ao-admin-por-papel-de-moderacao.md)).
- A logo, a tag e o ícone da He4rt Delas são do design system (módulo `he4rt`).

Os dois painéis dependem deste módulo, e este módulo só depende do `identity`.

> **Status:** implementado (aguardando PR). O design está na
> [spec](../../docs/specs/2026-10-06-he4rt-delas-tag-e-moderacao.md) e as decisões na
> [ADR-0001](docs/adr/0001-modulo-proprio-com-papel-e-historico.md).

## Glossário

| Termo                      | Definição                                                                                                                                                                                                                                                         | Não confundir com                                                                                      |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| **Tag He4rt Delas**        | Marca no perfil de que a pessoa faz parte da He4rt Delas. Existe quando a solicitação dela está `approved`.                                                                                                                                                       | Um Badge do `gamification`: badges vêm de XP ou claim, a tag vem de uma decisão.                       |
| **Solicitação**            | Uma linha em `delas_tag_requests`: o pedido da tag e a decisão sobre ele (`pending`, `approved`, `rejected`, `revoked`).                                                                                                                                          | O cargo `he4rt_delas` do Discord, concedido pelo `/cargo-delas`. Os dois ainda não são sincronizados.  |
| **Moderadora He4rt Delas** | Quem tem a role `delas-moderator` (`UserRole::DelasModerator`) e passa no gate `moderate-delas`. Decide solicitações e bloqueia solicitantes.                                                                                                                     | A moderação de conteúdo do módulo `moderation` (Case, Appeal, Moderator).                              |
| **Líder He4rt Delas**      | Quem tem a role `delas-lead` (`UserRole::DelasLead`) e passa no gate `lead-delas`. Tudo o que a moderadora faz, mais gerenciar moderadoras, conceder e remover a tag e ver o histórico completo (página Equipe).                                                  | Super admin: no `/admin` a líder só acessa a área da He4rt Delas e não nomeia outras líderes.          |
| **Bloqueio**               | Uma linha em `delas_requester_blocks`: impede a pessoa de solicitar a tag até ser desbloqueada. Sempre tem motivo. Não tira a tag de quem já tem.                                                                                                                 | Ban ou suspensão (`UserSituation` do `identity`): o bloqueio não afeta a conta nem o resto do Hub.     |
| **Espera**                 | Prazo depois de uma solicitação `rejected` ou `revoked` em que a pessoa não pode pedir de novo. Padrão de 15 dias (`delas.request_cooldown_days`).                                                                                                                | O bloqueio: a espera acaba sozinha, o bloqueio só acaba quando alguém desbloqueia.                     |
| **Discord conectado**      | Requisito para pedir a tag (`delas.require_discord`): uma identidade Discord conectada pela própria pessoa (escopo `activelyConnected` do identity). Fica antes de liberar o pedido, depois da espera e do bloqueio.                                              | Ter o cargo `he4rt_delas` no Discord: o cargo ainda não é sincronizado com a tag.                      |
| **Concessão direta**       | Líder (ou super admin) dá a tag sem solicitação e sem espera (`GrantDelasTag`). Encerra um bloqueio ativo, se houver.                                                                                                                                             | Aprovar uma solicitação: aprovar é decisão sobre um pedido que a pessoa fez.                           |
| **Histórico**              | `delas_transitions`: uma linha por ação (solicitar, aprovar, rejeitar, conceder, remover, bloquear, desbloquear), com quem fez, quando e motivo.                                                                                                                  | Logs da aplicação: o histórico é dado de domínio, append-only, gravado pelas próprias actions.         |
| **Candidatas**             | Quem pode ser escolhida nos seletores de pessoa da Equipe (`DelasCandidates`): conceder lista quem não tem a tag, remover lista só quem tem e não é da equipe, adicionar moderadora lista só quem tem a tag, sem super admins e a equipe. Quem age nunca aparece. | A regra de negócio: a consulta só filtra a lista; quem valida a ação são as actions de domínio.        |
| **Membra**                 | Quem tem a tag hoje: solicitação `approved`. Aparece na página Membras, onde a líder pode remover a tag e bloquear novos pedidos junto.                                                                                                                           | Moderadora: membra é quem tem a tag, moderadora é quem decide sobre ela.                               |
| **Edição de motivo**       | Linha `reason_corrected` no histórico que aponta (`corrects_id`) para a decisão editada. A original fica como foi escrita; vale a edição mais recente. Na tela, "Editar motivo" e "Editado em".                                                                   | Sobrescrever o motivo: nada no histórico é apagado. No código, a action se chama `CorrectDelasReason`. |
