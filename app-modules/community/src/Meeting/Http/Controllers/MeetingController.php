<?php

declare(strict_types=1);

namespace He4rt\Community\Meeting\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Community\Meeting\Actions\EndMeeting;
use He4rt\Community\Meeting\Actions\StartMeeting;
use He4rt\Community\Meeting\Exceptions\MeetingException;
use He4rt\Community\Meeting\Http\Requests\MeetingRequest;
use He4rt\Community\Meeting\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class MeetingController extends Controller
{
    public function getMeetings(string $provider): JsonResponse
    {
        return response()->json(
            Meeting::query()->with('meetingType')->paginate()
        );
    }

    public function postMeeting(
        string $provider,
        MeetingRequest $request,
        StartMeeting $startMeeting
    ): JsonResponse {
        try {
            return response()->json(
                $startMeeting->handle($provider, $request->input('external_account_id'), $request->input('meeting_type_id')),
                Response::HTTP_CREATED
            );
        } catch (MeetingException $meetingException) {
            return response()->json([
                'error' => $meetingException->getMessage(),
            ], $meetingException->getCode());
        }
    }

    public function postEndMeeting(
        string $provider,
        EndMeeting $endMeeting,
    ): Response {
        $endMeeting->handle();

        return response()->noContent();
    }
}
