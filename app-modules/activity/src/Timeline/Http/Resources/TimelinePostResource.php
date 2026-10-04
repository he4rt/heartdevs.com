<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Http\Resources;

use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin Timeline
 */
final class TimelinePostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PostEntry $postEntry */
        $postEntry = $this->postable;

        return [
            'id' => $this->id,
            'content' => $postEntry->content,
            'images' => $postEntry->getMedia('images')
                ->map(static fn (Media $media): string => $media->getUrl())
                ->all(),
            'author' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'avatar_url' => $this->user->getFilamentAvatarUrl(),
            ],
            'pinned' => $this->pinned,
            'root_id' => $this->root_id,
            'parent_id' => $this->parent_id,
            'replies_count' => $this->children_count ?? 0,
            'reactions_count' => $this->reactions_count ?? 0,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
