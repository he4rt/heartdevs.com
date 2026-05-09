<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Delegated;

use He4rt\Activity\Database\Factories\PostEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class PostEntry extends Model implements HasMedia
{
    /** @use HasFactory<PostEntryFactory> */
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'activity_post_entries';

    protected $fillable = [
        'content',
    ];

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
