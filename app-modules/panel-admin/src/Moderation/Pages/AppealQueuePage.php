<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Moderation\ModerationCluster;

class AppealQueuePage extends Page
{
    protected static ?string $cluster = ModerationCluster::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'appeals-queue';

    protected string $view = 'panel-admin::moderation.appeal-queue';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::moderation.navigation.appeals_queue');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::moderation.navigation.group_moderation');
    }
}
