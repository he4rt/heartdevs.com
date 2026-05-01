<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Models;

use Carbon\Carbon;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $tenant_id
 * @property string $message_id
 * @property string $provider_thread_id
 * @property string|null $name
 * @property bool|null $archived
 * @property int|null $auto_archive_duration
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class MessageThread extends Model
{
    use HasUuids;

    protected $table = 'message_threads';

    protected $fillable = [
        'id',
        'tenant_id',
        'message_id',
        'provider_thread_id',
        'name',
        'archived',
        'auto_archive_duration',
    ];

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'archived' => 'boolean',
        ];
    }
}
