<?php

declare(strict_types=1);

namespace He4rt\Delas\Support;

use He4rt\Identity\User\Models\User;

/**
 * Trava a pessoa até o fim da transação. Toda action que muda a situação de
 * alguém na He4rt Delas (pedido, tag, bloqueio, papel) começa por aqui: duas
 * decisões sobre a mesma pessoa nunca correm ao mesmo tempo, e as verificações
 * feitas depois da trava enxergam o estado mais recente.
 *
 * Só funciona dentro de `DB::transaction()`.
 */
final class PersonLock
{
    public static function acquire(User $person): void
    {
        User::query()->whereKey($person->getKey())->lockForUpdate()->first();

        // Os papéis carregados antes da trava podem estar velhos.
        $person->unsetRelation('roles');
    }
}
