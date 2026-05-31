<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Delegated;

use Carbon\Carbon;
use He4rt\Activity\Database\Factories\PostEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table(name: 'activity_post_entries')]
final class PostEntry extends Model implements HasMedia
{
    /** @use HasFactory<PostEntryFactory> */
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;
    use SoftDeletes;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk('public');
    }

    protected static function newFactory(): PostEntryFactory
    {
        return PostEntryFactory::new();
    }
}
