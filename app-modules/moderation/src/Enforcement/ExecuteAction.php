<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enforcement;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Platform\PlatformRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class ExecuteAction implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly ModerationAction $action,
        private readonly User $target,
    ) {}

    public function handle(PlatformRegistry $registry): void
    {
        $results = [];

        foreach ($this->action->target_platforms as $platformValue) {
            $platform = Platform::tryFrom($platformValue);

            if ($platform === null || !$registry->has($platform)) {
                continue;
            }

            $results[] = $registry->resolve($platform)->execute($this->action, $this->target);
        }

        $this->action->update([
            'execution_results' => array_map(fn (ExecutionResultDTO $r) => [
                'platform' => $r->platform->value,
                'success' => $r->success,
                'error' => $r->error,
            ], $results),
        ]);

        $this->action->case->update([
            'status' => CaseStatus::Resolved,
            'resolved_at' => now(),
        ]);

        event(new ActionExecuted($this->action));
    }
}
