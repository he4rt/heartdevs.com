<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Actions;

use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final readonly class CreatePost
{
    /**
     * @param  array<int, UploadedFile>  $images
     */
    public function handle(
        User $user,
        int $tenantId,
        string $content,
        array $images = [],
    ): Timeline {
        $content = mb_trim($content);

        throw_if($content === '', InvalidArgumentException::class, 'Post content cannot be empty.');

        $postEntry = PostEntry::query()->create([
            'content' => $content,
        ]);

        foreach ($images as $image) {
            $postEntry->addMedia($image)->toMediaCollection('images');
        }

        return Timeline::query()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenantId,
            'postable_type' => 'post_entry',
            'postable_id' => $postEntry->id,
        ]);
    }
}
