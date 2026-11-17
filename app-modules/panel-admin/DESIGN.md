---
version: alpha
name: He4rt Hybrid
description: A calm, warm frame around dense, precise data. The brand lives in the accent, the logo mark and the heading font; everything else is the neutral base.
colors:
    background: 'oklch(0.972 0.006 75)'
    foreground: 'oklch(0.215 0.012 60)'
    card: 'oklch(0.995 0.003 75)'
    card-foreground: 'oklch(0.215 0.012 60)'
    popover: 'oklch(0.995 0.003 75)'
    popover-foreground: 'oklch(0.215 0.012 60)'
    primary: 'oklch(0.47 0.17 300)'
    primary-foreground: 'oklch(0.985 0.006 75)'
    secondary: 'oklch(0.928 0.009 75)'
    secondary-foreground: 'oklch(0.27 0.012 60)'
    muted: 'oklch(0.945 0.008 75)'
    muted-foreground: 'oklch(0.48 0.014 65)'
    accent: 'oklch(0.935 0.01 75)'
    accent-foreground: 'oklch(0.215 0.012 60)'
    destructive: 'oklch(0.53 0.2 27)'
    success: 'oklch(0.5 0.12 152)'
    warning: 'oklch(0.52 0.115 68)'
    border: 'oklch(0.9 0.01 75)'
    input: 'oklch(0.64 0.014 70)'
    ring: 'oklch(0.58 0.17 300)'
    chart-1: 'oklch(0.85 0.07 300)'
    chart-2: 'oklch(0.74 0.12 300)'
    chart-3: 'oklch(0.62 0.16 300)'
    chart-4: 'oklch(0.47 0.17 300)'
    chart-5: 'oklch(0.37 0.14 300)'
    sidebar: 'oklch(0.955 0.009 75)'
    sidebar-foreground: 'oklch(0.215 0.012 60)'
    sidebar-primary: 'oklch(0.47 0.17 300)'
    sidebar-primary-foreground: 'oklch(0.985 0.006 75)'
    sidebar-accent: 'oklch(0.915 0.011 75)'
    sidebar-accent-foreground: 'oklch(0.215 0.012 60)'
    sidebar-border: 'oklch(0.895 0.01 75)'
    sidebar-ring: 'oklch(0.58 0.17 300)'
    dark-background: 'oklch(0.19 0.008 60)'
    dark-foreground: 'oklch(0.93 0.008 75)'
    dark-card: 'oklch(0.232 0.009 60)'
    dark-card-foreground: 'oklch(0.93 0.008 75)'
    dark-popover: 'oklch(0.278 0.01 60)'
    dark-popover-foreground: 'oklch(0.93 0.008 75)'
    dark-primary: 'oklch(0.76 0.12 300)'
    dark-primary-foreground: 'oklch(0.19 0.012 60)'
    dark-secondary: 'oklch(0.3 0.01 60)'
    dark-secondary-foreground: 'oklch(0.93 0.008 75)'
    dark-muted: 'oklch(0.278 0.01 60)'
    dark-muted-foreground: 'oklch(0.75 0.012 70)'
    dark-accent: 'oklch(0.325 0.011 60)'
    dark-accent-foreground: 'oklch(0.95 0.008 75)'
    dark-destructive: 'oklch(0.78 0.12 25)'
    dark-success: 'oklch(0.77 0.13 152)'
    dark-warning: 'oklch(0.81 0.12 75)'
    dark-border: 'oklch(0.95 0.01 75 / 9%)'
    dark-input: 'oklch(0.95 0.01 75 / 40%)'
    dark-ring: 'oklch(0.68 0.14 300)'
    dark-chart-1: 'oklch(0.88 0.06 300)'
    dark-chart-2: 'oklch(0.78 0.11 300)'
    dark-chart-3: 'oklch(0.68 0.15 300)'
    dark-chart-4: 'oklch(0.58 0.17 300)'
    dark-chart-5: 'oklch(0.48 0.16 300)'
    dark-sidebar: 'oklch(0.16 0.008 60)'
    dark-sidebar-foreground: 'oklch(0.93 0.008 75)'
    dark-sidebar-primary: 'oklch(0.76 0.12 300)'
    dark-sidebar-primary-foreground: 'oklch(0.19 0.012 60)'
    dark-sidebar-accent: 'oklch(0.245 0.01 60)'
    dark-sidebar-accent-foreground: 'oklch(0.95 0.008 75)'
    dark-sidebar-border: 'oklch(0.95 0.01 75 / 8%)'
    dark-sidebar-ring: 'oklch(0.68 0.14 300)'
