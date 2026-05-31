@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Presentation Layer

Modules with prefix `panel-*` and the `portal` module are **presentation layer**. They own UI concerns: Filament Resources, Pages, Widgets, Livewire components, and Blade views.

## Rule

Domain logic (Actions, Models, DTOs, business rules) belongs in domain modules (`identity`, `moderation`, `economy`, etc.), never in presentation modules.

Presentation modules import from domain modules.
Domain modules never import from presentation modules.

## Livewire

Whenever you're doing something with presentation layer, activate your `livewire-specialist` skill.

## Filament Rules

Research about Filament 5.x before implementing using `search-docs` MCP tool or `context7` if available.

Whenever you're in Filament Pages, Resources, Widgets, or Livewire components, use the `use` statement to import domain classes. This ensures that your presentation layer remains decoupled from domain logic.

If you're using Filament Actions, create a new action class to match the Domain Action. This keeps your presentation layer focused on UI logic.

@verbatim
<code-snippet name="Filament Action wrapping a Domain Action" lang="php">
class RegisterSubscriptionsAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::subfeature.actions.some_action.title-or-whatever'))
            ->icon(Heroicon::PlusCircle)
            ->color(Color::Sky)
            ->modalHeading(__('panel-admin::subfeature.actions.some_action.title-or-whatever'))
            ->modalSubmitActionLabel(__('panel-admin::subfeature.actions.some_action'))
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalContent(function (): View {
                /** @var Tenant|null $tenant */
                $tenant = filament()->getTenant();

                return view('panel-admin::some-view', []);
            })
            ->action(function ($data): void {
                resolve(SomeAction::class)->execute(SomeDto::fromRequest($data));

                Notification::make()
                    ->success()
                    ->send();
            });
    }
}
</code-snippet>
@endverbatim
