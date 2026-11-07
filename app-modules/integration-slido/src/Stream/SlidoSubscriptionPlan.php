<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Stream;

final readonly class SlidoSubscriptionPlan
{
    /**
     * @param  list<string>  $opening
     * @param  list<string>  $closing
     * @param  list<string>  $keeping
     */
    private function __construct(
        public array $opening,
        public array $closing,
        public array $keeping,
    ) {}

    /**
     * @param  list<string>  $wanted  event hashes that have an audience now
     * @param  list<string>  $connected  event hashes with an open connection
     */
    public static function between(array $wanted, array $connected): self
    {
        return new self(
            opening: array_values(array_diff($wanted, $connected)),
            closing: array_values(array_diff($connected, $wanted)),
            keeping: array_values(array_intersect($connected, $wanted)),
        );
    }
}
