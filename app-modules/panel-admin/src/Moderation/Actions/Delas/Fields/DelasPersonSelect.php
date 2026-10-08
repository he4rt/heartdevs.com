<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas\Fields;

use Closure;
use Filament\Forms\Components\Select;
use He4rt\Delas\Team\Queries\DelasCandidates;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Seletor de pessoa das actions da liderança. Só aparece (e só ganha rótulo)
 * quem a consulta de domínio `DelasCandidates` devolve.
 *
 * Listas pequenas (quem tem a tag) abrem já com as primeiras pessoas; a
 * comunidade inteira só por busca, para a lista não parecer completa.
 */
final class DelasPersonSelect
{
    /**
     * @param  Closure(DelasCandidates): Builder<User>  $scope  um dos escopos de `DelasCandidates`
     * @param  bool  $opensWithList  falso para listas do tamanho da comunidade: aí é só digitar
     */
    public static function make(Closure $scope, bool $opensWithList = true): Select
    {
        $candidates = resolve(DelasCandidates::class);
        $people = fn (): Builder => $scope($candidates);

        $select = Select::make('user_id')
            ->label(__('panel-admin::delas.actions.person'))
            ->helperText(__('panel-admin::delas.actions.person_hint'))
            ->searchPrompt(__('panel-admin::delas.actions.person_search_prompt'))
            ->required()
            ->searchable()
            // O projeto pré-carrega todo Select por padrão; aqui a decisão é por lista.
            ->preload($opensWithList)
            ->getSearchResultsUsing(fn (string $search): array => self::options($candidates->search($people(), $search)))
            ->getOptionLabelUsing(fn (?string $value): ?string => ($user = $people()->find($value)) instanceof User
                ? self::label($user)
                : null);

        if ($opensWithList) {
            $select->options(fn (): array => self::options($candidates->search($people(), '')));
        }

        return $select;
    }

    /**
     * A pessoa escolhida no formulário da action.
     *
     * @param  array<array-key, mixed>  $data  os dados do formulário, como o Filament entrega
     */
    public static function chosen(array $data): User
    {
        $id = $data['user_id'] ?? null;

        return User::query()->whereKey(is_string($id) ? $id : null)->firstOrFail();
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<string, string>
     */
    private static function options(Collection $users): array
    {
        return $users->mapWithKeys(fn (User $user): array => [$user->id => self::label($user)])->all();
    }

    private static function label(User $user): string
    {
        return $user->name.' (@'.$user->username.')';
    }
}
