<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Actions;

use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\DTOs\CreateReplyDTO;
use He4rt\Activity\Timeline\Timeline;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreateReply
{
    public function handle(CreateReplyDTO $dto): Timeline
    {
        $content = mb_trim($dto->content);

        throw_if($content === '', InvalidArgumentException::class, 'Reply content cannot be empty.');

        $parentTimeline = Timeline::query()
            ->where('id', $dto->parentTimelineId)
            ->where('tenant_id', $dto->tenantId)
            ->firstOrFail();

        $rootId = $parentTimeline->root_id ?? $parentTimeline->id;

        return DB::transaction(function () use ($dto, $content, $parentTimeline, $rootId): Timeline {
            $postEntry = PostEntry::query()->create([
                'content' => $content,
            ]);

            foreach ($dto->images as $image) {
                $postEntry->addMediaFromDisk($image, 'public')->toMediaCollection('images');
            }

            return Timeline::query()->create([
                'user_id' => $dto->userId,
                'tenant_id' => $parentTimeline->tenant_id,
                'postable_type' => $postEntry->getMorphClass(),
                'postable_id' => $postEntry->id,
                'root_id' => $rootId,
                'parent_id' => $rootId,
            ]);
        });
    }
}
