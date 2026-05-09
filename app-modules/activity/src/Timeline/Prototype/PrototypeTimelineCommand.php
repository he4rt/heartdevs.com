<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Prototype;

use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class PrototypeTimelineCommand extends Command
{
    protected $signature = 'prototype:timeline';

    protected $description = 'PROTOTYPE — Interactive timeline state machine explorer';

    private TimelineState $state;

    public function handle(): int
    {
        $this->state = new TimelineState;

        while (true) {
            $this->render();

            $action = select('Action:', [
                'post' => 'Create post',
                'reply' => 'Reply to post',
                'pin' => 'Toggle pin',
                'mod' => 'Moderation event',
                'user' => 'Switch user',
                'tenant' => 'Switch tenant',
                'quit' => 'Quit',
            ]);

            match ($action) {
                'post' => $this->doCreatePost(),
                'reply' => $this->doReply(),
                'pin' => $this->doTogglePin(),
                'mod' => $this->doModeration(),
                'user' => $this->doSwitchUser(),
                'tenant' => $this->doSwitchTenant(),
                'quit' => exit(0),
            };
        }
    }

    private function render(): void
    {
        system('clear');

        $user = $this->state->activeUser();
        $tenant = $this->state->activeTenant();

        $this->line('');
        $this->line("\x1b[1m╔══════════════════════════════════════════════════════╗\x1b[0m");
        $this->line("\x1b[1m║  PROTOTYPE: Timeline State Machine                   ║\x1b[0m");
        $this->line("\x1b[1m╚══════════════════════════════════════════════════════╝\x1b[0m");
        $this->line('');
        $this->line("\x1b[1mActive:\x1b[0m \x1b[33m@{$user['username']}\x1b[0m \x1b[2min\x1b[0m \x1b[36m{$tenant['name']}\x1b[0m");
        $this->line('');

        $feed = $this->state->getFeed();
        $this->line("\x1b[1m── Feed ── ".count($feed)." top-level posts ──\x1b[0m");
        $this->line('');

        if ($feed === []) {
            $this->line("\x1b[2m  (empty feed — create a post!)\x1b[0m");
        } else {
            foreach ($feed as $entry) {
                $this->renderEntry($entry);
            }
        }

        $this->renderModerationEvents();
        $this->renderLog();
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function renderEntry(array $entry, int $indent = 0): void
    {
        $prefix = str_repeat('  ', $indent);
        $user = $this->state->users[$entry['user_id']];
        $pinIcon = $entry['pinned'] ? ' \x1b[33m📌\x1b[0m' : '';

        if ($entry['postable_type'] === 'post_entry') {
            $post = $this->state->getPostContent($entry['postable_id']);
            $content = $post['content'] ?? '(deleted)';
            $this->line("{$prefix}\x1b[1m[{$entry['id']}]\x1b[0m \x1b[33m@{$user['username']}\x1b[0m{$pinIcon}");
            $this->line(sprintf('%s  "%s"', $prefix, $content));
        } elseif ($entry['postable_type'] === 'moderation_event') {
            $event = $this->state->getModerationEvent($entry['postable_id']);
            $modInfo = $event['moderator_visible']
                ? " \x1b[2mby {$event['moderator_name']}\x1b[0m"
                : '';
            $this->line("{$prefix}\x1b[1m[{$entry['id']}]\x1b[0m \x1b[31m🛡️ {$event['type']}\x1b[0m — \x1b[1m{$event['subject_name']}\x1b[0m{$modInfo}");
            $this->line("{$prefix}  \x1b[2mReason: {$event['reason']}\x1b[0m");
        }

        $replies = $this->state->getReplies($entry['id']);
        if ($replies !== []) {
            $this->line("{$prefix}  \x1b[2m└─ ".count($replies)." replies:\x1b[0m");
            foreach ($replies as $reply) {
                $replyUser = $this->state->users[$reply['user_id']];
                $replyPost = $this->state->getPostContent($reply['postable_id']);
                $this->line("{$prefix}     \x1b[1m[{$reply['id']}]\x1b[0m \x1b[33m@{$replyUser['username']}\x1b[0m: \"{$replyPost['content']}\"");
                $this->line("{$prefix}       \x1b[2mroot_id={$reply['root_id']}  parent_id={$reply['parent_id']}\x1b[0m");
            }
        }

        $this->line('');
    }

    private function renderModerationEvents(): void
    {
        $events = array_filter(
            $this->state->moderationEvents,
            fn (array $e): bool => $e['tenant_id'] === $this->state->activeTenantId,
        );

        if ($events === []) {
            return;
        }

        $this->line("\x1b[1m── All Moderation Events ──\x1b[0m");
        foreach ($events as $event) {
            $inTimeline = array_any($this->state->timelines, fn ($tl) => $tl['postable_type'] === 'moderation_event' && $tl['postable_id'] === $event['id']);
            $status = $inTimeline ? "\x1b[32m✓ in timeline\x1b[0m" : "\x1b[2m✗ not published\x1b[0m";
            $this->line(sprintf('  [%s] %s → %s: %s', $event['id'], $event['type'], $event['subject_name'], $status));
        }

        $this->line('');
    }

    private function renderLog(): void
    {
        $recent = array_slice($this->state->log, -5);
        if ($recent === []) {
            return;
        }

        $this->line("\x1b[2m── Recent actions ──\x1b[0m");
        foreach ($recent as $msg) {
            $this->line("\x1b[2m  {$msg}\x1b[0m");
        }

        $this->line('');
    }

    private function doCreatePost(): void
    {
        $content = text('Post content:');
        if (mb_trim($content) === '') {
            return;
        }

        $this->state->createPost($content);
    }

    private function doReply(): void
    {
        $feed = $this->state->getFeed();
        $allWithReplies = array_merge($feed, ...array_map(
            fn (array $entry): array => $this->state->getReplies($entry['id']),
            $feed,
        ));

        if ($allWithReplies === []) {
            $this->state->log[] = '⚠️ No posts to reply to';

            return;
        }

        $options = [];
        foreach ($allWithReplies as $entry) {
            $user = $this->state->users[$entry['user_id']];
            $post = $this->state->getPostContent($entry['postable_id']);
            $label = $entry['parent_id'] !== null ? '  ↳ ' : '';
            $options[$entry['id']] = sprintf('%s[%s] @%s: "%s"', $label, $entry['id'], $user['username'], $post['content']);
        }

        $parentId = select('Reply to:', $options);
        $content = text('Reply content:');
        if (mb_trim($content) === '') {
            return;
        }

        $this->state->createReply($parentId, $content);
    }

    private function doTogglePin(): void
    {
        $feed = $this->state->getFeed();
        $ownPosts = array_filter($feed, fn (array $t): bool => $t['postable_type'] === 'post_entry');

        if ($ownPosts === []) {
            $this->state->log[] = '⚠️ No posts to pin';

            return;
        }

        $options = [];
        foreach ($ownPosts as $entry) {
            $user = $this->state->users[$entry['user_id']];
            $post = $this->state->getPostContent($entry['postable_id']);
            $pin = $entry['pinned'] ? ' 📌' : '';
            $options[$entry['id']] = sprintf('[%s] @%s: "%s"%s', $entry['id'], $user['username'], $post['content'], $pin);
        }

        $timelineId = select('Toggle pin on:', $options);
        $this->state->togglePin($timelineId);
    }

    private function doModeration(): void
    {
        $type = select('Moderation type:', [
            'Ban' => 'Ban (→ publishes to timeline)',
            'Kick' => 'Kick (→ publishes to timeline)',
            'Warn' => 'Warn (→ NO timeline entry)',
            'Mute' => 'Mute (→ NO timeline entry)',
        ]);

        $subject = text('Subject name (who is being moderated):');
        if (mb_trim($subject) === '') {
            return;
        }

        $reason = text('Reason:');
        if (mb_trim($reason) === '') {
            return;
        }

        $visible = select('Show moderator name publicly?', [
            'yes' => 'Yes — moderator visible',
            'no' => 'No — moderator hidden',
        ]);

        $this->state->createModerationEvent($type, $subject, $reason, $visible === 'yes');
    }

    private function doSwitchUser(): void
    {
        $options = [];
        foreach ($this->state->listUsers() as $user) {
            $active = $user['id'] === $this->state->activeUserId ? ' (current)' : '';
            $options[$user['id']] = sprintf('@%s — %s%s', $user['username'], $user['name'], $active);
        }

        $this->state->activeUserId = select('Switch to user:', $options);
        $user = $this->state->activeUser();
        $this->state->log[] = '👤 Switched to @'.$user['username'];
    }

    private function doSwitchTenant(): void
    {
        $options = [];
        foreach ($this->state->listTenants() as $tenant) {
            $active = $tenant['id'] === $this->state->activeTenantId ? ' (current)' : '';
            $options[$tenant['id']] = $tenant['name'].$active;
        }

        $this->state->activeTenantId = (int) select('Switch to tenant:', $options);
        $tenant = $this->state->activeTenant();
        $this->state->log[] = '🏠 Switched to '.$tenant['name'];
    }
}
