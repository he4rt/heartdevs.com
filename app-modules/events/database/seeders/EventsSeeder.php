<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Seeders;

use Carbon\CarbonInterface;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Enums\EventType;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

/**
 * Seeds a matrix of events that exercises every meaningful combination of
 * event type, lifecycle status, schedule (past / ongoing / upcoming),
 * enrollment method, check-in method, capacity and attendance rules.
 *
 * Re-runnable: events are keyed by slug and their children are only seeded
 * the first time the event is created.
 */
final class EventsSeeder extends Seeder
{
    /** @var int Size of the demo participant pool shared across events. */
    private const int PARTICIPANT_POOL = 30;

    public function run(): void
    {
        $participants = $this->resolveParticipants();

        foreach ($this->matrix() as $spec) {
            $event = Event::query()->firstOrCreate(
                ['slug' => Str::slug($spec['title'])],
                [
                    'title' => $spec['title'],
                    'description' => $spec['description'],
                    'event_type' => $spec['type'],
                    'location' => $spec['location'],
                    'status' => $spec['status'],
                    ...$this->schedule($spec['schedule'], $spec['days']),
                ],
            );

            if (!$event->wasRecentlyCreated) {
                continue;
            }

            $this->seedPolicy($event, $spec);
            $this->seedEnrollments($event, $spec, $participants);
            $this->seedCheckInCodes($event, $spec);
        }
    }

