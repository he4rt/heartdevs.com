---
paths:
  - 'app/Models/**'
  - 'app-modules/*/src/**/*.php'
  - 'database/migrations/**'
  - 'app-modules/*/database/migrations/**'
---

# Models

These rules apply to every Eloquent model and to every schema change.

## Explicit class attributes

Every model declares its table and factory, also when they match Laravel's convention:

- `#[Table(name: '...')]`, always.
- `#[UseFactory(XxxFactory::class)]` when the model has a factory, never a `newFactory()` override. Keep the `HasFactory` trait, which gives `factory()`.

```php
// Good
#[UseFactory(UserFactory::class)]
#[Table(name: 'identity_users')]
final class User extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
}

// Avoid: implicit table and a newFactory() override
final class User extends Model
{
    use HasFactory;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
```

## PHPDoc follows the schema

When a migration, raw SQL or a schema dump adds, removes, renames or changes the type, nullability or default of a column, update the `@property` block of the model in the same commit. Every column has one line.

| Column | PHPDoc |
| --- | --- |
| `uuid`, `string`, `text` | `string` |
| `integer`, `bigInteger` | `int` |
| `boolean` | `bool` |
| `timestampTz`, `dateTimeTz` | `Carbon\|null` |
| `decimal`, `float` | `float` |
| backed enum or custom cast | the cast type (`TeamStatus`, `IdentityMetadata`) |

- Add `|null` when the column is nullable. `created_at` and `updated_at` are always `Carbon|null`.

## Typed JSON casts

- A JSON column with a known shape casts to a value object: `'metadata' => AsIdentityMetadata::class`, read as `$identity->metadata->lastSyncAt(IdentityCapability::Projects)`. The value object owns the shape, the validation and the accessors (`fromArray()`, `toArray()`, immutable `withX()` copies). The cast is only the bridge.
- Never use `'array'`, `'json'`, `'object'`, `'collection'`, `AsArrayObject`, `AsCollection`, `AsEnumArrayObject`, `AsEnumCollection`, `AsEncryptedArrayObject` or `AsEncryptedCollection`. `tests/Arch/NoLooseArrayCastsTest.php` fails on them. Its allowlist is only for JSON that is really polymorphic, and every entry has a reason.
- Declare the cast generic for the setter too, `CastsAttributes<TGet, TGet|array<array-key, mixed>>`, so an assignment of a raw array stays a typed branch.
- One owner keeps a key or a format, usually an enum accessor such as `IdentityCapability::lastSyncedAtKey()`. Never parse a string to recover a key.
- Prefer a Data class or a value object to an array or `stdClass` anywhere the shape is known: job payloads, event properties, integration responses, service returns. Never use `stdClass` in domain code.

## A model is not a response

- Never change a model to send more to a page. Add the value where the response is built: in the Data class, or in the props of the controller.
- Never attach a value to a model for a response: no `forceFill()`, `setAttribute()`, `setRelation()` or `$team->is_own = true`.
- Never add or change a `toArray()` on a model to add, rename or compute a value for a response.
- `with()`, `withCount()` and selected columns are loading, not changes. `forceFill()` followed by `save()` in an action is a write, not a response.
