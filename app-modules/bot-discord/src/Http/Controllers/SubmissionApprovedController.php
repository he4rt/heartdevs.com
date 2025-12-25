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
        $validated = $request->validate([
            'day' => ['required', 'integer', 'between:1,100'],
            'text' => ['required', 'string'],
            'tweet_url' => ['required', 'string', 'url'],
            'user_name' => ['required', 'string'],
        ]);
        try {
            $dto = SubmissionApprovedWebhookDTO::make($validated);

            $this->postSubmissionToDiscordAction->execute($dto);

            return response()->json(['ok' => true]);
        } catch (Throwable) {
            return response()->json([
                'ok' => false,
            ], 500);
        }
    }
}
