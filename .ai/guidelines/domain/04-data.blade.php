# Data

Assume the database has leaked already. Every column, row, cache entry, log line and file is something an attacker has. The safest data is the data we never stored.

## Store as little as possible

- Add a column, table, cache key or file only when the feature cannot work without it. "Useful later", analytics, debugging and convenience are not reasons.
- Derive instead of storing: counts, flags and anything you can compute from data we hold are queried or computed. A cached computation is fine when the entry has a TTL and holds nothing sensitive.
- Keep only what is shown or acted on. Drop a value from a third party that the app never displays or uses, instead of saving it "just in case".
- Prefer short-lived to permanent: nonces, sync markers and similar state go in the cache with a TTL, not in a table.

## Never store or leak secrets

- Never persist a token, password, private key, API key, session id or anything else that grants access, unless the feature cannot work without it. Then store it encrypted, as Fortify does for `two_factor_secret`.
- Credentials come from the environment (`config/services.php`), never from a table, a seeder, a test fixture or a committed file.
- Never log a secret, a request body, an email address or a full request. Log identifiers (user id, team id, status code).
- Never put a secret in a job payload, a queued notification, a flash message, a URL, a cookie or an Inertia prop.

## Encrypt sensitive content

- Use an encrypted Eloquent cast for content that holds, or can hold, sensitive information: private free text, and personal or contact details that are not public.
- Operational metadata (timestamps, booleans, counters, foreign keys, status enums, routine preferences) uses normal columns. A field that only its owner sees does not need encryption for that reason alone.
- Encrypted content has no unencrypted copy for search.

## Expose nothing by default

- Only a Data class sends a model to the frontend, and it names every field it sends. Never send a model or its `toArray()`.
- Internal paths, other users' private records and third-party identifiers never leave the server. When the page needs a file, send a route to it, not the storage path.

## Delete everything with the account

- `He4rt\Identity\Users\Actions\DeleteUser` removes everything stored about a person. A new table, column or file that relates to a user cascades on delete, or `DeleteUser` removes it, and a test covers it.
