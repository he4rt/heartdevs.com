# 0001. Social accounts replace the external identities

- Status: accepted
- Date: 2026-11-11

## Context

`ExternalIdentity` linked an OAuth account to a **team**, so a team could sync resources (projects, contributions) from GitHub. It had a driver registry, capabilities, a sync pipeline on Saloon, and the admin screen **Teams › Integrations**.

No driver was ever registered, and Socialite was not installed. The sub-domain stored OAuth tokens per team without a feature that used them, against `.ai/domain/04-data`.

The boilerplate needs social login: a person logs in with GitHub, Google, Discord or Twitch.

## Decision

- Remove `ExternalIdentity`, its tables, its permissions, the Integrations screen and `saloonphp/saloon`. A forward-only migration drops the tables and the permissions in a database that has them.
- Add `SocialAccounts` to `identity`: a social account links a provider account to a **user**. It stores the provider and the provider's user id. It stores no token, because the login does not call the provider's API after the callback.
- The panels call Socialite directly. `panel-app` holds the redirect and callback endpoints and the Discord and Twitch drivers. It turns the provider's answer into a `SocialUser` DTO, and the identity actions take that DTO.

## Exception to `.ai/rules/services.md`

The rule puts every third-party service behind a contract, with the implementation in an `integration-*` module, and allows only the implementation to ask whether a service is configured.

Social login does not follow it:

- Socialite is already the abstraction over the providers, one driver per vendor, and only the login of `panel-app` talks to them. A contract with one implementation adds a layer without a second use.
- `He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders` reads the client id and secret from `config/services.php`, so the admin can lock the switch of a provider without credentials. It is the only place that asks.

Reopen this decision when a second caller needs a provider (a data sync, another panel). Then an `integration-socialite` module takes the Socialite code behind a contract.

## Consequences

- A fork that wants team integrations builds them again, as an `integration-*` module.
- A team has no OAuth connection any more. A user has zero or more social accounts, one per provider.
