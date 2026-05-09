<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Actions;

use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use InvalidArgumentException;

final readonly class CreateReply
{
    public function handle(
        User $user,
        Timeline $parentTimeline,
        string $content,
    ): Timeline {
        $content = mb_trim($content);

        throw_if($content === '', InvalidArgumentException::class, 'Reply content cannot be empty.');

        $rootId = $parentTimeline->root_id ?? $parentTimeline->id;

        $postEntry = PostEntry::query()->create([
            'content' => $content,
        ]);

        return Timeline::query()->create([
            'user_id' => $user->id,
            'tenant_id' => $parentTimeline->tenant_id,
            'postable_type' => 'post_entry',
            'postable_id' => $postEntry->id,
            'root_id' => $rootId,
            'parent_id' => $rootId,
        ]);
    }
}
