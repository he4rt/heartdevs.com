<?php

declare(strict_types=1);

namespace He4rt\Delas\Team\Queries;

use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Quem pode ser escolhida nos seletores de pessoa da liderança: conceder a tag,
 * remover a tag e adicionar moderadora. A própria pessoa que age nunca aparece.
 *
 * Os escopos só filtram a lista; quem decide se a ação é válida continuam sendo
 * as actions de domínio.
 */
final readonly class DelasCandidates
{
    public const int SEARCH_LIMIT = 20;

    /**
     * Quem ainda não tem a tag. A lista é a comunidade inteira, então o seletor
     * busca em vez de pré-carregar.
     *
     * @return Builder<User>
     */
    public function forGrant(User $actor): Builder
    {
        return $this->others($actor)->whereNotIn('id', $this->members());
    }

    /**
     * Só quem tem a tag hoje. A lista é pequena, então o seletor pode
     * pré-carregar.
     *
     * @return Builder<User>
     */
    public function forRevoke(User $actor): Builder
    {
        return $this->others($actor)->whereIn('id', $this->members());
    }

    /**
     * Quem pode virar moderadora: fora da equipe (moderadoras e líderes) e sem
     * super admin.
     *
     * @return Builder<User>
     */
    public function forModerator(User $actor): Builder
    {
        return $this->others($actor)->withoutRole([
            UserRole::SuperAdmin->value,
            UserRole::DelasModerator->value,
            UserRole::DelasLead->value,
        ]);
    }

    /**
     * Busca por nome ou `@username` dentro de um dos escopos acima.
     *
     * @param  Builder<User>  $candidates
     * @return Collection<int, User>
     */
    public function search(Builder $candidates, string $search, int $limit = self::SEARCH_LIMIT): Collection
    {
        $username = mb_ltrim(mb_trim($search), '@');

        return $candidates
            ->where(fn (Builder $query): Builder => $query
                ->whereLike('name', '%'.mb_trim($search).'%')
                ->orWhereLike('username', '%'.$username.'%'))
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<User>
     */
    private function others(User $actor): Builder
    {
        return User::query()->whereKeyNot($actor->getKey());
    }

    /**
     * Quem tem a tag hoje: a solicitação está `approved`.
     *
     * @return Builder<DelasTagRequest>
     */
    private function members(): Builder
    {
        return DelasTagRequest::query()->where('status', DelasRequestStatus::Approved)->select('user_id');
    }
}