typography:
    headline-page:
        fontFamily: Bricolage Grotesque
        fontSize: 28px
        fontWeight: 600
        lineHeight: 34px
        letterSpacing: -0.02em
    headline-stat:
        fontFamily: Bricolage Grotesque
        fontSize: 32px
        fontWeight: 600
        lineHeight: 36px
        letterSpacing: -0.02em
        fontFeature: '"tnum" 1'
    headline-dialog:
        fontFamily: Bricolage Grotesque
        fontSize: 16px
        fontWeight: 600
        lineHeight: 20px
        letterSpacing: -0.01em
    body-md:
        fontFamily: Inter
        fontSize: 14px
        fontWeight: 400
        lineHeight: 20px
    body-data:
        fontFamily: Inter
        fontSize: 13px
        fontWeight: 400
        lineHeight: 18px
    label-sm:
        fontFamily: Inter
        fontSize: 12px
        fontWeight: 500
        lineHeight: 16px
    mono-id:
        fontFamily: JetBrains Mono
        fontSize: 12px
        fontWeight: 400
        lineHeight: 18px
        fontFeature: '"tnum" 1'
rounded:
    sm: 4.8px
    md: 6.4px
    lg: 8px
    xl: 11.2px
    2xl: 14.4px
    full: 9999px
spacing:
    shell-gutter: 40px
    page-header-gap: 32px
    card: 20px
    data-head-height: 32px
    data-cell-x: 12px
    data-cell-y: 4px
    field-height: 32px
components:
    button-primary:
        backgroundColor: '{colors.primary}'
        textColor: '{colors.primary-foreground}'
        typography: '{typography.body-md}'
        rounded: '{rounded.lg}'
        height: 32px
    input:
        backgroundColor: '{colors.card}'
        textColor: '{colors.foreground}'
        typography: '{typography.body-data}'
        rounded: '{rounded.lg}'
        height: '{spacing.field-height}'
    table-head:
        textColor: '{colors.muted-foreground}'
        typography: '{typography.label-sm}'
        height: '{spacing.data-head-height}'
        padding: '{spacing.data-cell-x}'
    table-cell:
        textColor: '{colors.foreground}'
        typography: '{typography.body-data}'
        padding: '{spacing.data-cell-y}'
    table-cell-identifier:
        textColor: '{colors.muted-foreground}'
        typography: '{typography.mono-id}'
    card-stat:
        backgroundColor: '{colors.card}'
        textColor: '{colors.card-foreground}'
        typography: '{typography.headline-stat}'
        rounded: '{rounded.xl}'
        padding: '{spacing.card}'
    page-title:
        textColor: '{colors.foreground}'
        typography: '{typography.headline-page}'
    sidebar:
        backgroundColor: '{colors.sidebar}'
        textColor: '{colors.sidebar-foreground}'
        typography: '{typography.body-md}'
    toast:
        backgroundColor: '{colors.popover}'
        textColor: '{colors.popover-foreground}'
        rounded: '{rounded.lg}'
    toast-success:
        backgroundColor: '{colors.popover}'
        textColor: '{colors.success}'
    toast-error:
        backgroundColor: '{colors.popover}'
        textColor: '{colors.destructive}'
    sidebar-item-active:
        backgroundColor: '{colors.sidebar-accent}'
        textColor: '{colors.sidebar-accent-foreground}'
    badge-secondary:
        backgroundColor: '{colors.secondary}'
        textColor: '{colors.secondary-foreground}'
    text-muted:
        backgroundColor: '{colors.muted}'
        textColor: '{colors.muted-foreground}'
    button-primary-dark:
        backgroundColor: '{colors.dark-primary}'
        textColor: '{colors.dark-primary-foreground}'
    surface-dark:
        backgroundColor: '{colors.dark-background}'
        textColor: '{colors.dark-foreground}'
    card-dark:
        backgroundColor: '{colors.dark-card}'
        textColor: '{colors.dark-card-foreground}'
    popover-dark:
        backgroundColor: '{colors.dark-popover}'
        textColor: '{colors.dark-popover-foreground}'
    text-muted-dark:
        backgroundColor: '{colors.dark-muted}'
        textColor: '{colors.dark-muted-foreground}'
    badge-secondary-dark:
        backgroundColor: '{colors.dark-secondary}'
        textColor: '{colors.dark-secondary-foreground}'
    toast-success-dark:
        backgroundColor: '{colors.dark-popover}'
        textColor: '{colors.dark-success}'
    toast-error-dark:
        backgroundColor: '{colors.dark-popover}'
        textColor: '{colors.dark-destructive}'
    sidebar-dark:
        backgroundColor: '{colors.dark-sidebar}'
        textColor: '{colors.dark-sidebar-foreground}'
    sidebar-item-active-dark:
        backgroundColor: '{colors.dark-sidebar-accent}'
        textColor: '{colors.dark-sidebar-accent-foreground}'
    sidebar-primary-dark:
        backgroundColor: '{colors.dark-sidebar-primary}'
        textColor: '{colors.dark-sidebar-primary-foreground}'
    accent-dark:
        backgroundColor: '{colors.dark-accent}'
        textColor: '{colors.dark-accent-foreground}'
---

# He4rt Hybrid

## Overview

The look of the He4rt Hub has two jobs: give the panels a base with character, and keep the brand in a few tokens, so a brand change touches only them.

The idea is **a calm frame around dense data**. The frame (sidebar, header, page title, cards) uses warm stone neutrals, a display heading and room to breathe. Inside it, the data (tables and forms) is dense and precise: 13px text, 36px rows, monospace for record identifiers. The frame orients, the data works.