    /**
     * The event matrix. Each row is a self-contained scenario with a
     * human-readable title so it is easy to recognise in the panels.
     *
     * @return list<array<string, mixed>>
     */
    private function matrix(): array
    {
        return [
            // RSVP-only meetup, no capacity, manual check-in, happening soon.
            [
                'title' => 'He4rt Meetup #42 — Carreira em Tech',
                'description' => 'Bate-papo aberto sobre transição de carreira e primeiro emprego em tecnologia.',
                'type' => EventType::Meetup,
                'status' => EventStatus::Published,
                'schedule' => 'upcoming',
                'days' => 1,
                'location' => 'Online — Discord He4rt',
                'enrollment_method' => EnrollmentMethod::Rsvp,
                'check_in_method' => CheckInMethod::Manual,
                'capacity' => null,
                'has_waitlist' => false,
                'attendance_requirement' => AttendanceRequirement::AnyDay,
                'minimum_days' => null,
                'cancellation_deadline_hours' => null,
                'xp' => [50, 0, 100],
                'enrollments' => [
                    EnrollmentStatus::Confirmed->value => 8,
                    EnrollmentStatus::Pending->value => 2,
                    EnrollmentStatus::Cancelled->value => 1,
                ],
            ],

            // The primary numeric-code target: ongoing single-day workshop.
            [
                'title' => 'Workshop: Rust do Zero ao Deploy',
                'description' => 'Mão na massa construindo e publicando uma API em Rust em uma tarde.',
                'type' => EventType::Workshop,
                'status' => EventStatus::Published,
                'schedule' => 'ongoing',
                'days' => 1,
                'location' => 'Sede He4rt — Sala 1',
                'enrollment_method' => EnrollmentMethod::RsvpCheckin,
                'check_in_method' => CheckInMethod::NumericCode,
                'capacity' => 50,
                'has_waitlist' => true,
                'attendance_requirement' => AttendanceRequirement::AllDays,
                'minimum_days' => null,
                'cancellation_deadline_hours' => 24,
                'xp' => [100, 200, 500],
                'enrollments' => [
                    EnrollmentStatus::Confirmed->value => 10,
                    EnrollmentStatus::CheckedIn->value => 6,
                    EnrollmentStatus::Waitlisted->value => 4,
                    EnrollmentStatus::Cancelled->value => 2,
                ],
                'check_in_codes' => true,
            ],

            // Small-capacity numeric-code meetup, ongoing, to test waitlist + check-in.
            [
                'title' => 'Mentoria em Grupo: Primeiro Emprego',
                'description' => 'Sessão de mentoria com vagas limitadas e lista de espera.',
                'type' => EventType::Meetup,
                'status' => EventStatus::Published,
                'schedule' => 'ongoing',
                'days' => 1,
                'location' => 'Online — Discord He4rt',
                'enrollment_method' => EnrollmentMethod::RsvpCheckin,
                'check_in_method' => CheckInMethod::NumericCode,
                'capacity' => 10,
                'has_waitlist' => true,
                'attendance_requirement' => AttendanceRequirement::AnyDay,
                'minimum_days' => null,
                'cancellation_deadline_hours' => 12,
                'xp' => [30, 80, 150],
                'enrollments' => [
                    EnrollmentStatus::CheckedIn->value => 7,
                    EnrollmentStatus::Confirmed->value => 3,
                    EnrollmentStatus::Waitlisted->value => 5,
                    EnrollmentStatus::NoShow->value => 1,
                ],
                'check_in_codes' => true,
            ],

            // Application-based, multi-day conference with QR check-in, upcoming.
            [
                'title' => 'He4rt Conf 2026',
                'description' => 'Três dias de palestras, trilhas e networking da comunidade He4rt.',
                'type' => EventType::Conference,
                'status' => EventStatus::Published,
                'schedule' => 'upcoming',
                'days' => 3,
                'location' => 'São Paulo — Centro de Convenções',
                'enrollment_method' => EnrollmentMethod::Application,
                'check_in_method' => CheckInMethod::QrCode,
                'capacity' => 200,
                'has_waitlist' => true,
                'attendance_requirement' => AttendanceRequirement::MinimumDays,
                'minimum_days' => 2,
                'cancellation_deadline_hours' => 48,
                'xp' => [200, 300, 1_000],
                'application_schema' => [
                    ['key' => 'why_participate',    'type' => 'text',     'label' => 'Por que quer participar da He4rt Conf?', 'required' => true],
                    ['key' => 'experience_level',   'type' => 'select',   'label' => 'Nível de experiência em desenvolvimento', 'required' => true, 'options' => ['Iniciante', 'Intermediário', 'Avançado']],
                    ['key' => 'personal_project',   'type' => 'textarea', 'label' => 'Descreva um projeto pessoal ou open source relevante', 'required' => false],
                    ['key' => 'attendance_days',    'type' => 'checkbox', 'label' => 'Em quais dias você pode comparecer?', 'required' => false, 'options' => ['Dia 1', 'Dia 2', 'Dia 3']],
                ],
                'enrollments' => [
                    EnrollmentStatus::Pending->value => 6,
                    EnrollmentStatus::Confirmed->value => 12,
                    EnrollmentStatus::Waitlisted->value => 4,
                    EnrollmentStatus::Rejected->value => 2,
                ],
            ],

            // Past, completed meetup with attendance recorded.
            [
                'title' => 'Live: PHP 8.5 — O que há de novo',
                'description' => 'Retrospectiva das novidades do PHP 8.5 com exemplos práticos.',
                'type' => EventType::Meetup,
                'status' => EventStatus::Completed,
                'schedule' => 'past',
                'days' => 1,
                'location' => 'Online — YouTube He4rt',
                'enrollment_method' => EnrollmentMethod::Rsvp,
                'check_in_method' => CheckInMethod::Manual,
                'capacity' => null,
                'has_waitlist' => false,
                'attendance_requirement' => AttendanceRequirement::AnyDay,
                'minimum_days' => null,
                'cancellation_deadline_hours' => null,
                'xp' => [40, 0, 120],
                'enrollments' => [
                    EnrollmentStatus::Attended->value => 9,
                    EnrollmentStatus::NoShow->value => 3,
                    EnrollmentStatus::Cancelled->value => 2,
                ],
            ],

            // Upcoming numeric-code workshop with any-day attendance.
            [
                'title' => 'Workshop: Docker para Desenvolvedores',
                'description' => 'Do build à orquestração: containers na prática para o dia a dia.',
                'type' => EventType::Workshop,
                'status' => EventStatus::Published,
                'schedule' => 'upcoming',
                'days' => 1,
                'location' => 'Sede He4rt — Sala 2',
                'enrollment_method' => EnrollmentMethod::RsvpCheckin,
                'check_in_method' => CheckInMethod::NumericCode,
                'capacity' => 40,
                'has_waitlist' => false,
                'attendance_requirement' => AttendanceRequirement::AnyDay,
                'minimum_days' => null,
                'cancellation_deadline_hours' => 24,
                'xp' => [80, 150, 400],
                'enrollments' => [
                    EnrollmentStatus::Confirmed->value => 14,
                    EnrollmentStatus::Pending->value => 1,
                ],
            ],

            // Draft (unpublished) workshop — should be invisible to participants.
            [
                'title' => 'Bootcamp Laravel — Turma 1',
                'description' => 'Rascunho do bootcamp intensivo de Laravel (ainda não publicado).',
                'type' => EventType::Workshop,
                'status' => EventStatus::Draft,
                'schedule' => 'upcoming',
                'days' => 5,
                'location' => 'Sede He4rt — Auditório',
                'enrollment_method' => EnrollmentMethod::RsvpCheckin,
                'check_in_method' => CheckInMethod::NumericCode,
                'capacity' => 30,
                'has_waitlist' => true,
                'attendance_requirement' => AttendanceRequirement::MinimumDays,
                'minimum_days' => 4,
                'cancellation_deadline_hours' => 72,
                'xp' => [150, 250, 1_500],
                'enrollments' => [],
            ],

            // Cancelled conference — terminal status, still viewable by participants.
            [
                'title' => 'Hackathon He4rt — Edição Inverno',
                'description' => 'Hackathon cancelado por falta de patrocínio (mantido para histórico).',
                'type' => EventType::Conference,
                'status' => EventStatus::Cancelled,
                'schedule' => 'upcoming',
                'days' => 2,
                'location' => 'Online — Discord He4rt',
                'enrollment_method' => EnrollmentMethod::Application,
                'check_in_method' => CheckInMethod::QrCode,
                'capacity' => 100,
                'has_waitlist' => false,
                'attendance_requirement' => AttendanceRequirement::AllDays,
                'minimum_days' => null,
                'cancellation_deadline_hours' => 48,
                'xp' => [100, 200, 800],
                'application_schema' => [
                    ['key' => 'project_idea',    'type' => 'text',   'label' => 'Qual ideia de projeto você traria para o hackathon?', 'required' => true],
                    ['key' => 'main_stack',      'type' => 'select', 'label' => 'Stack principal', 'required' => true, 'options' => ['PHP', 'JavaScript', 'Python', 'Go', 'Rust', 'Outra']],
                ],
                'enrollments' => [
                    EnrollmentStatus::Cancelled->value => 8,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function seedPolicy(Event $event, array $spec): void
    {
        EnrollmentPolicy::factory()->create([
            'event_id' => $event->id,
            'enrollment_method' => $spec['enrollment_method'],
            'check_in_method' => $spec['check_in_method'],
            'capacity' => $spec['capacity'],
            'has_waitlist' => $spec['has_waitlist'],
            'attendance_requirement' => $spec['attendance_requirement'],
            'minimum_days' => $spec['minimum_days'],
            'cancellation_deadline_hours' => $spec['cancellation_deadline_hours'],
            'xp_on_confirmed' => $spec['xp'][0],
            'xp_on_checked_in' => $spec['xp'][1],
            'xp_on_attended' => $spec['xp'][2],
            'application_schema' => $spec['application_schema'] ?? null,
        ]);
    }

    /**
     * @param  Collection<int, User>  $participants
     * @param  array<string, mixed>  $spec
     */
    private function seedEnrollments(Event $event, array $spec, Collection $participants): void
    {
        $pool = $participants->shuffle()->values();
        $cursor = 0;
        $waitlistPosition = 1;

        foreach ($spec['enrollments'] as $statusValue => $count) {
            $status = EnrollmentStatus::from($statusValue);

            for ($i = 0; $i < $count; $i++) {
                $user = $pool->get($cursor++);

                if ($user === null) {
                    return; // pool exhausted for this event
                }

                Enrollment::factory()->create([
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'status' => $status,
                    'waitlist_position' => $status === EnrollmentStatus::Waitlisted ? $waitlistPosition++ : null,
                    'rejection_reason' => $status === EnrollmentStatus::Rejected ? 'Vagas esgotadas.' : null,
                    'application_data' => $this->fakeApplicationData($spec['application_schema'] ?? null),
                    ...$this->enrollmentTimestamps($status),
                ]);
            }
        }
    }

    /**
     * Creates a representative set of codes for numeric-code events so every
     * branch of the check-in flow (valid / expired / revoked / exhausted) is
     * reachable from the admin panel and the participant component.
     *
     * @param  array<string, mixed>  $spec
     */
    private function seedCheckInCodes(Event $event, array $spec): void
    {
        if (!($spec['check_in_codes'] ?? false)) {
            return;
        }

        $now = Date::now();
        $today = Date::today();

        // Memorable, currently-valid code for manual testing.
        CheckInCode::factory()->create([
            'event_id' => $event->id,
            'event_date' => $today,
            'code' => '424242',
            'starts_at' => $now->clone()->subHour(),
            'expires_at' => $now->clone()->addHours(3),
            'max_uses' => null,
            'uses_count' => 0,
        ]);

        // Expired window.
        CheckInCode::factory()->create([
            'event_id' => $event->id,
            'event_date' => $today,
            'code' => '100001',
            'starts_at' => $now->clone()->subHours(5),
            'expires_at' => $now->clone()->subHour(),
        ]);

        // Manually revoked.
        CheckInCode::factory()->create([
            'event_id' => $event->id,
            'event_date' => $today,
            'code' => '100002',
            'starts_at' => $now->clone()->subHour(),
            'expires_at' => $now->clone()->addHours(3),
            'revoked_at' => $now->clone()->subMinutes(10),
        ]);

        // Exhausted (single use already consumed).
        CheckInCode::factory()->create([
            'event_id' => $event->id,
            'event_date' => $today,
            'code' => '100003',
            'starts_at' => $now->clone()->subHour(),
            'expires_at' => $now->clone()->addHours(3),
            'max_uses' => 1,
            'uses_count' => 1,
        ]);
    }

    /**
     * @return array{starts_at: CarbonInterface, ends_at: CarbonInterface}
     */
    private function schedule(string $schedule, int $days): array
    {
        $start = match ($schedule) {
            'past' => Date::today()->subDays(10),
            'ongoing' => Date::today(),
            default => Date::today()->addDays(14),
        };

        return [
            'starts_at' => $start->clone()->setTime(9, 0),
            'ends_at' => $start->clone()->addDays($days - 1)->setTime(18, 0),
        ];
    }

    /**
     * @return array<string, CarbonInterface|null>
     */
    private function enrollmentTimestamps(EnrollmentStatus $status): array
    {
        $enrolledAt = Date::now()->subDays(3);
        $confirmedAt = Date::now()->subDays(2);
        $checkedInAt = Date::now()->subDay();

        return match ($status) {
            EnrollmentStatus::Pending,
            EnrollmentStatus::Waitlisted => ['enrolled_at' => $enrolledAt],
            EnrollmentStatus::Confirmed => ['enrolled_at' => $enrolledAt, 'confirmed_at' => $confirmedAt],
            EnrollmentStatus::CheckedIn => ['enrolled_at' => $enrolledAt, 'confirmed_at' => $confirmedAt, 'checked_in_at' => $checkedInAt],
            EnrollmentStatus::Attended => ['enrolled_at' => $enrolledAt, 'confirmed_at' => $confirmedAt, 'checked_in_at' => $checkedInAt, 'attended_at' => $checkedInAt],
            EnrollmentStatus::NoShow => ['enrolled_at' => $enrolledAt, 'confirmed_at' => $confirmedAt],
            EnrollmentStatus::Cancelled => ['enrolled_at' => $enrolledAt, 'cancelled_at' => Date::now()->subDay()],
            EnrollmentStatus::Rejected => ['enrolled_at' => $enrolledAt],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $schema
     * @return array<string, mixed>|null
     */
    private function fakeApplicationData(?array $schema): ?array
    {
        if ($schema === null) {
            return null;
        }

        $data = [];

        foreach ($schema as $field) {
            $data[$field['key']] = match ($field['type'] ?? 'text') {
                'select' => fake()->randomElement($field['options'] ?? ['Opção A']),
                'checkbox' => blank($field['options']) ? [] : fake()->randomElements($field['options'], fake()->numberBetween(1, count($field['options']))),
                'textarea' => fake()->paragraph(),
                default => fake()->sentence(),
            };
        }

        return $data;
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveParticipants(): Collection
    {
        $existing = User::query()->limit(self::PARTICIPANT_POOL)->get();
        $missing = self::PARTICIPANT_POOL - $existing->count();

        if ($missing > 0) {
            return $existing->concat(User::factory()->count($missing)->create());
        }

        return $existing;
    }
}
