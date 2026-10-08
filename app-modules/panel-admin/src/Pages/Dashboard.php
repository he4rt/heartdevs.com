<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Moderation\ModerationCluster;

class Dashboard extends BaseDashboard
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    /**
     * Quem só modera (não é super admin) não tem nada no Dashboard: os widgets
     * são de super admin. Para essa pessoa, a casa do admin é a Moderação.
     */
    public static function isModerationHome(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && !$user->isSuperAdmin()
            && ModerationCluster::canAccessClusteredComponents();
    }

    public function mount(): void
    {
        if (static::isModerationHome()) {
            $this->redirect(ModerationCluster::getUrl());
        }
    }
}
