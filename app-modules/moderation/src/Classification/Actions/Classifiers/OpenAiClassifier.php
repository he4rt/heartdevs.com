<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Actions\Classifiers;

use He4rt\Moderation\Classification\Actions\ContentClassifierContract;
use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class OpenAiClassifier implements ContentClassifierContract
{
    private const array CATEGORY_MAP = [
        'harassment' => 'harassment',
        'hate' => 'toxicity',
        'sexual' => 'nsfw',
        'violence' => 'toxicity',
        'self-harm' => 'toxicity',
        'sexual/minors' => 'nsfw',
        'hate/threatening' => 'harassment',
        'violence/graphic' => 'toxicity',
        'harassment/threatening' => 'harassment',
    ];

    public function classify(ModerationContentDTO $content): ClassificationResultDTO
    {
        if (!config('moderation.classifiers.openai.enabled')) {
            return ClassificationResultDTO::empty($this->name());
        }

        try {
            $response = Http::withToken(config('services.openai.api_key'))
                ->post('https://api.openai.com/v1/moderations', [
                    'model' => config('moderation.classifiers.openai.model', 'omni-moderation-latest'),
                    'input' => $content->textContent,
                ]);

            if ($response->failed()) {
                Log::warning('OpenAI Moderation API failed', ['status' => $response->status()]);

                return ClassificationResultDTO::empty($this->name());
            }

            return $this->parseResponse($response->json());
        } catch (Throwable $throwable) {
            Log::warning('OpenAI Moderation API exception', ['error' => $throwable->getMessage()]);

            return ClassificationResultDTO::empty($this->name());
        }
    }

    public function name(): string
    {
        return 'openai';
    }

    private function parseResponse(array $data): ClassificationResultDTO
    {
        $result = $data['results'][0] ?? [];
        $categoryScores = $result['category_scores'] ?? [];

        $scores = [];
        foreach ($categoryScores as $category => $score) {
            $mapped = self::CATEGORY_MAP[$category] ?? null;
            if ($mapped !== null) {
                $scores[$mapped] = max($scores[$mapped] ?? 0, $score);
            }
        }

        return ClassificationResultDTO::fromScores($scores, $this->name());
    }
}
