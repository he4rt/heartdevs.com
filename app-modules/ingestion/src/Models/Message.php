<?php

declare(strict_types=1);

namespace He4rt\Ingestion\Models;

use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Connection('timescaledb')]
#[Table(name: 'messages')]
class Message extends Model
{
    use HasUuids;

    protected $guarded = [];

    /**
     * Set the keys for a save update query.
     * TimescaleDB requires the time column in the primary key.
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());

        if (isset($this->sent_at)) {
            $query->where('sent_at', '=', $this->sent_at);
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_pinned' => 'boolean',
            'mentions_everyone' => 'boolean',
            'sent_at' => 'datetime',
            'edited_at' => 'datetime',
        ];
    }
}
