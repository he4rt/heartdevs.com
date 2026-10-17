<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Delas;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\TagRequest\Actions\RequestDelasTag;
use He4rt\Delas\TagRequest\Queries\DelasEligibility;
use He4rt\Delas\TagRequest\ValueObjects\DelasEligibilityResult;
use He4rt\Identity\User\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Seção He4rt Delas do perfil: pedir a tag (com pop-up de confirmação) e
 * acompanhar o status.
 *
 * @property-read DelasEligibilityResult $eligibility
 */
class DelasProfileSection extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[Computed]
    public function eligibility(): DelasEligibilityResult
    {
        return resolve(DelasEligibility::class)->for($this->user());
    }

    public function requestAction(): Action
    {
        return Action::make('request')
            ->label(__('panel-app::delas.profile.toggle'))
            ->modalHeading(__('panel-app::delas.profile.modal.heading'))
            ->modalContent(view('panel-app::livewire.delas.request-modal'))
            ->modalSubmitActionLabel(__('panel-app::delas.profile.modal.submit'))
            // Rosa claro com texto 900 (4,81:1): o Filament escolheria texto branco num tom mais escuro.
            ->modalSubmitAction(fn (Action $action): Action => $action->extraAttributes([
                'class' => 'bg-delas-primary! text-delas-900! hover:bg-delas-300! [&_*]:text-delas-900!',
            ]))
            ->modalCancelActionLabel(__('panel-app::delas.profile.modal.cancel'))
            ->modalWidth(Width::Large)
            ->visible(fn (): bool => $this->eligibility->canRequest())
            ->action(function (): void {
                try {
                    resolve(RequestDelasTag::class)->handle($this->user());
                } catch (DelasException $delasException) {
                    Notification::make()->danger()->title($delasException->getMessage())->send();

                    return;
                } finally {
                    unset($this->eligibility);
                }

                Notification::make()
                    ->success()
                    ->title(__('panel-app::delas.profile.sent'))
                    ->body(__('panel-app::delas.profile.sent_body'))
                    ->send();

                $this->dispatch('delas-tag-updated');
            });
    }

    public function render(): View
    {
        return view('panel-app::livewire.delas.profile-section');
    }

    private function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
