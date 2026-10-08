<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas\Concerns;

use Closure;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\Support\Reason;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * O que as actions da He4rt Delas no admin têm em comum: quem age, o campo de
 * motivo e o jeito de rodar uma action de domínio sem derrubar a página.
 */
trait InteractsWithDelasActions
{
    protected function actor(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    protected function isLead(): bool
    {
        return $this->actor()->can('lead-delas');
    }

    protected function reasonField(string $label, bool|Closure $required = true, ?string $hint = null): Textarea
    {
        return Textarea::make('reason')
            ->label($label)
            ->required($required)
            ->maxLength(Reason::MAX_LENGTH)
            ->rows(3)
            ->helperText($hint ?? __('panel-admin::delas.reason_hint'));
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    protected function reasonFrom(array $data): ?string
    {
        $reason = $data['reason'] ?? null;

        return is_string($reason) ? $reason : null;
    }

    /**
     * Roda a action de domínio e transforma uma recusa (regra de negócio ou
     * permissão) em notificação, sem derrubar a página.
     */
    protected function runDomainAction(Closure $operation, string $successTitle): void
    {
        try {
            $operation();
        } catch (DelasException|AuthorizationException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        Notification::make()->success()->title($successTitle)->send();
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    protected function text(string $key, array $replace = []): string
    {
        $text = __($key, $replace);

        return is_string($text) ? $text : $key;
    }
}
