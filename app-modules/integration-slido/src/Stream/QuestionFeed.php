<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Stream;

use He4rt\IntegrationSlido\Data\SlidoQuestion;
use He4rt\IntegrationSlido\Data\SlidoQuestionPatch;

final class QuestionFeed
{
    private const array SNAPSHOT_EVENTS = [
        'newQuestion',
        'slido.event.question.update',
    ];

    private const array PATCH_EVENTS = [
        'slido.event.question.patch',
        'slido.event.question.admin.action',
    ];

    private const array IGNORED_EVENTS = [
        'slido.event.analytics.update',
        'slido.event.statistics.update',
    ];

    public function questionFrom(SocketIoFrame $frame): ?SlidoQuestion
    {
        return $this->isAnyOf($frame, self::SNAPSHOT_EVENTS) ? SlidoQuestion::tryFromPayload($frame->params()) : null;
    }

    public function patchFrom(SocketIoFrame $frame): ?SlidoQuestionPatch
    {
        return $this->isAnyOf($frame, self::PATCH_EVENTS) ? SlidoQuestionPatch::tryFromPayload($frame->params()) : null;
    }

    public function isKnown(SocketIoFrame $frame): bool
    {
        return $this->isAnyOf($frame, [...self::SNAPSHOT_EVENTS, ...self::PATCH_EVENTS, ...self::IGNORED_EVENTS]);
    }

    /**
     * @param  list<string>  $eventNames
     */
    private function isAnyOf(SocketIoFrame $frame, array $eventNames): bool
    {
        return $frame->kind === FrameKind::Event && in_array($frame->eventName, $eventNames, strict: true);
    }
}
