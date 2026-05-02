<?php

declare(strict_types=1);

namespace He4rt\Moderation\Jobs;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Contracts\ModerationPlatformContract;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Events\ActionExecuted;
use He4rt\Moderation\Models\ModerationAction;
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

    public function handle(): void
    {
        $platforms = app()->tagged('moderation.platforms');
        $results = [];

        foreach ($platforms as $adapter) {
            /** @var ModerationPlatformContract $adapter */
            if (in_array($adapter->platform()->value, $this->action->target_platforms, true)) {
                $results[] = $adapter->execute($this->action, $this->target);
            }
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
