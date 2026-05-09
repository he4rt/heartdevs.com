<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use BackedEnum;
use Filament\Pages\Page;
use He4rt\Activity\Timeline\Timeline;

class ThreadPage extends Page
{
    public string $record;

    protected static string|null|BackedEnum $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'timeline/{record}';

    protected static ?string $title = 'Thread';

    protected string $view = 'panel-app::pages.thread';

    public function mount(string $record): void
    {
        $this->record = $record;
    }

    public function getTimeline(): Timeline
    {
        return Timeline::query()
            ->where('id', $this->record)
            ->whereNull('parent_id')
            ->with(['user', 'postable', 'reactions'])
            ->withCount('children', 'reactions')
            ->firstOrFail();
    }

    public function getTitle(): string
    {
        return 'Thread';
    }
}
