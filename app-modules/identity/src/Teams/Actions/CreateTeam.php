<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams\Actions;

use He4rt\Identity\Teams\DTOs\TeamData;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateTeam
{
    private const int SLUG_SUFFIX_LENGTH = 5;

    public function __construct(private AddTeamMember $addMember) {}

    /**
     * The owner joins the team, because team access checks membership only.
     */
    public function handle(User $owner, TeamData $data): Team
    {
        return DB::transaction(function () use ($owner, $data): Team {
            $team = Team::query()->create([
                ...$data->toDatabase(),
                'slug' => $data->slug ?? $this->slugFor($data->name),
                'owner_id' => $owner->getKey(),
            ]);

            $this->addMember->handle($team, $owner);

            return $team;
        });
    }

    private function slugFor(string $name): string
    {
        return sprintf('%s-%s', Str::slug($name) ?: 'team', Str::lower(Str::random(self::SLUG_SUFFIX_LENGTH)));
    }
}
