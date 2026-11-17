<?php

declare(strict_types=1);

namespace Tests\Fixtures\Logging;

use App\Support\Logging\WideEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Tries;
use RuntimeException;

#[Tries(tries: 3)]
final class ChargeCard implements ShouldQueue
{
    use Queueable;

    public function __construct(public bool $declines = false) {}

    public function handle(): void
    {
        WideEvent::add('checkout.attempt', $this->attempts());

        throw_if($this->declines, RuntimeException::class, 'card declined');
    }
}
