@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# PHPStan — ignoreErrors Conventions

When adding entries to `ignoreErrors` in any `phpstan.neon` file, always use
the **indented block style**: a lone `-` on its own line, with keys indented
beneath it. Never use the inline `- { ... }` style, as it requires horizontal
scrolling and hurts readability.

## Correct format

@verbatim
<code-snippet name="ignoreErrors block style" lang="neon">
parameters:
    ignoreErrors:
        -
            message: '#^Error message regex here#'
            identifier: error.identifier
            count: 1
            path: src/Path/To/File.php
</code-snippet>
@endverbatim

## Rules

- `message` must be a regex wrapped in `#` delimiters.
- Always scope errors to a specific `path` — never leave an entry without one.
- Always include `count` so PHPStan warns if the number of occurrences changes.
- Always include `identifier` when PHPStan provides one (e.g. `property.notFound`).
- Do not escape spaces with `\ ` inside `#...#` regex patterns.
- Prefer fixing the root cause over ignoring. Only ignore when:
    - The error comes from a third-party or generated code.
    - The false positive is a known PHPStan/Larastan limitation (e.g. Livewire `$form`).

## Baseline

Prefer running `{{ $assist->binCommand('phpstan analyse --generate-baseline') }}` for bulk
legacy errors. Manual `ignoreErrors` entries are reserved for intentional,
documented suppressions only.
