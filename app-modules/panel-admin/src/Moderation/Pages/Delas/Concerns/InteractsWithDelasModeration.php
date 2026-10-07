<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages\Delas\Concerns;

use Closure;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\Support\Reason;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/**
 * O que as páginas da He4rt Delas no cluster de Moderação têm em comum: o grupo
 * na subnavegação, o cabeçalho com a logo, a tabela única e os campos e colunas
 * repetidos. Cada página decide o próprio acesso no `canAccess()`.
 */
trait InteractsWithDelasModeration
{
    public static function getNavigationGroup(): string
    {
        return __('panel-admin::delas.navigation.group');
    }

    public function getHeading(): Htmlable
    {
        return new HtmlString(view('panel-admin::moderation.delas.heading', [
            'label' => static::getNavigationLabel(),
        ])->render());
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    protected function isLead(): bool
    {
        return $this->actor()->can('lead-delas');
    }

    protected function personColumn(string $name, string $username, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->weight('medium')
            ->description(fn (mixed $record): string => '@'.data_get($record, $username))
            ->searchable([str_contains($name, '.') ? 'name' : $name]);
    }

    protected function dateColumn(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->dateTime('d/m/Y H:i')
            ->timezone(config('app.display_timezone'))
            ->sortable();
    }

    protected function reasonField(string $label, bool $required = true, ?string $hint = null): Textarea
    {
        return Textarea::make('reason')
            ->label($label)
            ->required($required)
            ->maxLength(Reason::MAX_LENGTH)
            ->rows(3)
            ->helperText($hint ?? __('panel-admin::delas.reason_hint'));
    }

    /**
     * Ao remover a tag, a líder pode impedir novos pedidos com o mesmo motivo.
     */
    protected function alsoBlockToggle(): Toggle
    {
        return Toggle::make('also_block')
            ->label(__('panel-admin::delas.actions.also_block'))
            ->helperText(__('panel-admin::delas.actions.also_block_hint'))
            ->default(state: false);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    protected function alsoBlockFrom(array $data): bool
    {
        return ($data['also_block'] ?? false) === true;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    protected function revokedTitle(array $data): string
    {
        return $this->text($this->alsoBlockFrom($data)
            ? 'panel-admin::delas.actions.revoked_and_blocked'
            : 'panel-admin::delas.actions.revoked');
    }

    /**
     * Roda uma action de domínio e transforma recusas em notificação, sem
     * derrubar a página.
     */
    protected function attempt(Closure $operation, string $successTitle): bool
    {
        try {
            $operation();
        } catch (DelasException|AuthorizationException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return false;
        }

        Notification::make()->success()->title($successTitle)->send();

        return true;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    protected function reasonFrom(array $data): ?string
    {
        $reason = $data['reason'] ?? null;

        return is_string($reason) ? $reason : null;
    }

    protected function text(string $key): string
    {
        $text = __($key);

        return is_string($text) ? $text : $key;
    }

    protected function actor(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
