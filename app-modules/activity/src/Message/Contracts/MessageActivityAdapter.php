<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Contracts;

use He4rt\Activity\Message\Data\AttachmentData;
use He4rt\Activity\Message\Data\EmbedData;
use He4rt\Activity\Message\Data\MembershipEventData;
use He4rt\Activity\Message\Data\MentionData;
use He4rt\Activity\Message\Data\ReplyData;
use He4rt\Activity\Message\Data\ThreadData;
use He4rt\Activity\Message\Enums\MessageKind;
use He4rt\Activity\Message\Enums\MessageSourceKind;

interface MessageActivityAdapter
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function messageKind(array $raw): MessageKind;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function rawMessageType(array $raw): ?int;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function sourceKind(array $raw): MessageSourceKind;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function isPinned(array $raw): bool;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function editedAt(array $raw): ?string;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function mentionsEveryone(array $raw): bool;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function mentionRoleCount(array $raw): int;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function extractReply(array $raw): ?ReplyData;

    /**
     * @param  array<string, mixed>  $raw
     * @return list<MentionData>
     */
    public function extractMentions(array $raw): array;

    /**
     * @param  array<string, mixed>  $raw
     * @return list<AttachmentData>
     */
    public function extractAttachments(array $raw): array;

    /**
     * @param  array<string, mixed>  $raw
     * @return list<EmbedData>
     */
    public function extractEmbeds(array $raw): array;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function extractMembershipEvent(array $raw): ?MembershipEventData;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function extractThread(array $raw): ?ThreadData;
}
