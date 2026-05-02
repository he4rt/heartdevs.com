<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Moderation\ModerationCluster;

class ModerationQueuePage extends Page
{
    protected static ?string $cluster = ModerationCluster::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'queue';

    protected string $view = 'panel-admin::moderation.queue';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::moderation.navigation.queue');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::moderation.navigation.group_moderation');
    }
}
