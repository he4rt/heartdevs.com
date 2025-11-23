<?php

declare(strict_types=1);

namespace He4rt\Message\Tests\Unit;

use DateMalformedStringException;
use DateTimeImmutable;
use He4rt\Message\Entities\MessageEntity;
use Illuminate\Support\Facades\Date;

trait MessageProviderTrait
{
    /**
     * @throws DateMalformedStringException
     */
    public function validMessagePayload(array $fields = []): array
    {
        return [
            'id' => 'canhassi-id',
            'provider_id' => 'é-o-canhas-id',
            'provider_message_id' => 'he4rtDevelopers',
            'channel_id' => 'canal-foda',
            'content' => 'conteudo-foda',
            'sent_at' => new DateTimeImmutable(Date::now()->toString()),
            'obtained_experience' => 12,
            ...$fields,
        ];
    }

    /**
     * @throws DateMalformedStringException
     */
    public function validMessageEntity(): MessageEntity
    {
        return MessageEntity::make($this->validMessagePayload());
    }
}
