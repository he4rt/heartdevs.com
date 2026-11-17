# Templates

The examples use "an e-commerce for independent coffee roasters". Replace every line with confirmed facts.

## PRODUCT.md

The impeccable product schema, version 1. Copy the marker comment as it is. Its number changes only when this template changes.

`## Stack` is left out: in He4rt the codebase already answers it.

```markdown
# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Owners of small coffee roasteries who sell beans online without a developer. They update the catalog between
roasts, a few times a week, often from a phone in the roastery.

## Product Purpose

A roaster opens a shop in an afternoon and sells fresh roasts by subscription and one-off orders. Success: the
first paid order in the first week.

## Positioning

Built around the roast date: a bag is sold as "roasted on", not as stock that waits on a shelf.

## Operating Context

Orders arrive in batches after each roast. The owner packs and ships the same day and prints labels from the
order list.

## Capabilities and Constraints

- Catalog of beans with roast dates, sizes and grind options.
- Cart and checkout, one-off and subscription.
- Payments through one provider. Undecided: which provider.
- Not in the first release: shipping carriers, reviews.
- A shop is one Team. A customer never sees another shop's data.

## Brand Commitments

Omit this section when the product has no binding name, voice or assets yet.

## Evidence on Hand

- No customers, prices or metrics yet. Do not invent them.

## Product Principles

1. Freshness before volume: never sell a roast older than the shop allows.
2. One person runs the shop: every daily task fits on a phone.
3. The owner keeps the customer: we never contact a shop's customers directly.

## Accessibility & Inclusion

Omit this section when the product has no requirement beyond the WCAG 2.1 AA baseline.
```

## CONTEXT-MAP.md

Every module, planned or real. Keep the modules that exist today in the table.

```markdown
# Context Map

| Module                                            | Type         | Status  | Owns                                | Depends on        | Phase |
| ------------------------------------------------- | ------------ | ------- | ----------------------------------- | ----------------- | ----- |
| [identity](app-modules/identity/CONTEXT.md)       | domain       | active  | user, team, role, external identity | -                 | -     |
| [panel-admin](app-modules/panel-admin/CONTEXT.md) | presentation | active  | -                                   | identity          | -     |
| [panel-app](app-modules/panel-app/CONTEXT.md)     | presentation | active  | -                                   | identity          | -     |
| catalog                                           | domain       | planned | product, variant, roast             | identity          | 1     |
| orders                                            | domain       | planned | cart, order, order line             | catalog, identity | 1     |
| integration-stripe                                | integration  | planned | payment, refund                     | orders            | 2     |

## Boundaries

- A catalog **product** has a current price. An order **line** keeps the price at the time of sale, and never
  reads the catalog price again.
- The cart lives in `orders`, not in its own module: it has no lifecycle apart from becoming an order.
```

Statuses: `planned` (a row only, no folder), `active` (scaffolded). A link to `CONTEXT.md` appears when the module exists.

## docs/roadmap.md

Phases in order. A phase has a goal a user can see, its modules, and the check that closes it.

```markdown
# Roadmap

## Phase 1: sell a bag of coffee (started)

- Goal: a roaster lists a bean and a customer places an order.
- Modules: `catalog` (new), `orders` (new), `panel-app` (catalog and order screens).
- Done when: an order goes from placed to shipped by hand, without payment.

## Phase 2: take payment (planned)

- Goal: the customer pays at checkout.
- Modules: `integration-stripe` (new), `orders` (payment states).
- Done when: a paid order and a refund show in the order list.

## Later

- Shipping carriers and tracking.
- Reviews. Cut from the first release; ask again after phase 2.
```

Statuses: `planned`, `started`, `done`. Never reorder a phase that started.
