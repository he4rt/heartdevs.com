<?php

declare(strict_types=1);

use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Audit\ModerationAuditLog;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Rules\ModerationRule;

return [
    'modules' => [
        // Modules whose Filament/Admin/ resources will be discovered
        // Ex: 'identity', 'gamification', 'events'
    ],

    'tenant_scoped_models' => [
        ModerationCase::class,
        ModerationAction::class,
        ModerationAppeal::class,
        ModerationRule::class,
        ModerationAuditLog::class,
    ],
];
