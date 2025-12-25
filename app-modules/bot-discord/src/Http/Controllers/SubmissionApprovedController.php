<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\BotDiscord\Actions\PostSubmissionToDiscordAction;
use He4rt\BotDiscord\DTO\SubmissionApprovedWebhookDTO;
use Illuminate\Http\Request;
use Throwable;

class SubmissionApprovedController extends Controller
{
    public function __construct(
        private readonly PostSubmissionToDiscordAction $postSubmissionToDiscordAction
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $validated = $request->validate([
                'day' => ['required', 'integer'],
                'text' => ['required', 'string'],
                'tweetUrl' => ['required', 'string'],
                'userName' => ['required', 'string'],
            ]);

            $dto = SubmissionApprovedWebhookDTO::make($validated);

            $this->postSubmissionToDiscordAction->execute($dto);

            return response()->json(['ok' => true]);
        } catch (Throwable $throwable) {
            return response()->json([
                'ok' => false,
                'error' => $throwable->getMessage(),
            ], 400);
        }
    }
}
