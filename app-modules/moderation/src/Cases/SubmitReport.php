<?php

declare(strict_types=1);

namespace He4rt\Moderation\Cases;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Cases\Models\ModerationReport;
use He4rt\Moderation\Classification\Jobs\ClassifyContent;
use He4rt\Moderation\Classification\Jobs\IngestContent;
use He4rt\Moderation\Classification\Jobs\RouteDecision;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\ViolationType;

final readonly class SubmitReport
{
    public function handle(
        User $reporter,
        ModerationContentDTO $contentDTO,
        ViolationType $reason,
        ?string $details,
        Platform $platform,
    ): ModerationCase {
        $existingCase = ModerationCase::query()
            ->where('content_id', $contentDTO->contentId)
            ->where('content_type', $contentDTO->contentType)
            ->whereIn('status', [CaseStatus::Pending->value, CaseStatus::Assigned->value])
            ->first();

        if ($existingCase) {
            $this->addReport($existingCase, $reporter, $reason, $details, $platform);
            $existingCase->increment('priority', 10);

            return $existingCase;
        }

        $case = new IngestContent($contentDTO, CaseSource::UserReport)->handle();
        $this->addReport($case, $reporter, $reason, $details, $platform);

        new ClassifyContent($case)->handle();
        $case->refresh();
        new RouteDecision($case)->handle();

        return $case->refresh();
    }

    private function addReport(
        ModerationCase $case,
        User $reporter,
        ViolationType $reason,
        ?string $details,
        Platform $platform,
    ): void {
        ModerationReport::query()->create([
            'case_id' => $case->id,
            'reporter_id' => $reporter->id,
            'reason' => $reason,
            'details' => $details,
            'platform' => $platform,
        ]);
    }
}
