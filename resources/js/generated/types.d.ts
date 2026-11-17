declare namespace App {
namespace Data {
namespace Navigation {
export type NavigationGroupData = {
key: string,
heading: string | null,
items: App.Data.Navigation.NavigationItemData[],
};
export type NavigationItemData = {
key: string,
label: string,
icon: string,
href: string,
current: boolean,
};
}
namespace Support {
export type EnumOptionData = {
value: string,
label: string,
tone: App.Enums.Tone,
};
export type PageData = {
data: undefined[],
meta: App.Data.Support.PageMetaData,
};
export type PageMetaData = {
currentPage: number,
lastPage: number,
perPage: number,
total: number,
from: number | null,
to: number | null,
};
}
}
namespace Enums {
export type Language = 'en' | 'pt_BR';
export type ToastType = 'success' | 'error' | 'warning' | 'info';
export type Tone = 'neutral' | 'primary' | 'info' | 'success' | 'warning' | 'danger';
}
}
declare namespace He4rt {
namespace Identity {
namespace Permissions {
export type PermissionsEnum = 'view' | 'view_any' | 'create' | 'update' | 'delete' | 'restore' | 'force_delete';
export type Roles = 'super_admin' | 'user';
}
namespace SocialAccounts {
export type SocialProvider = 'github' | 'google' | 'discord' | 'twitch';
}
namespace Teams {
export type TeamStatus = 'active' | 'suspended' | 'archived';
}
}
namespace PanelAdmin {
namespace Features {
namespace Account {
namespace Profile {
namespace Data {
export type BrowserSessionData = {
browser: string,
platform: string,
ipAddress: string | null,
lastActiveAt: string,
isCurrent: boolean,
};
export type PasskeyData = {
id: number,
name: string,
authenticator: string | null,
createdAt: string,
lastUsedAt: string | null,
};
export type ProfileData = {
name: string,
email: string,
locale: string,
themeColor: string | null,
hasPassword: boolean,
};
}
}
}
namespace Home {
namespace Data {
export type DashboardStatData = {
key: string,
label: string,
value: number,
};
}
}
namespace Setup {
namespace Roles {
namespace Data {
export type PermissionActionData = {
key: string,
label: string,
};
export type PermissionGroupData = {
key: string,
label: string,
resources: He4rt.PanelAdmin.Features.Setup.Roles.Data.PermissionResourceData[],
};
export type PermissionMatrixData = {
actions: He4rt.PanelAdmin.Features.Setup.Roles.Data.PermissionActionData[],
groups: He4rt.PanelAdmin.Features.Setup.Roles.Data.PermissionGroupData[],
total: number,
};
export type PermissionResourceData = {
key: string,
label: string,
permissions: Record<string, string>,
};
export type RoleFormData = {
id: string,
name: string,
isSystem: boolean,
permissionIds: string[],
};
export type RoleRowData = {
id: string,
name: string,
label: string,
guardName: string,
permissionsCount: number,
usersCount: number,
isSystem: boolean,
updatedAt: string,
};
}
}
namespace Teams {
namespace Data {
export type TeamDetailData = {
id: string,
name: string,
slug: string,
description: string,
contactEmail: string,
status: App.Data.Support.EnumOptionData,
owner: He4rt.PanelAdmin.Features.Setup.Users.Data.UserOptionData,
membersCount: number,
deletedAt: string | null,
};
export type TeamRowData = {
id: string,
name: string,
slug: string,
status: App.Data.Support.EnumOptionData,
ownerName: string,
membersCount: number,
createdAt: string,
deletedAt: string | null,
};
}
namespace Members {
namespace Data {
export type TeamMemberData = {
id: string,
name: string,
email: string,
isOwner: boolean,
joinedAt: string | null,
};
}
}
}
namespace Users {
namespace Data {
export type UserOptionData = {
id: string,
name: string,
email: string,
};
export type UserRowData = {
id: string,
name: string,
email: string,
roles: string[],
verified: boolean,
createdAt: string,
canImpersonate: boolean,
};
}
}
}
namespace System {
namespace Activity {
namespace Data {
export type ActivityChangeData = {
field: string,
old: any,
new: any,
};
export type ActivityDetailData = {
row: He4rt.PanelAdmin.Features.System.Activity.Data.ActivityRowData,
logName: string | null,
subjectId: string | null,
changes: He4rt.PanelAdmin.Features.System.Activity.Data.ActivityChangeData[],
properties: Record<string | number, any>,
context: Record<string, string | null>,
integrity: unknown,
related: He4rt.PanelAdmin.Features.System.Activity.Data.ActivityRowData[],
};
export type ActivityRowData = {
id: number,
description: string,
event: string | null,
subjectType: string | null,
subjectLabel: string | null,
causerName: string | null,
riskScore: number | null,
riskLevel: string | null,
createdAt: string,
};
}
}
namespace Docs {
namespace Data {
export type DocNodeData = {
path: string,
title: string,
url: string,
children: He4rt.PanelAdmin.Features.System.Docs.Data.DocNodeData[],
};
export type DocPageData = {
path: string,
title: string,
html: string,
locale: string,
isFallback: boolean,
children: He4rt.PanelAdmin.Features.System.Docs.Data.DocNodeData[],
};
}
}
namespace FeatureFlags {
namespace Data {
export type FeatureFlagData = {
key: string,
label: string,
description: string,
scope: App.Data.Support.EnumOptionData,
gateOpen: boolean,
enabledCount: number,
sharedWithFrontend: boolean,
};
export type FeatureFlagTeamRowData = {
id: string,
name: string,
slug: string,
enabled: boolean,
};
export type FeatureFlagUserRowData = {
id: string,
name: string,
email: string,
enabled: boolean,
};
}
}
namespace Logs {
namespace Data {
export type EventActivityData = {
id: number,
description: string,
event: string | null,
};
export type EventDetailData = {
row: He4rt.PanelAdmin.Features.System.Logs.Data.EventRowData,
userName: string | null,
impersonatorName: string | null,
teamId: string | null,
teamName: string | null,
errorType: string | null,
errorMessage: string | null,
frames: string[],
fields: He4rt.PanelAdmin.Features.System.Logs.Data.EventFieldData[],
json: string,
related: He4rt.PanelAdmin.Features.System.Logs.Data.EventRowData[],
activities: He4rt.PanelAdmin.Features.System.Logs.Data.EventActivityData[],
};
export type EventFieldData = {
key: string,
value: string,
};
export type EventRowData = {
id: string,
day: string,
offset: number,
timestamp: string,
kind: App.Data.Support.EnumOptionData,
outcome: App.Data.Support.EnumOptionData,
title: string,
status: number | null,
durationMs: number | null,
keptBecause: string | null,
requestId: string,
userId: string | null,
};
}
}
namespace Mails {
namespace Data {
export type MailAttachmentData = {
id: number,
filename: string,
mime: string,
size: number,
url: string,
};
export type MailDetailData = {
row: He4rt.PanelAdmin.Features.System.Mails.Data.MailRowData,
from: string[],
cc: string[],
bcc: string[],
replyTo: string[],
mailer: string,
mailClass: string | null,
text: string | null,
hasHtml: boolean,
previewUrl: string,
sentAt: string | null,
attachments: He4rt.PanelAdmin.Features.System.Mails.Data.MailAttachmentData[],
events: {
type: string,
occurredAt: string | null,
}[],
};
export type MailRowData = {
id: number,
subject: string | null,
to: string[],
mailClass: string | null,
status: string,
attachmentsCount: number,
createdAt: string,
};
}
}
namespace Settings {
namespace Data {
export type AuthenticationSettingsData = {
twoFactorEnabled: boolean,
passkeysEnabled: boolean,
socialProviders: string[],
};
export type SettingsTabData = {
key: string,
label: string,
href: string,
current: boolean,
};
export type SocialProviderOptionData = {
value: string,
label: string,
configured: boolean,
credentialKeys: string,
callbackUrl: string,
};
}
}
}
}
namespace Shared {
namespace Data {
export type AdminSharedPropsData = {
user: He4rt.PanelAdmin.Shared.Data.AdminUserData,
navigation: App.Data.Navigation.NavigationGroupData[],
};
export type AdminUserData = {
id: string,
name: string,
email: string,
initials: string,
themeColor: string | null,
locale: string,
};
}
}
}
namespace PanelApp {
namespace Features {
namespace Account {
namespace Settings {
namespace Data {
export type BrowserSessionData = {
browser: string,
platform: string,
ipAddress: string | null,
lastActiveAt: string,
isCurrent: boolean,
};
export type PasskeyData = {
id: number,
name: string,
authenticator: string | null,
createdAt: string,
lastUsedAt: string | null,
};
export type SocialConnectionData = {
provider: App.Data.Support.EnumOptionData,
accountId: string | null,
connectedAt: string | null,
canDisconnect: boolean,
};
}
}
}
}
namespace Shared {
namespace Data {
export type AccountData = {
user: He4rt.PanelApp.Shared.Data.AppUserData,
navigation: App.Data.Navigation.NavigationGroupData[],
impersonatorName: string | null,
canAccessAdmin: boolean,
};
export type AppUserData = {
id: string,
name: string,
email: string,
initials: string,
emailVerified: boolean,
themeColor: string | null,
};
}
}
}
}
declare namespace Illuminate {
export type CursorPaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
path: string,
per_page: number,
next_cursor: string | null,
next_page_url: string | null,
prev_cursor: string | null,
prev_page_url: string | null,
},
};
export type CursorPaginatorInterface<TKey, TValue> = Illuminate.CursorPaginator<TKey, TValue>;
export type LengthAwarePaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
total: number,
current_page: number,
first_page_url: string,
from: number | null,
last_page: number,
last_page_url: string,
next_page_url: string | null,
path: string,
per_page: number,
prev_page_url: string | null,
to: number | null,
},
};
export type LengthAwarePaginatorInterface<TKey, TValue> = Illuminate.LengthAwarePaginator<TKey, TValue>;
}
declare namespace Spatie {
namespace LaravelData {
export type CursorPaginatedDataCollection<TKey, TValue> = Illuminate.CursorPaginator<TKey, TValue>;
export type PaginatedDataCollection<TKey, TValue> = Illuminate.LengthAwarePaginator<TKey, TValue>;
}
}
