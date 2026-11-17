---
paths:
  - 'app-modules/*/src/**/Rules/**'
  - 'app-modules/*/src/**/Http/Requests/**'
  - 'app-modules/*/src/**/Auth/**'
  - 'app/Rules/**'
---

# Validation rules

## Write the rules where they run

- Each form request, and each Fortify action that calls `Validator::make()`, lists its rules inline, with Laravel's rules: `'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')]`.
- Never group rules in a class of static methods (`AccountRules::email()`) or in a base request. When two endpoints need the same rules, repeat them. The list stays next to the endpoint that it protects, and a change to one endpoint does not change the other.

```php
// Avoid: a class that only returns lists of Laravel rules
final class AccountRules
{
    public static function email(?string $ignoreUserId = null): array { /* ... */ }
}

'email' => AccountRules::email(ignoreUserId: $user->id),

// Good: the request states its own rules
'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user)],
```

## Custom rules

- Write a custom rule only for a check that Laravel's rules cannot do: a domain query or a domain invariant, such as a role that the actor can grant.
- Create it with `php artisan make:rule <Name> --no-interaction`, then move it to its folder:
    - A rule that wraps a query or an invariant of a domain module lives in that module: `app-modules/<module>/src/<Context>/Rules/`. Both panels and the domain actions use it.
    - A rule that only one screen needs lives in the feature's `Rules/` folder in the panel.
    - A rule that every module uses, and that has no domain, lives in `app/Rules/`.
- The name says what a valid value is, with no `Rule` suffix: `GrantableRole`, `TeamOfActor`.
- A rule is `final readonly`, implements `ValidationRule`, takes the request context (actor, team) in its constructor and takes an optional `$message`. A rule that also implements `DataAwareRule` or `ValidatorAwareRule` is only `final`, because the validator writes to it.
- A list of allowed ids or values that a query builds is a rule object, not `Rule::in()`.
- A rule skips a value that another rule on the same field already rejects, such as an id that is not a uuid.
- A domain action that other callers reach without a form request keeps its own check with the same rule.
- Every custom rule has a test for a value it accepts and for each kind of value it refuses.

```php
// Avoid
'role_id' => ['required', Rule::in($actor->grantableRoles()->pluck('id'))],

// Good: the rule lives in the identity module, and the action uses it too
'role_id' => ['required', 'uuid', new GrantableRole($actor)],
```

```php
namespace He4rt\Identity\Permissions\Rules;

final readonly class GrantableRole implements ValidationRule
{
    public function __construct(private User $actor, private ?string $message = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !Str::isUuid($value)) {
            return;
        }

        $isGrantable = $this->actor->grantableRoles()->whereKey($value)->exists();

        if (!$isGrantable) {
            $fail($this->message ?? 'You cannot grant this role.');
        }
    }
}
```
