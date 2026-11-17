---
title: Settings
order: 4
---

# Settings

**Settings** changes how He4rt works for every user. Open it from **System → Settings**. Each
tab is one group of settings.

## Permissions

Each tab has two permissions. Give them to a role on **Roles & Permissions**, in the **Settings**
group.

| Permission | What it allows                                             |
| ---------- | ---------------------------------------------------------- |
| View       | See the tab. Without it, the tab is hidden.                |
| Update     | Change and save the tab. Without it, the tab is read only. |

Only the **Super Admin** role has both permissions by default.

## Authentication

This tab turns the login methods on and off.

| Setting                   | On                                                                                             | Off                                                                                                  |
| ------------------------- | ---------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| Two factor authentication | Every user must have an authenticator app. A user without one sets it up before the next page. | Nobody is asked for a code. The users keep their setup, and it works again when you turn it back on. |
| Passkeys                  | Users can log in with a passkey and add passkeys on their security page.                       | The passkey button and the passkeys section are hidden. The saved passkeys stay.                     |

> When you turn two factor on, you must set it up too, before the next page of the admin.

A new install starts with both methods off outside production, and on in production. Every change
goes to the **Activity log**.

### Social login

The **Social login** section has one switch per provider: GitHub, Google, Discord and Twitch. A
provider that is on shows a "Continue with" button on the login page. New users can sign up with it
while registration is open.

A provider needs an OAuth app and its credentials in `.env` first. Until then, its switch is locked
and names the keys to add, for example `GITHUB_CLIENT_ID` and `GITHUB_CLIENT_SECRET`. In the OAuth
app, set the callback URL to `https://<your app>/auth/<provider>/callback`, for example
`https://app.example.com/auth/github/callback`. Each provider shows its callback URL with a copy
button next to it.

| Setting    | On                                                       | Off                                                                                         |
| ---------- | -------------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| A provider | Users log in, sign up and connect their account with it. | The button is hidden. The connected accounts stay, and work again when you turn it back on. |

> Turning a provider off locks nobody out. A user without a password can still use **Forgot
> password** on the login page.
