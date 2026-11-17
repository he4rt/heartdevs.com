---
paths:
  - 'app-modules/*/src/**/Actions/**'
  - 'app-modules/*/src/**/Exceptions/**'
---

# Actions

An action holds one business operation of a domain module. Controllers, jobs, commands, listeners and other actions call it, so it never knows who called it.

## Shape

- An action lives in the `Actions/` folder of the sub-domain that owns the state it changes: `He4rt\Identity\Users\Actions\DeleteUser`. Never put one in a presentation module.
- It is a `final readonly class` with one public `handle()` method. Inject dependencies through the constructor as private properties. An action without dependencies has only `handle()`.
- `handle()` takes typed arguments: models, enums, value objects and scalars. Never a `Request` or an array of input. From the third input value on, it takes one DTO in place of the scalars.
- A DTO that a model stores has `toDatabase()`: the fields that go to their columns as they are, keyed by column name. A field that needs logic (a generated slug, a relation to sync) stays out, and the action handles it.
- A DTO never implements `Arrayable` or `JsonSerializable`. It carries input, and a secret in it must never reach a response or a log.

## Explicit names

- The name is the business verb and its object, with no suffix: `DeleteUser`, `StartImpersonation`, `LeaveImpersonation`, `SyncRolePermissions`, `UpdateAuthenticationSettings`.
- One operation is one action. Do not merge two operations into one action that takes the new state as an argument (`UpdateImpersonation::handle($user, active: false)`), and do not force a business verb into Create, Update or Delete when it hides the rule (`CreateImpersonation`).
- Never use a vague name: no `ManageUser`, `HandleTeam`, `ProcessX` or `*Service` with many methods.
- Code that only reads is not an action. A read goes in a `Queries/` class, and pure parsing or computing goes in a plain class.

## Writes, transactions and races

- Wrap the operation in `DB::transaction()` when it writes more than one row or model.
- Transactions and row locks live in actions, never in HTTP middleware.
- An action never authorizes or validates: the form request of the endpoint does that. The one exception is a race. When two requests can pass the same check before the write (a limit, the last owner of a team), the action locks the parent row in a short transaction and checks the condition again under the lock. If it no longer holds, the action skips the write, or throws its own exception when the caller must tell the user.

## Exceptions

- A domain exception lives in the `Exceptions/` folder of its sub-domain, extends `DomainException`, and is named after the rule it protects, with no `Exception` suffix: `UserOwnsTeams`, `TeamOwnerCannotLeave`, `CannotImpersonate`.
- It has a named constructor that builds the message: `UserOwnsTeams::make()`, `TeamOwnerCannotLeave::of($team)`.
- Throw it with `throw_if()` or `throw new`. The caller catches it and turns it into a toast or a field error.

```php
final readonly class DeleteUser
{
    public function handle(User $user): void
    {
        $ownsTeams = Team::withTrashed()->where('owner_id', $user->id)->exists();

        throw_if($ownsTeams, UserOwnsTeams::make());

        DB::transaction(function () use ($user): void {
            $user->tenants()->detach();
            $user->delete();
        });
    }
}
```
