<?php

// PROTOTYPE — Timeline state machine. Answers:
// 1. Does 1-level thread flattening work? (reply-to-reply → flat under root)
// 2. Does single-pin-per-user-per-tenant work? (pin B → unpin A)
// 3. Do only Ban/Kick create timeline entries? (Warn/Mute ignored)
// 4. Does tenant scoping isolate feeds?
// 5. Do replies stay out of the top-level feed?

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Prototype;

use Illuminate\Support\Facades\Date;
use RuntimeException;

final class TimelineState
{
    /** @var array<string, array{id: string, name: string, username: string}> */
    public readonly array $users;

    /** @var array<int, array{id: int, name: string}> */
    public readonly array $tenants;

    /** @var array<string, array<string, mixed>> */
    public array $timelines = [];

    /** @var array<string, array<string, mixed>> */
    public array $postEntries = [];

    /** @var array<string, array<string, mixed>> */
    public array $moderationEvents = [];

    public int $activeTenantId = 1;

    public string $activeUserId = 'u1';

    /** @var list<string> */
    public array $log = [];

    private int $seq = 0;

    public function __construct()
    {
        $this->users = [
            'u1' => ['id' => 'u1', 'name' => 'Daniel Reis', 'username' => 'danielhe4rt'],
            'u2' => ['id' => 'u2', 'name' => 'Kemi', 'username' => 'kemi'],
            'u3' => ['id' => 'u3', 'name' => 'João Silva', 'username' => 'jsilva'],
        ];
        $this->tenants = [
            1 => ['id' => 1, 'name' => 'He4rt Developers'],
            2 => ['id' => 2, 'name' => 'Laravel Brasil'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createPost(string $content): array
    {
        $postId = $this->nextId('pe');
        $this->postEntries[$postId] = [
            'id' => $postId,
            'content' => $content,
        ];

        $tlId = $this->nextId('tl');
        $entry = [
            'id' => $tlId,
            'user_id' => $this->activeUserId,
            'tenant_id' => $this->activeTenantId,
            'postable_type' => 'post_entry',
            'postable_id' => $postId,
            'root_id' => null,
            'parent_id' => null,
            'is_ignored' => false,
            'pinned' => false,
            'views' => 0,
            'created_at' => Date::now()->getTimestamp(),
        ];
        $this->timelines[$tlId] = $entry;

        $user = $this->activeUser();
        $this->log[] = sprintf('✅ @%s posted [%s]: "%s"', $user['username'], $tlId, $content);

        return $entry;
    }

    /**
     * @return array<string, mixed>
     */
    public function createReply(string $parentId, string $content): array
    {
        $parent = $this->timelines[$parentId] ?? throw new RuntimeException('Not found: '.$parentId);

        $rootId = $parent['root_id'] ?? $parent['id'];

        $postId = $this->nextId('pe');
        $this->postEntries[$postId] = [
            'id' => $postId,
            'content' => $content,
        ];

        $tlId = $this->nextId('tl');
        $entry = [
            'id' => $tlId,
            'user_id' => $this->activeUserId,
            'tenant_id' => $parent['tenant_id'],
            'postable_type' => 'post_entry',
            'postable_id' => $postId,
            'root_id' => $rootId,
            'parent_id' => $rootId,
            'is_ignored' => false,
            'pinned' => false,
            'views' => 0,
            'created_at' => Date::now()->getTimestamp(),
        ];
        $this->timelines[$tlId] = $entry;

        $user = $this->activeUser();
        $isFlattened = $parent['root_id'] !== null ? ' (FLATTENED → root)' : '';
        $this->log[] = sprintf('💬 @%s replied [%s] to [%s]%s: "%s"', $user['username'], $tlId, $parentId, $isFlattened, $content);

        return $entry;
    }

    public function togglePin(string $timelineId): void
    {
        $entry = &$this->timelines[$timelineId];
        throw_unless(isset($entry), RuntimeException::class, 'Not found: '.$timelineId);

        if ($entry['user_id'] !== $this->activeUserId) {
            $this->log[] = sprintf('🚫 Cannot pin [%s]: not your post', $timelineId);

            return;
        }

        if ($entry['pinned']) {
            $entry['pinned'] = false;
            $this->log[] = sprintf('📌 Unpinned [%s]', $timelineId);

            return;
        }

        $unpinned = [];
        foreach ($this->timelines as $id => &$t) {
            if ($t['user_id'] === $this->activeUserId
                && $t['tenant_id'] === $entry['tenant_id']
                && $t['pinned']) {
                $t['pinned'] = false;
                $unpinned[] = $id;
            }
        }

        unset($t);

        $entry['pinned'] = true;

        $msg = sprintf('📌 Pinned [%s]', $timelineId);
        if ($unpinned !== []) {
            $msg .= ' (unpinned: '.implode(', ', $unpinned).')';
        }

        $this->log[] = $msg;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function createModerationEvent(
        string $type,
        string $subjectName,
        string $reason,
        bool $moderatorVisible = true,
    ): ?array {
        $eventId = $this->nextId('me');
        $mod = $this->activeUser();

        $this->moderationEvents[$eventId] = [
            'id' => $eventId,
            'tenant_id' => $this->activeTenantId,
            'type' => $type,
            'subject_name' => $subjectName,
            'moderator_name' => $mod['name'],
            'moderator_visible' => $moderatorVisible,
            'reason' => $reason,
            'occurred_at' => Date::now()->getTimestamp(),
        ];

        if (!in_array($type, ['Ban', 'Kick'], true)) {
            $this->log[] = sprintf('🛡️ %s on %s — NO timeline entry (only Ban/Kick publish)', $type, $subjectName);

            return null;
        }

        $tlId = $this->nextId('tl');
        $entry = [
            'id' => $tlId,
            'user_id' => $this->activeUserId,
            'tenant_id' => $this->activeTenantId,
            'postable_type' => 'moderation_event',
            'postable_id' => $eventId,
            'root_id' => null,
            'parent_id' => null,
            'is_ignored' => false,
            'pinned' => false,
            'views' => 0,
            'created_at' => Date::now()->getTimestamp(),
        ];
        $this->timelines[$tlId] = $entry;

        $vis = $moderatorVisible ? 'visible' : 'hidden';
        $this->log[] = sprintf('🛡️ %s on %s → timeline [%s] (moderator: %s)', $type, $subjectName, $tlId, $vis);

        return $entry;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getFeed(?int $tenantId = null): array
    {
        $tenantId ??= $this->activeTenantId;

        $feed = array_filter(
            $this->timelines,
            fn (array $t): bool => $t['tenant_id'] === $tenantId
                && !$t['is_ignored']
                && $t['parent_id'] === null,
        );

        usort($feed, fn (array $a, array $b): int => $b['created_at'] <=> $a['created_at']);

        return array_values($feed);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getReplies(string $rootId): array
    {
        return array_values(array_filter(
            $this->timelines,
            fn (array $t): bool => $t['root_id'] === $rootId,
        ));
    }

    /**
     * @return array{id: string, name: string, username: string}
     */
    public function activeUser(): array
    {
        return $this->users[$this->activeUserId];
    }

    /**
     * @return array{id: int, name: string}
     */
    public function activeTenant(): array
    {
        return $this->tenants[$this->activeTenantId];
    }

    /**
     * @return list<array{id: string, name: string, username: string}>
     */
    public function listUsers(): array
    {
        return array_values($this->users);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function listTenants(): array
    {
        return array_values($this->tenants);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPostContent(string $postId): ?array
    {
        return $this->postEntries[$postId] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getModerationEvent(string $eventId): ?array
    {
        return $this->moderationEvents[$eventId] ?? null;
    }

    private function nextId(string $prefix): string
    {
        return $prefix.'-'.++$this->seq;
    }
}
