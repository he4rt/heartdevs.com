<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Delegated;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostEntry extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'activity_post_entries';

    protected $fillable = [
        'content',
    ];
}
