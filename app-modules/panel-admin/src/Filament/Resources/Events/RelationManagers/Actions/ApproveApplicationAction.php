<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use He4rt\Events\Enrollment\Actions\ApproveApplicationAction as ApproveApplicationDomainAction;
use He4rt\Events\Enrollment\DTOs\ApproveApplicationDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;

final class ApproveApplicationAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('events::pages.admin_approve_application'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Enrollment $record): bool => $record->status === EnrollmentStatus::Pending)
            ->requiresConfirmation()
            ->modalHeading(__('events::pages.admin_approve_application_modal_heading'))
            ->modalDescription(fn (Enrollment $record): string => __('events::pages.admin_approve_application_modal_description', ['name' => $record->user?->name]))
            ->action(static function (Enrollment $record): void {
                try {
                    resolve(ApproveApplicationDomainAction::class)->handle(
                        new ApproveApplicationDTO(
                            enrollmentId: $record->id,
                            actorId: (string) auth()->id(),
                        ),
                    );

                    Notification::make()
                        ->success()
                        ->title(__('events::pages.admin_approve_application_success'))
                        ->send();
                } catch (EnrollmentException $enrollmentException) {
                    Notification::make()
                        ->danger()
                        ->title($enrollmentException->getMessage())
                        ->send();
                }
            });
    }

    public static function getDefaultName(): string
    {
        return 'approveApplication';
    }
}
