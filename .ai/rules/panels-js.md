---
paths:
  - 'app-modules/panel-*/resources/js/**'
  - 'resources/js/**'
---

# Panel frontend

Every UI change keeps the layout still. Nothing flashes, jumps, clips or reflows on the first load, on navigation, on a reload of props, or after an action.

## Building blocks

- Reuse the shared kit in `resources/js` (alias `@`) before you write a component: `components/ui` (Base UI, so use the `render` prop, not `asChild`), `DataTable` with `useTableQuery`, `PageHeader`, `ConfirmDialog`, `ToneBadge`, `lib/format`. A panel's own code uses its alias (`@admin`, `@app`).
- Call routes through Wayfinder, never with a hand-written URL: `import { index } from '@/wayfinder/routes/namespaced/panel-admin/teams'`. Run `bun run routes` after a route change.
- Read server types from `resources/js/generated/types.d.ts` (`He4rt.PanelAdmin.Features...`). Never repeat a Data class as a hand-written type.
- A form uses `useForm` and `form.submit(route())`. A request that returns JSON (a QR code, recovery codes) uses `useHttp`.

## Visual system

- `DESIGN.md` at the root is the visual system. Its tokens are the values of `resources/css/app.css`. Read it before you change how something looks.
- Use the tokens through Tailwind: `bg-card`, `text-muted-foreground`, `rounded-lg`, `font-heading`. Never write a raw colour, font family or radius in a component.
- A change to a colour token goes into `DESIGN.md` and `app.css` in the same change. `tests/Arch/DesignTokensTest.php` fails when they differ, and `bun run design:lint` checks the WCAG AA contrast.
- Display names the screen, mono identifies a record, sans does the rest. A page title is an `h1` to `h3`, which take the heading font from the base layer. An email, a date or a slug in a table cell goes in `<span className="data-id">`.
- Depth comes from the surface tokens (`sidebar`, then `background`, `card`, `popover`), not from a new shadow or a tinted background.

## Loading

- A skeleton has exactly the size of the loaded content: the same height, line count and wrapping at every breakpoint, in light and dark mode.
- Show a skeleton only while data is really missing, never while you reload data that you have. Delay it by about 300 ms, so a fast response shows nothing.

## Navigation and requests

- An Inertia `<Link>` to a GET page prefetches (`prefetch`). A GET page never writes. A page that must write on GET keeps `prefetch={false}` on every link to it.
- Never prefetch a non-GET link, an external URL, or a URL that is not an Inertia page (a file, a download, JSON).
- After an action that changes what a prefetched page shows, flush that page (`router.flush(url)` or `router.flushAll()`), so the next click does not show the old state.
- One user intent is one request. When the server would only echo the URL back (a search, a filter), change it on the client with `router.replace({ url, props })`. Data that the first paint needs comes with the page as a prop or a deferred prop, never from a fetch in a mount effect.
- A reload, a refresh button or a filter reloads only the page's own props (`only: [...]`), and the old data stays on screen until the new data arrives.
- After a write that did not go through Inertia (a library's own fetch, such as `@laravel/passkeys`), refresh the props with `router.visit(route(), { only: [...], preserveScroll: true, preserveState: true })`, never `router.reload()`. A reload is always async, and Inertia drops an async response when the asset version changed after a deploy, so the screen keeps the old data. A visit reloads the whole page in that case.

## Actions

- A button that calls the router directly is disabled while its request runs (`onStart` / `onFinish`, or `form.processing`), so a double click never sends twice.
- Update the UI at once and roll back on failure (`router.optimistic(...)`, `useHttp().optimistic(...)`) when the server returns no value that the UI needs. Wait for the server when the UI needs the server's value, or when a validation error must show in a form that the optimistic change would unmount.

## Rendering

- The React Compiler is off. Do not add `useMemo`, `useCallback` or `memo` by default. Add one only for a measured cost, or for an identity that an effect or a child really depends on.
- Derive values from props during render, or reset a subtree with a `key`. Never copy props into state with an effect.
- Read `window`, `matchMedia`, `localStorage` or the time through `useSyncExternalStore` or a hook built on it, never during render and never in a mount effect that sets state.
- Never declare a component inside another component.
- Key a list that adds, removes or reorders rows by a stable id. When rows have no id yet (a new row in a form), give each one a client-only `rowId` and strip it before you send the form:

```tsx
// Avoid: removing a row hands its DOM (focus, an open listbox) to the next one
<div key={index}>

// Good
buttons: (template?.buttons ?? []).map((button) => ({ rowId: crypto.randomUUID(), ...button })),
form.transform((data) => ({ ...data, buttons: data.buttons.map(({ rowId: _rowId, ...button }) => button) }));
<div key={button.rowId}>
```

## Changing content

- Numbers, counts and countdowns use `tabular-nums`.
- A control is the same size when it is disabled, loading or enabled. Put a spinner beside a stable label instead of changing the label.
- Conditional UI (a banner, an inline confirm, an error) reserves its space or overlays. An inline confirm fits in the slot of the control it replaces.
- Right after an action, the control under the pointer does not turn into a destructive one.

## Overflow

- A dialog or popover is capped to the viewport (`dvh`) and scrolls inside, with a sticky header and footer when its actions would scroll away. No double scrollbars, and no page scroll through the overlay.
- Long text (a name, an email, a URL) uses `min-w-0` with `truncate` or `line-clamp`, and the full text in `title`.
- A page stays as wide as the viewport on a phone. A page grid that holds a table or a wide row uses `grid-cols-1`, so the table scrolls in its own box.
- An input uses at least 16px text below `md`, so iOS does not zoom.

## Check before you call it done

- Look at the change in a browser at 1440×900, 1366×650 and 390×844, in light and dark.
- Open, select, dismiss and open again every overlay you changed, with the keyboard and with touch.
