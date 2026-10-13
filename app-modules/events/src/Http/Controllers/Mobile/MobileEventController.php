<?php

declare(strict_types=1);

namespace He4rt\Events\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use He4rt\Events\CheckIn\Actions\NumericCodeCheckInAction;
use He4rt\Events\CheckIn\DTOs\NumericCodeCheckInDTO;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\CheckIn\Rules\CheckInCodeRule;
use He4rt\Events\Enrollment\Actions\EnrollUserAction;
use He4rt\Events\Enrollment\DTOs\EnrollUserDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Models\Event;
use He4rt\Events\Http\Resources\EventDetailResource;
use He4rt\Events\Http\Resources\EventResource;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MobileEventController extends Controller
{
    /**
     * Listar eventos
     *
     * Retorna os eventos visíveis a participantes (publicados, concluídos
     * ou cancelados), mais próximos primeiro, com o status de inscrição
     * do usuário autenticado quando houver.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $events = Event::query()
            ->with(['enrollments' => fn (Relation $query): Relation => $query->where('user_id', $user->id)])
            ->viewableByParticipant()
            ->orderBy('starts_at')
            ->simplePaginate(20);

        return EventResource::collection($events);
    }

    /**
     * Detalhe do evento
     *
     * Inclui a inscrição do usuário (se houver) e os mesmos booleans que
     * o painel web usa pra decidir a ação disponível (can_apply,
     * can_confirm_presence, is_event_full).
     */
    public function show(Request $request, Event $event): EventDetailResource
    {
        throw_unless($event->status->isViewableByParticipant(), NotFoundHttpException::class);

        $event->load('enrollmentPolicy');

        /** @var User $user */
        $user = $request->user();

        $enrollment = Enrollment::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        return new EventDetailResource($event, $enrollment);
    }

    /**
     * Inscrever-se no evento
     *
     * Cobre RSVP e candidatura (application) — a mesma Action decide o
     * status inicial (confirmed, waitlisted ou pending) de acordo com a
     * política de inscrição do evento.
     */
    public function enroll(Request $request, Event $event, EnrollUserAction $enrollUser): JsonResponse
    {
        throw_unless($event->status->isViewableByParticipant(), NotFoundHttpException::class);

        $request->validate([
            'application_data' => ['sometimes', 'array'],
        ]);

        /** @var array<string, mixed>|null $applicationData */
        $applicationData = $request->input('application_data');

        /** @var User $user */
        $user = $request->user();

        try {
            $enrollment = $enrollUser->handle(new EnrollUserDTO(
                eventId: $event->id,
                userId: $user->id,
                applicationData: $applicationData,
            ));
        } catch (EnrollmentException $enrollmentException) {
            return response()->json(['message' => $enrollmentException->getMessage()], $enrollmentException->getCode());
        }

        return response()->json([
            'status' => $enrollment->status->value,
            'waitlist_position' => $enrollment->waitlist_position,
        ], Response::HTTP_CREATED);
    }

    /**
     * Check-in por código numérico
     *
     * Único método de check-in self-service disponível hoje (QR fica pra
     * uma iteração futura — ver docs/plans/2026-09-22-api-mobile-jwt.md).
     */
    public function checkIn(Request $request, Event $event, NumericCodeCheckInAction $checkIn): JsonResponse
    {
        throw_unless($event->status->isViewableByParticipant(), NotFoundHttpException::class);

        $request->validate([
            'code' => ['required', 'string', new CheckInCodeRule],
        ]);

        $code = $request->string('code')->toString();

        /** @var User $user */
        $user = $request->user();

        $enrollment = Enrollment::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$enrollment instanceof Enrollment || !in_array($enrollment->status, [EnrollmentStatus::Confirmed, EnrollmentStatus::CheckedIn], strict: true)) {
            return response()->json(['message' => 'Not enrolled in this event.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rateLimitKey = $this->checkInRateLimitKey($user->id, $event->id, $request->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return response()->json([
                'message' => CheckInException::checkInCodeRateLimited(RateLimiter::availableIn($rateLimitKey))->getMessage(),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($rateLimitKey, 60);

        try {
            $checkInRecord = $checkIn->handle(new NumericCodeCheckInDTO(
                enrollment: $enrollment,
                code: $code,
                eventDate: Date::today(),
            ));
        } catch (CheckInException $checkInException) {
            return response()->json(['message' => $checkInException->getMessage()], $checkInException->getCode());
        }

        RateLimiter::clear($rateLimitKey);

        return response()->json([
            'event_date' => $checkInRecord->event_date->toDateString(),
            'checked_in_at' => $checkInRecord->checked_in_at?->toIso8601String(),
        ], Response::HTTP_CREATED);
    }

    private function checkInRateLimitKey(string $userId, string $eventId, ?string $ip): string
    {
        return sprintf('numeric-code-check-in:%s:%s:%s', $userId, $eventId, md5($ip ?? 'unknown'));
    }
}
