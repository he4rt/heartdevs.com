<?php

declare(strict_types=1);

namespace He4rt\IntegrationDevTo\Polling;

use Illuminate\Support\Facades\Http;

final class DevToApiClient
{
    public function getArticlesByOrg(string $orgSlug, int $page = 1, int $perPage = 30): array
    {
        $baseUrl = config('integration-devto.api_base_url');

        $response = Http::get($baseUrl.'/articles', [
            'username' => $orgSlug,
            'per_page' => $perPage,
            'page' => $page,
        ]);

        return $response->json() ?? [];
    }

    public function getArticle(int $articleId): array
    {
        $baseUrl = config('integration-devto.api_base_url');

        $response = Http::get(sprintf('%s/articles/%d', $baseUrl, $articleId));

        return $response->json() ?? [];
    }
}
