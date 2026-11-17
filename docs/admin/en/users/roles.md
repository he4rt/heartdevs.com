---
title: Roles & Permissions
icon: heroicon-o-shield-check
order: 2
---

# Roles & Permissions

The platform uses **role-based access control (RBAC)**. Each user can be assigned one or more roles, and each role grants a specific set of permissions. Only **Super Administrators** can manage roles.

Navigate to **User Management > Roles** to view, create, or edit roles.

## Role List

The roles table shows:

- **Name** — The role name (searchable)
- **Guard** — The authentication guard the role applies to (usually `web`)
- **Permissions** — A count of how many permissions are assigned to this role
- **Last Updated** — When the role was last modified

## Creating a Role

Click **New Role** and fill in:

- **Name** — A descriptive name for the role (e.g., "Editor", "Viewer"). Must be unique.
- **Guard Name** — The authentication guard (defaults to `web`; usually no need to change this)

### Assigning Permissions

The **Permissions** section lists every resource, organized by category. Give each resource an access level:

| Level    | Grants                                                   |
| -------- | -------------------------------------------------------- |
| **None** | No access                                                |
| **Read** | View Any and View                                        |
| **Edit** | Read, plus Create and Update                             |
| **Full** | Every action, including Delete, Restore and Force Delete |

Use **Set all** in a category header to give every resource in that category the same level.

For a combination that no level covers, open the resource with the arrow button and turn single actions on or off. The resource then shows a **Custom** badge.

Click **Create** to save the role.

## Editing a Role

Click **Edit** on any role row to modify its name or permissions. Changes take effect immediately for all users who have this role.

## Deleting a Role

Use the **Delete** action on a role row or from the edit page. You will be asked to confirm before the role is removed.

> **Warning:** Deleting a role will revoke its permissions from all users who had it assigned.

## How Permissions Work

Permissions control access to specific resources and actions across the platform. Common permission types include:

- **View** — Can see records in a resource
- **Create** — Can create new records
- **Update** — Can edit existing records
- **Delete** — Can remove records

A user's effective permissions are the **combined set** of all permissions from all their assigned roles. If any role grants a permission, the user has it.

A feature flag (**System → Feature flags**) never replaces a permission. A feature behind a flag needs the flag on and the permission.

## Assigning Roles to Users

Roles are assigned from the **user edit form**. Navigate to a user's edit page to add or remove their roles.
