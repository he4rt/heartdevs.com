<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Actions;

use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\DTOs\CreatePostDTO;
use He4rt\Activity\Timeline\Timeline;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreatePost
{
    public function handle(CreatePostDTO $dto): Timeline
    {
        $content = mb_trim($dto->content);

        throw_if(blank($content), InvalidArgumentException::class, 'Post content cannot be empty.');

        return DB::transaction(static function () use ($dto, $content): Timeline {
            $postEntry = PostEntry::query()->create([
                'content' => $content,
            ]);

            foreach ($dto->images as $image) {
                $postEntry->addMediaFromDisk($image, 'public')->toMediaCollection('images');
            }

            return Timeline::query()->create([
                'user_id' => $dto->userId,
                'tenant_id' => $dto->tenantId,
                'postable_type' => $postEntry->getMorphClass(),
                'postable_id' => $postEntry->id,
            ]);
        });
    }
}