It is a product register: a work tool for people who spend the day in the panel, not a marketing page.

## Colors

Warm stone neutrals with low chroma (0.003 to 0.014, hue 60 to 75) replace the cold zinc of the stock theme. There is one accent.

- **Stone canvas (`background`):** the floor of the frame. Off-white in light, a warm charcoal in dark, never pure white or pure black.
- **Ink (`foreground`):** warm near-black and warm near-white. 15:1 or more on every surface.
- **Smoke (`muted-foreground`):** column headers, descriptions, dates. 5.6:1 or more on every surface.
- **Violet (`primary`):** the default accent, the same hue as the old purple with less chroma. Only for the primary action, the brand mark and focus.
- **Warm steel (`input`):** the border of a control, 3:1 against its surface. It is not `border`: `border` and `sidebar-border` are decorative hairlines (1.2 to 1.3:1). The frame whispers, the control has a firm edge.
- **Semantic (`destructive`, `success`, `warning`):** 4.5:1 or more as text in both themes.

### The brand slots

The brand changes through these tokens only: `primary`, `sidebar-primary`, `ring`, `sidebar-ring`, `chart-1` to `chart-5`, and the heading font. `primary-foreground` stays, because it passes 6.6:1 on violet, blue and green accents. The other 27 slots are the neutral base.

Tested accents (light / dark `primary`): violet `oklch(0.47 0.17 300)` / `oklch(0.76 0.12 300)`, blue `oklch(0.47 0.14 255)` / `oklch(0.76 0.11 250)`, green `oklch(0.45 0.1 160)` / `oklch(0.77 0.12 160)`. A new accent keeps lightness 0.45 to 0.47 in light and 0.76 to 0.77 in dark, so the contrast holds.

### Dark mode

Dark mode is not the light theme inverted. Tokens prefixed `dark-` are the `.dark` values of the same slot. Luminance rises toward the data, in four steps:

| Layer                           | Slot              | Lightness |
| ------------------------------- | ----------------- | --------- |
| Frame (recessed)                | `dark-sidebar`    | 0.160     |
| Canvas                          | `dark-background` | 0.190     |
| Surface (tables, cards)         | `dark-card`       | 0.232     |
| Raised (dialogs, menus, toasts) | `dark-popover`    | 0.278     |

The dark neutrals are warm charcoal, not blue-grey.

## Typography

Three roles, three fonts: **display names the screen, mono identifies a record, sans does the rest.**

- **Bricolage Grotesque** (`font-heading`, a brand slot): page titles (28px), stat numbers (32px, tabular) and dialog titles (16px), weight 600, tight tracking.
- **Inter** (`font-sans`): the frame at 14px (navigation, header, descriptions, buttons) and the data at 13px (table cells, inputs, field labels). Column headers and badges are 12px, weight 500.
- **JetBrains Mono** (`font-mono`): record identifiers in a table cell, such as an email, a date or a role slug, at 12px with tabular figures.

The jump from a 28px title to 13px data, with no step between, is the contrast of the direction. Use weights 400, 500 and 600 only, and 600 only in the display font.

## Layout

The structure does not change: sidebar on the left, a 56px header, then the page. The rhythm does.

- The frame uses wide steps: 40px page gutter on large screens, 32px under the page title, 20px inside a card.
- The data uses tight steps: 32px column headers, 12px cell padding, 4px vertical cell padding (36px rows), 32px inputs.

## Elevation & Depth

Depth comes from tonal layers, not from shadows. A table or a card sits on `card`, one step lighter than the canvas. Dialogs, menus and toasts sit on `popover`, the highest step, with a 1px hairline ring and a short, soft shadow. There is no zebra striping and no vertical rule in a table; a row has a 1px `border` below it.

## Shapes

`--radius` is 8px. The scale derives from it: 4.8px for small controls, 6.4px for sidebar items, 8px for buttons and inputs, 11.2px for cards and dialogs, 14.4px for large containers.

## Components

The components keep the anatomy of shadcn `base-nova`. Only tokens change.

- **Page title:** `headline-page`, on every `h1`, `h2` and `h3`.
- **Stat card:** the value uses `headline-stat`; the label is 13px `muted-foreground`.
- **Table:** `table-head` and `table-cell`. A column that holds a record identifier (email, date, slug) uses `table-cell-identifier`.
- **Input:** a 32px control with the `input` border at 3:1 and a 3px `ring` on focus.
- **Toast:** `popover` surface. A success or error toast tints only its text, icon and border, never its background, so the dark theme does not turn olive.
- **Impersonation pill:** `popover` surface with a `warning` border and icon.

## Do's and Don'ts

- Do use `primary` only for the main action of a screen, the brand mark and focus.
- Do put a record identifier in mono, and a screen name in the display font.
- Do keep the warm hue (60 to 75) in every new neutral.
- Don't add a colour slot when a tonal layer that exists can carry the hierarchy.
- Don't tint a toast or card background with a semantic colour.
- Don't use weight 700, or 600 outside the display font.
- Don't change component anatomy to get a look. If a look does not fit in tokens, it is the wrong look.
