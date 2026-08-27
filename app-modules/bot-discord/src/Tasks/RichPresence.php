<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Tasks;

use Discord\Parts\User\Activity;
use Laracord\Tasks\Task;

class RichPresence extends Task
{
    /** @var int The task interval. */
    protected int $interval = 600;

    /** @var bool Determine if the task handler should execute during boot. */
    protected bool $eager = true;

    /** Handle the task. */
    public function handle(): void
    {
        $activities = $this->makeActivities();

        $this->discord()->updatePresence($activities[array_rand($activities)]);
    }

    /** @return Activity[] */
    private function makeActivities(): array
    {
        return [
            $this->discord()->factory(Activity::class, ['type' => Activity::TYPE_LISTENING, 'name' => '/apresentar']),
            $this->discord()->factory(Activity::class, ['type' => Activity::TYPE_WATCHING, 'name' => 'da comunidade para a comunidade.']),
            $this->discord()->factory(Activity::class, ['type' => Activity::TYPE_WATCHING, 'name' => 'heartdevs.com']),
            $this->discord()->factory(Activity::class, ['type' => Activity::TYPE_WATCHING, 'name' => 'github.com/he4rt']),
        ];
    }
}
