<?php

declare(strict_types=1);

namespace App\Support\Logging;

use App\Contracts\Enums\HasDescription;
use App\Contracts\Enums\HasLabel;
use App\Contracts\Enums\HasTone;
use App\Enums\Tone;

enum WideEventOutcome: string implements HasDescription, HasLabel, HasTone
{
    case Success = 'success';
    case ClientError = 'client_error';
    case Error = 'error';

    public function getLabel(): string
    {
        return match ($this) {
            self::Success => 'Success',
            self::ClientError => 'Client error',
            self::Error => 'Error',
        };
    }

    public function getTone(): Tone
    {
        return match ($this) {
            self::Success => Tone::Success,
            self::ClientError => Tone::Warning,
            self::Error => Tone::Danger,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Success => 'The work finished without an error.',
            self::ClientError => 'The request was refused with a 4xx status: not found, validation, forbidden.',
            self::Error => 'The work failed: a 5xx status, a reported exception or a command with a non-zero exit code.',
        };
    }
}
