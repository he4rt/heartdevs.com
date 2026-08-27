<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use BackedEnum;
use Filament\Pages\Page;
use He4rt\Events\Event\Models\Event;

class EventPage extends Page
{
    public string $record;

    protected static string|null|BackedEnum $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'events/{record}';

    protected static ?string $title = 'Event';

    protected string $view = 'panel-app::pages.event';

    public function mount(string $record): void
    {
        $exists = Event::query()
            ->where('id', $record)
            ->viewableByParticipant()
            ->exists();

        abort_unless($exists, 404);

        $this->record = $record;
    }
}
