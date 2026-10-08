<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages\Delas\Concerns;

use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Moderation\Actions\Delas\ViewDelasProfileAction;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * O que as páginas da He4rt Delas no cluster de Moderação têm em comum: o grupo
 * na subnavegação, o cabeçalho com a logo, a tabela única e as colunas
 * repetidas. Cada página decide o próprio acesso no `canAccess()`, e as ações
 * moram em `Moderation\Actions\Delas`.
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

    protected function actor(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * Nome, @username e "Ver perfil". Clicar na célula abre o resumo do perfil.
     *
     * @param  string  $name  `name` quando a linha é a pessoa; `user.name` quando ela vem pela relação `user`
     */
    protected function personColumn(string $name, string $username, string $label): TextColumn
    {
        $relation = str_contains($name, '.') ? Str::before($name, '.') : null;

        return TextColumn::make($name)
            ->label($label)
            ->weight('medium')
            ->description(fn (mixed $record): HtmlString => new HtmlString(
                e('@'.data_get($record, $username)).' · '.$this->clickableHint('panel-admin::delas.actions.view_profile'),
            ))
            ->action(ViewDelasProfileAction::make()->personRelation($relation))
            ->searchable([$relation === null ? $name : 'name']);
    }

    protected function dateColumn(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->dateTime('d/m/Y H:i')
            ->timezone(config('app.display_timezone'))
            ->sortable();
    }

    /**
     * Texto sublinhado na descrição de uma coluna, avisando que clicar na célula
     * abre algo. O clique em si é a action da coluna.
     */
    protected function clickableHint(string $key): string
    {
        return '<span class="cursor-pointer underline">'.e($this->text($key)).'</span>';
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
