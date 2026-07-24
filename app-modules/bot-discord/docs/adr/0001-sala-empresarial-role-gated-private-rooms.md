---
type: adr
title: 'Sala Empresarial — role-gated private voice rooms'
module: bot-discord
status: accepted
date: 2026-07-23
---

# Sala Empresarial — role-gated private voice rooms

Partner companies (**Empresa Parceira**) need private voice rooms on the guild. We add a
separate `/sala-empresarial empresa:<slug>` slash command that **converts an existing
`/sala`-tracked room** into a private one by stamping Discord permission overwrites: `deny`
`@everyone` and `allow` the selected company's **Partner Role** for `CONNECT`, `SPEAK`,
`USE_VAD`, `SEND_MESSAGES` and `READ_MESSAGE_HISTORY` (the last two lock the room's built-in
text chat). `MENTION_EVERYONE` and `VIEW_CHANNEL` are left unchanged by the command — the
former keeps whatever the category inherits (every `/sala` room already blocks it), the
latter stays untouched — so the room is visible-but-locked.

## Considered Options

- **Convert existing room vs. a flag on `/sala`.** We convert. `/sala` is shared by the
  whole guild; branching its logic and gating a `empresarial:true` flag by role would
  couple a general command to partner concerns. A dedicated command mirrors `/sala-limite`
  and keeps `/sala` untouched.
- **Explicit `empresa` parameter vs. inferring the caller's role.** Explicit parameter. A
  caller can belong to more than one Empresa Parceira, so intersecting their roles with the
  registry is ambiguous. The parameter picks exactly one company; we then validate the
  caller actually holds that company's Partner Role and reject (ephemeral) otherwise.
- **Config registry vs. hardcoding `brd`.** A flat `bot-discord.roles.partners`
  (`slug => role_id`) registry. Adding a second partner company is one env + one config
  line, no code change. The slash-command `choices` are built from this map. Each partner's
  Partner Role id is seeded as an env-var default in `config/bot-discord.php` (the source of
  truth), so no role ids live in this ADR.
- **Stateless vs. persisting the empresarial state.** Stateless. We only convert
  `/sala`-tracked rooms, which `DynamicVoiceTask` auto-deletes when empty, so the overwrites
  die with the channel. `VoiceChannelDTO` is left unchanged and Discord's overwrites are the
  single source of truth; re-running the command is a harmless re-stamp. There is no revert
  command — a room un-privatizes by emptying and being deleted.

## Consequences

- This is the first permission-overwrite code in `bot-discord`; prior room commands only
  set the channel's parent/user-limit and relied on category inheritance.
- Gating is by Partner Role membership, **not** room ownership (unlike `/sala-limite`) — any
  member of the selected company may privatize the room.
- Because `VIEW_CHANNEL` is not denied, empresarial rooms remain listed to everyone. A
  non-member already connected at conversion time is not force-disconnected but is muted
  (`deny SPEAK`/`USE_VAD`) until they leave. Both are accepted trade-offs for the MVP.
