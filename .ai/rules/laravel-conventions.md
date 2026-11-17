---
paths:
  - '**/*.php'
---

# Laravel conventions

Use what Laravel already gives you before you write it with plain PHP. The framework helper says the intent, and Larastan knows its types.

## Collections, not native array functions

- Build and change a list with a collection: `collect($items)->map(...)->filter(...)`, not `array_map()`, `array_filter()`, `array_values()`, `array_unique()`, `sort()` or a `foreach` that pushes into an array.
- A list that leaves a collection ends with `->values()->all()`. Never wrap a collection in `array_values()`.
- Type that list `array<int, T>`, not `list<T>`. Larastan types `->values()->all()` as `array<int, T>`, and the TypeScript transformer still writes `T[]`. Never add `array_values()` only to make a `list<T>` PHPDoc pass.

```php
// Avoid: array_values() only to satisfy a list<T> PHPDoc
/** @return list<string> */
public function permissionIds(): array
{
    return array_values($this->safe()->collect('permissions')->map(strval(...))->unique()->all());
}

// Good
/** @return array<int, string> */
public function permissionIds(): array
{
    return $this->safe()->collect('permissions')->map(strval(...))->unique()->values()->all();
}
```

- An item that only some cases add to a literal list goes in with a spread, not with `array_filter()` to remove a `null`:

```php
return [
    CanonicalizeUsername::class,
    ...($challengesTwoFactor ? [RedirectsIfTwoFactorAuthenticatable::class] : []),
    AttemptToAuthenticate::class,
];
```

### Callback traps

- `map()` calls the callback with `($value, $key)`. A first-class callable whose second parameter is optional gets the key in it: `->map(UserRowData::fromModel(...))` sends the index as `$canImpersonate`. Wrap such a callable in a closure: `->map(fn (User $user): UserRowData => UserRowData::fromModel($user))`.
- `filter()` also passes `($value, $key)`. A native function as the callback (`is_string(...)`) throws `ArgumentCountError`, so use a closure: `->filter(fn (mixed $value): bool => is_string($value))`.

## Typed accessors, not casts

- Read input, config and collection values through the typed accessors: `$request->safe()->string('name')`, `->integer()`, `->boolean()`, `->enum()`, `config()->string('session.table')`.
- Never check a type and cast it by hand when an accessor does it: no `is_string($x) ? $x : ''` or `(int) $request->input('page')`.

## Helpers

- `blank()` and `filled()`, not `empty()` or `=== ''` on a value that can also be `null`.
- `throw_if()` and `throw_unless()` for a guard that throws a domain exception.
- `to_route()` for a redirect to a named route, not `redirect()->route()`.
- `once()` to memoize a lookup inside one request, not a nullable property that a method fills.
