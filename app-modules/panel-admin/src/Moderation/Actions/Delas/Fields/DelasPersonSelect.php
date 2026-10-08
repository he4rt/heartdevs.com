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
 * Seletor de pessoa das actions da liderança. Abre com as primeiras pessoas do
 * escopo (o padrão de Select do projeto) e busca o resto digitando. Só aparece
 * (e só ganha rótulo) quem a consulta de domínio `DelasCandidates` devolve.
 */
final class DelasPersonSelect
{
    /**
     * @param  Closure(DelasCandidates): Builder<User>  $scope  um dos escopos de `DelasCandidates`
     */
    public static function make(Closure $scope): Select
    {
        $candidates = resolve(DelasCandidates::class);
        $people = fn (): Builder => $scope($candidates);

        return Select::make('user_id')
            ->label(__('panel-admin::delas.actions.person'))
            ->helperText(__('panel-admin::delas.actions.person_hint'))
            ->required()
            ->searchable()
            ->options(fn (): array => self::options($candidates->search($people(), '')))
            ->getSearchResultsUsing(fn (string $search): array => self::options($candidates->search($people(), $search)))
            ->getOptionLabelUsing(fn (?string $value): ?string => ($user = $people()->find($value)) instanceof User
                ? self::label($user)
                : null);
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
