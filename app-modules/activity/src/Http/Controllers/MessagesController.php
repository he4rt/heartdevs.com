<?php

declare(strict_types=1);

namespace He4rt\Activity\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Activity\Actions\NewMessage;
use He4rt\Activity\Actions\NewVoiceMessage;
use He4rt\Activity\DTOs\NewMessageDTO;
use He4rt\Activity\Http\Requests\CreateMessageRequest;
use He4rt\Activity\Http\Requests\CreateVoiceMessageRequest;
use Illuminate\Http\Response;

final class MessagesController extends Controller
{
    public function postMessage(
        CreateMessageRequest $request,
        string $provider,
        NewMessage $newMessage,
    ): Response {
        $newMessage->persist(NewMessageDTO::make($request->validated()));

        return response()->noContent();
    }

    public function postVoiceMessage(
        CreateVoiceMessageRequest $request,
        string $provider,
        NewVoiceMessage $voiceMessage,
    ): Response {
        $voiceMessage->persist($request->validated());

        return response()->noContent();
    }
}
