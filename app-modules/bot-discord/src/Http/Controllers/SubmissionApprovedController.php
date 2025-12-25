<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\BotDiscord\Actions\PostSubmissionToDiscordAction;
use He4rt\BotDiscord\DTO\SubmissionApprovedWebhookDTO;
use Illuminate\Http\Request;

class SubmissionApprovedController extends Controller
{
    public function __construct(
        private readonly PostSubmissionToDiscordAction $postSubmissionToDiscordAction
    ) {}

    public function __invoke(Request $request)
    {
        $dto = SubmissionApprovedWebhookDTO::make($request->toArray());

        $this->postSubmissionToDiscordAction->execute($dto);

        return response()->json(['ok' => true]);
    }
}
