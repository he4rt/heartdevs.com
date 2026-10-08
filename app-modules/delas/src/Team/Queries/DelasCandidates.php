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

    private const string ACCENTED = 'ÁÀÂÃÄáàâãäÉÈÊËéèêëÍÌÎÏíìîïÓÒÔÕÖóòôõöÚÙÛÜúùûüÇçÑñ';

    private const string PLAIN = 'AAAAAaaaaaEEEEeeeeIIIIiiiiOOOOOoooooUUUUuuuuCcNn';

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
     * Quem pode virar moderadora: tem a tag, está fora da equipe (moderadoras e
     * líderes) e não é super admin. A lista é pequena, então o seletor pode
     * pré-carregar. Filtra pelo nome da role direto, sem o `withoutRole()` do
     * Spatie, que quebra quando uma das roles ainda não existe no banco.
     *
     * @return Builder<User>
     */
    public function forModerator(User $actor): Builder
    {
        return $this->others($actor)->whereIn('id', $this->members())->whereDoesntHave('roles', fn (Builder $roles): Builder => $roles->whereIn('name', [
            UserRole::SuperAdmin->value,
            UserRole::DelasModerator->value,
            UserRole::DelasLead->value,
        ]));
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
                // Sem acento dos dois lados: "thais" acha "Thaís".
                ->whereRaw('lower(translate(name, ?, ?)) like ?', [self::ACCENTED, self::PLAIN, '%'.$this->plain($search).'%'])
                ->orWhereLike('username', '%'.$username.'%'))
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    private function plain(string $search): string
    {
        return mb_strtolower(strtr(mb_trim($search), array_combine(mb_str_split(self::ACCENTED), mb_str_split(self::PLAIN))));
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
