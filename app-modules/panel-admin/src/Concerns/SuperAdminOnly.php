<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Concerns;

use He4rt\Identity\User\Models\User;

/**
 * Restringe um Resource, Page ou Cluster do `/admin` a super admins (ADR-0003 do
 * identity). Entrar no painel não libera nada por si só: sem este trait, o
 * Filament abre o componente para qualquer pessoa que passou no
 * `canAccessPanel()`, inclusive papéis de moderação.
 */
trait SuperAdminOnly
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
    }
}
