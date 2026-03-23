<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Feedback\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use He4rt\Community\Feedback\Enums\ReviewTypeEnum;
use He4rt\PanelAdmin\Filament\Resources\Feedback\FeedbackResource;

class EditFeedback extends EditRecord
{
    protected static string $resource = FeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon(Heroicon::Check)
                ->color('success')
                ->action(function (): void {
                    $this->record->review()->updateOrCreate(
                        ['feedback_id' => $this->record->id],
                        [
                            'tenant_id' => $this->record->tenant_id,
                            'staff_id' => auth()->id(),
                            'status' => ReviewTypeEnum::APPROVED,
                            'received_at' => now(),
                        ]
                    );
                })
                ->successNotificationTitle('Feedback approved'),

            Action::make('decline')
                ->label('Decline')
                ->icon(Heroicon::XMark)
                ->color('danger')
                ->form([
                    Textarea::make('reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->review()->updateOrCreate(
                        ['feedback_id' => $this->record->id],
                        [
                            'tenant_id' => $this->record->tenant_id,
                            'staff_id' => auth()->id(),
                            'status' => ReviewTypeEnum::DECLINED,
                            'reason' => $data['reason'],
                            'received_at' => now(),
                        ]
                    );
                })
                ->successNotificationTitle('Feedback declined'),

            DeleteAction::make(),
        ];
    }
}
