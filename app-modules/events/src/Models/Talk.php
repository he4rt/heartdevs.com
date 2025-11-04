<?php

declare(strict_types=1);

namespace He4rt\Events\Models;

use He4rt\Events\Database\Factories\TalkFactory;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(TalkFactory::class)]
class Talk extends Model
{
    use HasFactory;

    protected $table = 'events_talks';

    protected $fillable = [
        'event_id',
        'user_id',
        'tenant_id',
        'status',
        'field_type',
        'title',
        'description',
    ];

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
