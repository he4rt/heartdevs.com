<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Filament\Resources\Users\UserResource;
use He4rt\Profile\Models\Profile;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function mount(string|int $record): void
    {
        parent::mount($record);

        Profile::ensureExists((string) $record);
    }

    /**
     * @return array<int, ViewAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
