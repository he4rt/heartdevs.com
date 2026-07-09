<?php

declare(strict_types=1);

namespace App\Geo;

use Illuminate\Support\Facades\Http;

final readonly class WorldApiClient
{
    public function __construct(
        private string $baseUrl,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function countries(): array
    {
        return $this->get('/countries', [
            'fields' => 'iso2,iso3',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function states(string $iso2): array
    {
        return $this->get('/states', [
            'filters' => ['country_code' => $iso2],
            'fields' => 'cities',
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<array<string, mixed>>
     */
    private function get(string $path, array $query = []): array
    {
        $response = Http::baseUrl(mb_rtrim($this->baseUrl, '/'))
            ->acceptJson()
            ->get($path, $query);

        $response->throw();

        /** @var list<array<string, mixed>> $data */
        $data = $response->json('data', []);

        return $data;
    }
}
