---
paths:
  - 'phpstan*.neon'
  - 'app-modules/*/phpstan*.neon'
---

# PHPStan

Fix the cause before you ignore an error. Ignore it only when it comes from third-party or generated code, or from a known PHPStan or Larastan limitation, such as a stub type wider than the runtime value.

## `ignoreErrors` entries

Use the block style: a lone `-` with the keys under it. Never the inline `- { ... }` style.

```neon
parameters:
    ignoreErrors:
        -
            message: '#^Error message regex here#'
            identifier: error.identifier
            count: 1
            path: src/Path/To/File.php
```

- `message` is a regex in `#` delimiters. Do not escape spaces with `\ ` inside it.
- Every entry has a `path` and a `count`, and an `identifier` when PHPStan gives one.

## Wrong vendor stubs

When a vendor stub reports a type wider than the runtime value (a union that is always one branch, a nullable that is never null), do not change working code to satisfy it. Ignore the error in the module's own `app-modules/<module>/phpstan.ignore.neon`, never in the root file, so the entry stays next to the code.

## Baseline

Use `vendor/bin/phpstan analyse --generate-baseline` only for a bulk of old errors. A hand-written `ignoreErrors` entry is for an intentional suppression with a reason.
