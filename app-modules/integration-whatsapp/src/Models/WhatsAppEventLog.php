<?php

declare(strict_types=1);

namespace He4rt\IntegrationWhatsapp\Models;

use Carbon\CarbonInterface;
use He4rt\IntegrationWhatsapp\Database\Factories\WhatsAppEventLogFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $event_id
 * @property string $type
 * @property string|null $chat_jid
 * @property CarbonInterface $received_at
 * @property array<string, mixed> $payload
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'whatsapp_event_logs')]
#[UseFactory(factoryClass: WhatsAppEventLogFactory::class)]
final class WhatsAppEventLog extends Model
{
    /** @use HasFactory<WhatsAppEventLogFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
        ];
    }
}
