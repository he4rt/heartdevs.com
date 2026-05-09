<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Actions;

use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\DTOs\CreatePostDTO;
use He4rt\Activity\Timeline\Timeline;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final readonly class CreatePost
{
    public function handle(CreatePostDTO $dto): Timeline
    {
        $content = mb_trim($dto->content);

        throw_if($content === '', InvalidArgumentException::class, 'Post content cannot be empty.');

        $postEntry = PostEntry::query()->create([
            'content' => $content,
        ]);

        foreach ($dto->images as $image) {
            $path = Storage::disk('public')->path($image);
            $postEntry->addMedia($path)->toMediaCollection('images');
        }

        return Timeline::query()->create([
            'user_id' => $dto->userId,
            'tenant_id' => $dto->tenantId,
            'postable_type' => 'post_entry',
            'postable_id' => $postEntry->id,
        ]);
    }
}
