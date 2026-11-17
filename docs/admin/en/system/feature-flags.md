---
title: Feature flags
order: 5
---

# Feature flags

A **feature flag** turns a feature on for some people before everyone, and turns it off again without
a deploy. Open it from **System → Feature flags**.

The developers declare the flags in the code. This screen only switches them on and off. He4rt brings
one flag, **Keep every log**, that keeps every event of a user in **System → Logs**.

## How a flag works

Every flag has a **scope** and a **gate**.

| Scope  | Who gets the feature                                  |
| ------ | ----------------------------------------------------- |
| Global | Everyone, as soon as the gate is open.                |
| Team   | The teams you turn it on for, while the gate is open. |
| User   | The users you turn it on for, while the gate is open. |

The **gate** is the main switch of the flag. When it is closed, nobody gets the feature, also the
teams and users you turned it on for. Use it to stop a feature that breaks. The lists stay, so when
you open the gate again, the same teams and users get the feature back.

A new flag starts off everywhere, with its gate closed.

A flag never gives access by itself. A person also needs the permissions of the feature.

## Turn a flag on

1. Open the gate of the flag in the **Gate** column.
2. For a team or user flag, click the arrow at the end of the row.
3. Search for the team or the user, and turn on its switch.

Use the **Enabled** filter to see only the teams or users that have the flag.

When the gate is closed and some teams or users have the flag, the row shows a warning: they do not
get the feature until you open the gate.

Every change goes to the **Activity log**.

## Permissions

The permissions are in the **Support** group of **Roles & Permissions**, on **Stored Feature Flag**.

| Permission | What it allows                                                    |
| ---------- | ----------------------------------------------------------------- |
| View       | See the flags and the teams and users that have them.             |
| Update     | Open and close the gates, and turn a flag on or off for a record. |

Only the **Super Admin** role has them by default.
