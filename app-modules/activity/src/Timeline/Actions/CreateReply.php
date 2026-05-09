<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Actions;

use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\DTOs\CreateReplyDTO;
use He4rt\Activity\Timeline\Timeline;
use InvalidArgumentException;

final readonly class CreateReply
{
    public function handle(CreateReplyDTO $dto): Timeline
    {
        $content = mb_trim($dto->content);

        throw_if($content === '', InvalidArgumentException::class, 'Reply content cannot be empty.');

        $parentTimeline = Timeline::query()->findOrFail($dto->parentTimelineId);

        $rootId = $parentTimeline->root_id ?? $parentTimeline->id;

        $postEntry = PostEntry::query()->create([
            'content' => $content,
        ]);

        return Timeline::query()->create([
            'user_id' => $dto->userId,
            'tenant_id' => $parentTimeline->tenant_id,
            'postable_type' => 'post_entry',
            'postable_id' => $postEntry->id,
            'root_id' => $rootId,
            'parent_id' => $rootId,
        ]);
    }
}
