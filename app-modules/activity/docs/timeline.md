# Timeline

The timeline is a social feed on the `/app` panel. It displays two kinds of entries: user-authored posts (`post_entry`) and system-generated moderation events (`moderation_event`). New entry types can be added without modifying existing code.

## Architecture

```
activity_timeline (polymorphic hub)
├── postable_type: "post_entry"      → PostEntry model (user content)
├── postable_type: "moderation_event" → ModerationEvent model (system)
└── postable_type: "your_new_type"   → YourModel (future)
```

The `Timeline` model uses a polymorphic `postable` relation (`postable_type` + `postable_id`). Each entry type is a separate model registered in the morph map. The feed query, Livewire components, and Blade views delegate rendering by `postable_type`.

### Key files

| Layer    | File                                                     | Purpose                                 |
| -------- | -------------------------------------------------------- | --------------------------------------- |
| Model    | `src/Timeline/Timeline.php`                              | Hub model with `postable()` morphTo     |
| Model    | `src/Timeline/Delegated/PostEntry.php`                   | User post content + media               |
| Model    | `src/Moderation/Models/ModerationEvent.php`              | Moderation event data                   |
| Action   | `src/Timeline/Actions/CreatePost.php`                    | Creates PostEntry + Timeline            |
| Action   | `src/Timeline/Actions/CreateReply.php`                   | Creates reply (1-level flat)            |
| Action   | `src/Timeline/Actions/TogglePinPost.php`                 | Pin/unpin own posts                     |
| Action   | `src/Timeline/Actions/PublishModerationEntry.php`        | ModerationEvent → Timeline              |
| Listener | `src/Timeline/Listeners/PublishModerationToTimeline.php` | ActionExecuted → ModerationEvent        |
| Query    | `src/Timeline/Queries/TimelineFeed.php`                  | Feed builder (tenant, ignored, replies) |
| DTO      | `src/Timeline/DTOs/CreatePostDTO.php`                    | Input for CreatePost                    |
| DTO      | `src/Timeline/DTOs/CreateReplyDTO.php`                   | Input for CreateReply                   |
| Provider | `ActivityServiceProvider.php`                            | MorphMap, observers, listeners          |

### UI files (panel-app module)

| File                                                   | Purpose                                |
| ------------------------------------------------------ | -------------------------------------- |
| `Livewire/Timeline/Feed.php`                           | Feed with infinite scroll              |
| `Livewire/Timeline/PostShow.php`                       | Individual post with pin               |
| `Livewire/Timeline/Composer.php`                       | Inline post composer                   |
| `views/livewire/timeline/feed.blade.php`               | Feed layout, routes by `postable_type` |
| `views/components/timeline/header.blade.php`           | Post header (avatar, name, time)       |
| `views/components/timeline/post-entry.blade.php`       | Post content + images                  |
| `views/components/timeline/moderation-event.blade.php` | Moderation card                        |
| `views/components/timeline/engagement.blade.php`       | Reply/reaction/view counts, pin        |

## Adding a new entry type

### Step 1: Create the model

Create your model in the appropriate module. It needs a UUID primary key to match the `activity_timeline.postable_id` column.

```php
// app-modules/your-module/src/Models/BadgeAward.php

final class BadgeAward extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'badge_name', 'awarded_at'];
}
```

### Step 2: Register in the morph map

Add the alias in `ActivityServiceProvider::boot()`:

```php
Relation::morphMap([
    // existing...
    'badge_award' => BadgeAward::class,
]);
```

### Step 3: Create the timeline entry

When the event happens (badge earned, level up, etc.), create a `Timeline` row pointing to your model:

```php
Timeline::query()->create([
    'user_id' => $user->id,
    'tenant_id' => $tenantId,
    'postable_type' => 'badge_award',
    'postable_id' => $badgeAward->id,
]);
```

For cross-module events, use a listener (like `PublishModerationToTimeline` listens for `ActionExecuted`):

```php
// In ActivityServiceProvider::boot()
Event::listen(BadgeAwarded::class, [PublishBadgeToTimeline::class, 'handle']);
```

### Step 4: Create the Blade component

Create a Blade component at `panel-app/resources/views/components/timeline/badge-award.blade.php`:

```blade
@props (['timeline'])

@php
    $award = $timeline->postable;
@endphp

<div class="rounded-xl border ...">
    {{-- your card design --}}
</div>
```

System-generated entries (no user interaction) should be pure Blade like this. If the card needs interactivity (like, reply), wrap it in a Livewire component instead.

### Step 5: Register in the feed view

Add your type to `feed.blade.php`:

```blade
@if ($item->postable_type === 'moderation_event')
    <x-panel-app::timeline.moderation-event :timeline="$item" />
@elseif ($item->postable_type === 'badge_award')
    <x-panel-app::timeline.badge-award :timeline="$item" />
@else
    <livewire:timeline-post-show ... />
@endif
```

That's it. Five steps: model, morph map, creation logic, Blade component, feed routing.

## Important constraints

### Threading

Threading is 1-level flat. All replies point to the root post:

```
root_id  = original_post.id
parent_id = original_post.id  (always equals root_id)
```

Replying to a reply flattens — both `root_id` and `parent_id` point to the original root, never to the intermediate reply. The `CreateReply` action enforces this.

### Tenant scoping

Every timeline entry has a `tenant_id`. The `TimelineFeed` query filters by tenant. When creating entries, always pass the correct tenant — entries without a tenant won't appear in any feed.

### Pinning

Users can pin one post per tenant. Pinning a new post unpins the previous one automatically. Only the post owner can pin. `TogglePinPost` enforces both rules.

### Moderation event flow

Two sources create `ModerationEvent` records:

1. **Discord ETL** — imports bans/kicks from Discord directly as `ModerationEvent`
2. **Web admin panel** — moderator judges a `ModerationCase`, executes action → `ActionExecuted` event → `PublishModerationToTimeline` listener creates a `ModerationEvent`

Both paths converge on the same model. The `ModerationEvent::created` observer then creates the timeline entry via `PublishModerationEntry`. Only `Ban` and `Kick` types are published — `Warn`, `Mute`, etc. are filtered out.

### Blade vs Livewire rendering

- **Interactive entries** (user posts with pin, replies, reactions) → Livewire component (`PostShow`)
- **System entries** (moderation events, future badges/milestones) → pure Blade component (no Livewire overhead)

Each Livewire component on the page costs ~2-4KB in the HTML snapshot. System events that have no interactivity should always be pure Blade.

### Feed query

`TimelineFeed` excludes replies (`whereNull('parent_id')`) and ignored entries (`is_ignored = false`). It orders by `created_at DESC` and uses `simplePaginate(15)` to avoid COUNT queries. The feed Livewire component uses `HasLoadMore` trait for infinite scroll, capped at 100 items.
