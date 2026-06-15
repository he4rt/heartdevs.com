<?php

declare(strict_types=1);

namespace App\Console\Commands;

use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\User\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

#[Description('Generate a comprehensive community analytics report')]
#[Signature('discord:community-report')]
class CommunityReport extends Command
{
    /** @var array<string, mixed> */
    private array $report = [];

    public function handle(): void
    {
        intro('He4rt Developers — Community Analytics Report');
        info('Generated at: '.now()->toDateTimeString());

        $this->reportOverview();
        $this->reportUserProfileCompleteness();
        $this->reportAuthenticationProviders();
        $this->reportGamification();
        $this->reportBadges();
        $this->reportEconomy();
        $this->reportMessagesAndVoice();
        $this->reportEvents();
        $this->reportMeetings();
        $this->reportFeedback();
        $this->reportSeasonalRankings();
        $this->reportDiscordScrapeCorrelation();

        $this->saveReportJson();

        outro('Community report complete!');
    }

    private function reportOverview(): void
    {
        info('=== 1. Overview ===');

        $totalUsers = DB::table('users')->count();
        $totalTenants = DB::table('tenants')->whereNull('deleted_at')->count();
        $totalEvents = DB::table('events')->count();
        $totalMessages = DB::table('messages')->count();
        $totalVoice = DB::table('voice_messages')->count();
        $totalCharacters = DB::table('characters')->count();

        table(
            headers: ['Metric', 'Count'],
            rows: [
                ['Total Users', number_format($totalUsers)],
                ['Total Tenants', number_format($totalTenants)],
                ['Total Events', number_format($totalEvents)],
                ['Total Messages', number_format($totalMessages)],
                ['Total Voice Sessions', number_format($totalVoice)],
                ['Total Characters', number_format($totalCharacters)],
            ],
        );

        $this->report['overview'] = ['totalUsers' => $totalUsers, 'totalTenants' => $totalTenants, 'totalEvents' => $totalEvents, 'totalMessages' => $totalMessages, 'totalVoice' => $totalVoice, 'totalCharacters' => $totalCharacters];
    }

    private function reportUserProfileCompleteness(): void
    {
        info('=== 2. User Profile Completeness ===');

        $totalUsers = DB::table('users')->count();

        if ($totalUsers === 0) {
            warning('No users found.');
            $this->report['profile_completeness'] = ['total_users' => 0];

            return;
        }

        $withInfo = DB::table('user_information')->distinct('user_id')->count('user_id');

        $infoFills = DB::table('user_information')
            ->selectRaw('
                COUNT(github_url) as github,
                COUNT(linkedin_url) as linkedin,
                COUNT(birthdate) as birthdate,
                COUNT(about) as about
            ')
            ->first();

        $withAddress = DB::table('user_address')->distinct('user_id')->count('user_id');

        $pct = fn (int $count): string => round($count / $totalUsers * 100, 1).'%';

        table(
            headers: ['Field', 'Count', '% of Users'],
            rows: [
                ['Has Information Record', number_format($withInfo), $pct($withInfo)],
                ['Has GitHub URL', number_format((int) $infoFills->github), $pct((int) $infoFills->github)],
                ['Has LinkedIn URL', number_format((int) $infoFills->linkedin), $pct((int) $infoFills->linkedin)],
                ['Has Birthdate', number_format((int) $infoFills->birthdate), $pct((int) $infoFills->birthdate)],
                ['Has About', number_format((int) $infoFills->about), $pct((int) $infoFills->about)],
                ['Has Address', number_format($withAddress), $pct($withAddress)],
            ],
        );

        $topCountries = DB::table('user_address')
            ->select('country', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get();

        if ($topCountries->isNotEmpty()) {
            info('Top 5 Countries');
            table(
                headers: ['Country', 'Count'],
                rows: $topCountries->map(fn ($r) => [$r->country, number_format($r->cnt)])->all(),
            );
        }

        $this->report['profile_completeness'] = [
            'total_users' => $totalUsers,
            'with_information' => $withInfo,
            'github_url' => (int) $infoFills->github,
            'linkedin_url' => (int) $infoFills->linkedin,
            'birthdate' => (int) $infoFills->birthdate,
            'about' => (int) $infoFills->about,
            'with_address' => $withAddress,
            'top_countries' => $topCountries->pluck('cnt', 'country')->toArray(),
        ];
    }

    private function reportAuthenticationProviders(): void
    {
        info('=== 3. Authentication & Providers ===');

        $userModelType = User::class;

        $providerCounts = DB::table('providers')
            ->select('provider', DB::raw('COUNT(*) as cnt'))
            ->where('model_type', $userModelType)
            ->groupBy('provider')
            ->pluck('cnt', 'provider');

        $discordCount = $providerCounts->get('discord', 0);
        $twitchCount = $providerCounts->get('twitch', 0);

        $withBoth = DB::table('providers')
            ->select('model_id')
            ->where('model_type', $userModelType)
            ->groupBy('model_id')
            ->havingRaw('COUNT(DISTINCT provider) = 2')
            ->count();

        $withAvatar = DB::table('providers')
            ->where('model_type', $userModelType)
            ->whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->distinct('model_id')
            ->count('model_id');

        $totalUsers = DB::table('users')->count();
        $usersWithProvider = DB::table('providers')
            ->where('model_type', $userModelType)
            ->distinct('model_id')
            ->count('model_id');
        $usersNoProvider = $totalUsers - $usersWithProvider;

        table(
            headers: ['Metric', 'Count'],
            rows: [
                ['Discord Providers', number_format($discordCount)],
                ['Twitch Providers', number_format($twitchCount)],
                ['Users with Both', number_format($withBoth)],
                ['Users with Avatar', number_format($withAvatar)],
                ['Users without ANY Provider', number_format($usersNoProvider)],
            ],
        );

        if ($usersNoProvider > 0) {
            warning($usersNoProvider.' users have no external identity linked.');
        }

        $this->report['authentication'] = [
            'discord' => $discordCount,
            'twitch' => $twitchCount,
            'both' => $withBoth,
            'with_avatar' => $withAvatar,
            'no_provider' => $usersNoProvider,
        ];
    }

    private function reportGamification(): void
    {
        info('=== 4. Gamification ===');

        $totalCharacters = DB::table('characters')->count();

        if ($totalCharacters === 0) {
            warning('No characters found.');
            $this->report['gamification'] = ['total_characters' => 0];

            return;
        }

        $zeroXp = DB::table('characters')->where('experience', 0)->count();

        // Bucket characters into level ranges in PHP instead of building dynamic
        // SQL. Each range maps to a half-open [lowerXp, upperXp) experience window
        // derived from the level => XP threshold table, so the queries stay static.
        $levelRanges = [
            '1-5' => [1, 5],
            '6-10' => [6, 10],
            '11-20' => [11, 20],
            '21-30' => [21, 30],
            '31-40' => [31, 40],
            '41-50' => [41, 50],
        ];

        /** @var array<string, int> $levelDistribution */
        $levelDistribution = [];

        foreach ($levelRanges as $label => [$minLevel, $maxLevel]) {
            $lowerXp = Character::LEVEL_THRESHOLDS[$minLevel];
            $upperXp = Character::LEVEL_THRESHOLDS[$maxLevel + 1] ?? null;

            $rangeQuery = DB::table('characters')->where('experience', '>=', $lowerXp);

            if ($upperXp !== null) {
                $rangeQuery->where('experience', '<', $upperXp);
            }

            $count = $rangeQuery->count();

            if ($count > 0) {
                $levelDistribution[$label] = $count;
            }
        }

        $percentiles = DB::selectOne('
            SELECT
                percentile_cont(0.25) WITHIN GROUP (ORDER BY experience) as p25,
                percentile_cont(0.50) WITHIN GROUP (ORDER BY experience) as p50,
                percentile_cont(0.75) WITHIN GROUP (ORDER BY experience) as p75,
                percentile_cont(0.90) WITHIN GROUP (ORDER BY experience) as p90,
                percentile_cont(0.99) WITHIN GROUP (ORDER BY experience) as p99,
                AVG(experience) as avg_xp,
                MAX(experience) as max_xp
            FROM characters
        ');

        $zeroXpPct = round($zeroXp / $totalCharacters * 100, 1);

        table(
            headers: ['Metric', 'Value'],
            rows: [
                ['Total Characters', number_format($totalCharacters)],
                ['Characters with 0 XP', number_format($zeroXp).sprintf(' (%s%%)', $zeroXpPct)],
                ['Avg XP', number_format((int) $percentiles->avg_xp)],
                ['Max XP', number_format((int) $percentiles->max_xp)],
            ],
        );

        $distributionRows = [];
        foreach ($levelDistribution as $range => $cnt) {
            $distributionRows[] = [
                $range,
                number_format($cnt),
                round($cnt / $totalCharacters * 100, 1).'%',
            ];
        }

        info('Level Distribution');
        table(
            headers: ['Level Range', 'Characters', '% of Total'],
            rows: $distributionRows,
        );

        info('XP Percentiles');
        table(
            headers: ['Percentile', 'XP'],
            rows: [
                ['P25', number_format((int) $percentiles->p25)],
                ['P50 (Median)', number_format((int) $percentiles->p50)],
                ['P75', number_format((int) $percentiles->p75)],
                ['P90', number_format((int) $percentiles->p90)],
                ['P99', number_format((int) $percentiles->p99)],
            ],
        );

        $this->report['gamification'] = [
            'total_characters' => $totalCharacters,
            'zero_xp' => $zeroXp,
            'avg_xp' => (int) $percentiles->avg_xp,
            'max_xp' => (int) $percentiles->max_xp,
            'percentiles' => [
                'p25' => (int) $percentiles->p25,
                'p50' => (int) $percentiles->p50,
                'p75' => (int) $percentiles->p75,
                'p90' => (int) $percentiles->p90,
                'p99' => (int) $percentiles->p99,
            ],
            'level_distribution' => $levelDistribution,
        ];
    }

    private function reportBadges(): void
    {
        info('=== 5. Badges ===');

        $totalBadges = DB::table('badges')->count();
        $activeBadges = DB::table('badges')->where('active', true)->count();
        $totalClaims = DB::table('characters_badges')->count();
        $totalCharacters = DB::table('characters')->count();

        $claimRate = $totalCharacters > 0 ? round($totalClaims / $totalCharacters, 2) : 0;

        $charactersWithBadge = DB::table('characters_badges')
            ->distinct('character_id')
            ->count('character_id');

        table(
            headers: ['Metric', 'Value'],
            rows: [
                ['Total Badges', number_format($totalBadges)],
                ['Active Badges', number_format($activeBadges)],
                ['Total Claims', number_format($totalClaims)],
                ['Characters with >= 1 Badge', number_format($charactersWithBadge)],
                ['Avg Claims per Character', (string) $claimRate],
            ],
        );

        $topBadges = DB::table('characters_badges')
            ->join('badges', 'badges.id', '=', 'characters_badges.badge_id')
            ->select('badges.name', DB::raw('COUNT(*) as claims'))
            ->groupBy('badges.id', 'badges.name')
            ->orderByDesc('claims')
            ->limit(5)
            ->get();

        if ($topBadges->isNotEmpty()) {
            info('Top 5 Most Claimed Badges');
            table(
                headers: ['Badge', 'Claims'],
                rows: $topBadges->map(fn ($r) => [$r->name, number_format($r->claims)])->all(),
            );
        }

        $this->report['badges'] = [
            'total_badges' => $totalBadges,
            'active_badges' => $activeBadges,
            'total_claims' => $totalClaims,
            'characters_with_badge' => $charactersWithBadge,
            'claims_per_character' => $claimRate,
            'top_5' => $topBadges->pluck('claims', 'name')->toArray(),
        ];
    }

    private function reportEconomy(): void
    {
        info('=== 6. Economy ===');

        $totalWallets = DB::table('wallets')->count();

        if ($totalWallets === 0) {
            warning('No wallets found.');
            $this->report['economy'] = ['total_wallets' => 0];

            return;
        }

        $currencyStats = DB::table('wallets')
            ->select(
                'currency',
                DB::raw('COUNT(*) as wallet_count'),
                DB::raw('SUM(balance) as total_balance'),
                DB::raw('AVG(balance) as avg_balance'),
                DB::raw('MAX(balance) as max_balance'),
            )
            ->groupBy('currency')
            ->get();

        info('Wallets by Currency');
        table(
            headers: ['Currency', 'Wallets', 'Total in Circulation', 'Avg Balance', 'Max Balance'],
            rows: $currencyStats->map(fn ($r) => [
                $r->currency,
                number_format($r->wallet_count),
                number_format((int) $r->total_balance),
                number_format((int) $r->avg_balance),
                number_format((int) $r->max_balance),
            ])->all(),
        );

        $txByType = DB::table('transactions')
            ->select('type', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('type')
            ->get();

        if ($txByType->isNotEmpty()) {
            info('Transactions by Type');
            table(
                headers: ['Type', 'Count', 'Total Amount'],
                rows: $txByType->map(fn ($r) => [
                    $r->type,
                    number_format($r->cnt),
                    number_format((int) $r->total_amount),
                ])->all(),
            );
        }

        $this->report['economy'] = [
            'total_wallets' => $totalWallets,
            'by_currency' => $currencyStats->keyBy('currency')->map(fn ($r) => [
                'wallets' => $r->wallet_count,
                'total_balance' => (int) $r->total_balance,
                'avg_balance' => (int) $r->avg_balance,
                'max_balance' => (int) $r->max_balance,
            ])->all(),
            'transactions_by_type' => $txByType->pluck('cnt', 'type')->toArray(),
        ];
    }

    private function reportMessagesAndVoice(): void
    {
        info('=== 7. Messages & Voice ===');

        $totalMessages = DB::table('messages')->count();
        $totalVoice = DB::table('voice_messages')->count();

        if ($totalMessages === 0 && $totalVoice === 0) {
            warning('No messages or voice sessions found.');
            $this->report['messages_voice'] = ['total_messages' => 0, 'total_voice' => 0];

            return;
        }

        $msgXp = (int) (DB::table('messages')->sum('obtained_experience'));
        $voiceXp = (int) (DB::table('voice_messages')->sum('obtained_experience'));

        $userModelType = User::class;

        $msgStats = DB::selectOne('
            SELECT
                COUNT(*) as unique_users,
                AVG(msg_count)::int as avg_msgs,
                percentile_cont(0.5) WITHIN GROUP (ORDER BY msg_count)::int as median_msgs
            FROM (
                SELECT p.model_id, COUNT(*) as msg_count
                FROM messages m
                JOIN providers p ON p.id = m.provider_id
                WHERE p.model_type = ?
                GROUP BY p.model_id
            ) sub
        ', [$userModelType]);

        table(
            headers: ['Metric', 'Value'],
            rows: [
                ['Total Messages', number_format($totalMessages)],
                ['Unique Users who Messaged', number_format((int) ($msgStats->unique_users ?? 0))],
                ['Avg Messages/User', number_format((int) ($msgStats->avg_msgs ?? 0))],
                ['Median Messages/User', number_format((int) ($msgStats->median_msgs ?? 0))],
                ['Total XP from Messages', number_format($msgXp)],
                ['Total Voice Sessions', number_format($totalVoice)],
                ['Total XP from Voice', number_format($voiceXp)],
            ],
        );

        $topChannels = DB::table('messages')
            ->select('channel_id', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('channel_id')
            ->groupBy('channel_id')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        if ($topChannels->isNotEmpty()) {
            info('Top 10 Channels by Message Count');
            table(
                headers: ['Channel ID', 'Messages'],
                rows: $topChannels->map(fn ($r) => [$r->channel_id, number_format($r->cnt)])->all(),
            );
        }

        $this->report['messages_voice'] = [
            'total_messages' => $totalMessages,
            'unique_messagers' => (int) ($msgStats->unique_users ?? 0),
            'avg_messages_per_user' => (int) ($msgStats->avg_msgs ?? 0),
            'median_messages_per_user' => (int) ($msgStats->median_msgs ?? 0),
            'total_message_xp' => $msgXp,
            'total_voice_sessions' => $totalVoice,
            'total_voice_xp' => $voiceXp,
            'top_channels' => $topChannels->pluck('cnt', 'channel_id')->toArray(),
        ];
    }

    private function reportEvents(): void
    {
        info('=== 8. Events ===');

        $totalEvents = DB::table('events')->count();

        if ($totalEvents === 0) {
            warning('No events found.');
            $this->report['events'] = ['total_events' => 0];

            return;
        }

        $totalAttendees = DB::table('events_attendees')->count();
        $uniqueAttendees = DB::table('events_attendees')->distinct('user_id')->count('user_id');
        $avgAttendees = (float) DB::table('events')->avg('attendees_count');

        $totalTalks = DB::table('events_talks')->count();
        $talksByStatus = DB::table('events_talks')
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $eventsByType = DB::table('events')
            ->select('event_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('event_type')
            ->pluck('cnt', 'event_type');

        table(
            headers: ['Metric', 'Value'],
            rows: [
                ['Total Events', number_format($totalEvents)],
                ['Total Attendee Registrations', number_format($totalAttendees)],
                ['Unique Attendees', number_format($uniqueAttendees)],
                ['Avg Attendees per Event', number_format($avgAttendees, 1)],
                ['Total Talk Submissions', number_format($totalTalks)],
                ['Talks Accepted', number_format($talksByStatus->get('accepted', 0))],
                ['Talks Pending', number_format($talksByStatus->get('pending', 0))],
                ['Talks Rejected', number_format($talksByStatus->get('rejected', 0))],
                ['Talks Done', number_format($talksByStatus->get('done', 0))],
            ],
        );

        if ($eventsByType->isNotEmpty()) {
            info('Events by Type');
            table(
                headers: ['Type', 'Count'],
                rows: $eventsByType->map(fn ($cnt, $type) => [$type, number_format($cnt)])->values()->all(),
            );
        }

        $topEvents = DB::table('events')
            ->select('title', 'attendees_count', 'event_type', 'event_at')
            ->orderByDesc('attendees_count')
            ->limit(5)
            ->get();

        if ($topEvents->isNotEmpty()) {
            info('Top 5 Events by Attendance');
            table(
                headers: ['Title', 'Attendees', 'Type', 'Date'],
                rows: $topEvents->map(fn ($r) => [
                    mb_substr((string) $r->title, 0, 40),
                    number_format($r->attendees_count),
                    $r->event_type,
                    $r->event_at ? mb_substr((string) $r->event_at, 0, 10) : 'N/A',
                ])->all(),
            );
        }

        $totalSponsors = DB::table('sponsors')->count();
        $totalSponsorships = DB::table('events_sponsors')->count();

        if ($totalSponsors > 0) {
            note(sprintf('Sponsors: %d total, %d event sponsorships', $totalSponsors, $totalSponsorships));
        }

        $this->report['events'] = [
            'total_events' => $totalEvents,
            'total_attendee_registrations' => $totalAttendees,
            'unique_attendees' => $uniqueAttendees,
            'avg_attendees' => round($avgAttendees, 1),
            'by_type' => $eventsByType->toArray(),
            'total_talks' => $totalTalks,
            'talks_by_status' => $talksByStatus->toArray(),
            'total_sponsors' => $totalSponsors,
            'total_sponsorships' => $totalSponsorships,
        ];
    }

    private function reportMeetings(): void
    {
        info('=== 9. Meetings ===');

        $totalMeetings = DB::table('meetings')->count();

        if ($totalMeetings === 0) {
            warning('No meetings found.');
            $this->report['meetings'] = ['total_meetings' => 0];

            return;
        }

        $totalParticipants = DB::table('meeting_participants')->count();
        $uniqueParticipants = DB::table('meeting_participants')->distinct('user_id')->count('user_id');
        $avgParticipants = round($totalParticipants / $totalMeetings, 1);

        table(
            headers: ['Metric', 'Value'],
            rows: [
                ['Total Meetings', number_format($totalMeetings)],
                ['Total Participations', number_format($totalParticipants)],
                ['Unique Participants', number_format($uniqueParticipants)],
                ['Avg Participants/Meeting', number_format($avgParticipants, 1)],
            ],
        );

        $topTypes = DB::table('meetings')
            ->join('meeting_types', 'meeting_types.id', '=', 'meetings.meeting_type_id')
            ->select('meeting_types.name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('meeting_types.id', 'meeting_types.name')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get();

        if ($topTypes->isNotEmpty()) {
            info('Top Meeting Types');
            table(
                headers: ['Type', 'Meetings'],
                rows: $topTypes->map(fn ($r) => [$r->name, number_format($r->cnt)])->all(),
            );
        }

        $this->report['meetings'] = [
            'total_meetings' => $totalMeetings,
            'total_participations' => $totalParticipants,
            'unique_participants' => $uniqueParticipants,
            'avg_participants' => $avgParticipants,
            'top_types' => $topTypes->pluck('cnt', 'name')->toArray(),
        ];
    }

    private function reportFeedback(): void
    {
        info('=== 10. Feedback ===');

        $totalFeedback = DB::table('feedbacks')->count();

        if ($totalFeedback === 0) {
            warning('No feedback found.');
            $this->report['feedback'] = ['total' => 0];

            return;
        }

        $totalReviews = DB::table('feedback_reviews')->count();
        $reviewsByStatus = DB::table('feedback_reviews')
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $pendingReview = $totalFeedback - $totalReviews;

        table(
            headers: ['Metric', 'Count'],
            rows: [
                ['Total Feedback', number_format($totalFeedback)],
                ['Reviewed', number_format($totalReviews)],
                ['Pending Review', number_format($pendingReview)],
                ['Approved', number_format($reviewsByStatus->get('approved', 0))],
                ['Declined', number_format($reviewsByStatus->get('declined', 0))],
            ],
        );

        $byType = DB::table('feedbacks')
            ->select('type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('type')
            ->orderByDesc('cnt')
            ->get();

        if ($byType->isNotEmpty()) {
            info('Feedback by Type');
            table(
                headers: ['Type', 'Count'],
                rows: $byType->map(fn ($r) => [$r->type, number_format($r->cnt)])->all(),
            );
        }

        $uniqueSenders = DB::table('feedbacks')->distinct('sender_id')->count('sender_id');
        $uniqueTargets = DB::table('feedbacks')->distinct('target_id')->count('target_id');
        note(sprintf('Unique senders: %d | Unique targets: %d', $uniqueSenders, $uniqueTargets));

        $this->report['feedback'] = [
            'total' => $totalFeedback,
            'reviewed' => $totalReviews,
            'pending' => $pendingReview,
            'approved' => $reviewsByStatus->get('approved', 0),
            'declined' => $reviewsByStatus->get('declined', 0),
            'by_type' => $byType->pluck('cnt', 'type')->toArray(),
            'unique_senders' => $uniqueSenders,
            'unique_targets' => $uniqueTargets,
        ];
    }

    private function reportSeasonalRankings(): void
    {
        info('=== 11. Seasonal Rankings ===');

        $totalSeasons = DB::table('seasons')->count();

        if ($totalSeasons === 0) {
            warning('No seasons found.');
            $this->report['seasons'] = ['total' => 0];

            return;
        }

        $totalRankings = DB::table('seasons_rankings')->count();

        $currentSeason = DB::table('seasons')
            ->where(static function (Builder $q): void {
                $q->whereNull('ended_at')
                    ->orWhere('ended_at', '>', now());
            })
            ->latest('started_at')
            ->first();

        $rows = [
            ['Total Seasons', number_format($totalSeasons)],
            ['Total Rankings Entries', number_format($totalRankings)],
        ];

        if ($currentSeason) {
            $rows[] = ['Current Season', $currentSeason->name];
            $rows[] = ['Started At', $currentSeason->started_at ? mb_substr((string) $currentSeason->started_at, 0, 10) : 'N/A'];
        }

        table(headers: ['Metric', 'Value'], rows: $rows);

        $top10 = collect();

        if ($currentSeason) {
            $top10 = DB::table('seasons_rankings')
                ->join('characters', 'characters.id', '=', 'seasons_rankings.character_id')
                ->join('users', 'users.id', '=', 'characters.user_id')
                ->where('seasons_rankings.season_id', $currentSeason->id)
                ->select(
                    'users.username',
                    'seasons_rankings.ranking_position',
                    'seasons_rankings.level',
                    'seasons_rankings.experience',
                    'seasons_rankings.messages_count',
                )
                ->orderBy('seasons_rankings.ranking_position')
                ->limit(10)
                ->get();

            if ($top10->isNotEmpty()) {
                info('Top 10 — Season: '.$currentSeason->name);
                table(
                    headers: ['#', 'Username', 'Level', 'XP', 'Messages'],
                    rows: $top10->map(fn ($r) => [
                        $r->ranking_position,
                        $r->username,
                        $r->level,
                        number_format($r->experience),
                        number_format($r->messages_count),
                    ])->all(),
                );
            }
        }

        // Fallback: live top 10 from characters table
        if ($top10->isEmpty()) {
            $liveTop = DB::table('characters')
                ->join('users', 'users.id', '=', 'characters.user_id')
                ->select('users.username', 'characters.experience', 'characters.reputation')
                ->orderByDesc('characters.experience')
                ->limit(10)
                ->get();

            if ($liveTop->isNotEmpty()) {
                info('Live Top 10 by XP (from characters table)');
                table(
                    headers: ['#', 'Username', 'XP', 'Reputation'],
                    rows: $liveTop->values()->map(static fn ($r, int $i): array => [
                        (string) ($i + 1),
                        (string) $r->username,
                        number_format((int) $r->experience),
                        number_format((int) $r->reputation),
                    ])->all(),
                );
            }
        }

        $this->report['seasons'] = [
            'total' => $totalSeasons,
            'total_rankings' => $totalRankings,
            'current_season' => $currentSeason?->name,
        ];
    }

    private function reportDiscordScrapeCorrelation(): void
    {
        info('=== 12. Discord Scrape Correlation ===');

        if (!Storage::disk('local')->exists('discord/members.json')) {
            warning('discord/members.json not found. Skipping scrape correlation.');
            $this->report['discord_correlation'] = ['skipped' => true];

            return;
        }

        /** @var array<string, mixed> $membersData */
        $membersData = (array) json_decode((string) Storage::disk('local')->get('discord/members.json'), true);
        /** @var list<array<string, mixed>> $members */
        $members = is_array($membersData['members'] ?? null) ? $membersData['members'] : [];
        $scrapedDiscordIds = collect($members)
            ->map(static fn (array $m): string => (string) ($m['user']['id'] ?? ''))
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values();

        info(sprintf('Scraped Discord members: %d (from %s)', $scrapedDiscordIds->count(), $membersData['scraped_at']));

        $userModelType = User::class;
        $platformDiscordIds = DB::table('providers')
            ->where('provider', 'discord')
            ->where('model_type', $userModelType)
            ->pluck('provider_id');

        info('Platform Discord providers: '.$platformDiscordIds->count());

        $notOnPlatform = $scrapedDiscordIds->diff($platformDiscordIds);
        $notInDiscord = $platformDiscordIds->diff($scrapedDiscordIds);
        $overlap = $scrapedDiscordIds->intersect($platformDiscordIds);

        table(
            headers: ['Metric', 'Count'],
            rows: [
                ['Discord Members (scraped)', number_format($scrapedDiscordIds->count())],
                ['Platform Discord Users', number_format($platformDiscordIds->count())],
                ['Overlap (in both)', number_format($overlap->count())],
                ['In Discord, NOT on Platform', number_format($notOnPlatform->count())],
                ['On Platform, NOT in Discord', number_format($notInDiscord->count())],
            ],
        );

        if ($notInDiscord->count() > 0) {
            warning($notInDiscord->count().' platform users are not in the Discord server (may have left).');
        }

        // GitHub correlation
        $githubReport = ['skipped' => true];

        if (Storage::disk('local')->exists('discord/github_connections.json')) {
            /** @var array<string, mixed> $githubData */
            $githubData = (array) json_decode((string) Storage::disk('local')->get('discord/github_connections.json'), true);
            /** @var list<array<string, mixed>> $connections */
            $connections = is_array($githubData['connections'] ?? null) ? $githubData['connections'] : [];
            $githubConnections = collect($connections);

            info('GitHub connections from scrape: '.$githubConnections->count());

            $scrapedGithub = $githubConnections->keyBy('discord_id');

            $platformGithub = DB::table('providers')
                ->join('user_information', 'providers.model_id', '=', 'user_information.user_id')
                ->where('providers.provider', 'discord')
                ->where('providers.model_type', $userModelType)
                ->whereNotNull('user_information.github_url')
                ->where('user_information.github_url', '!=', '')
                ->select('providers.provider_id as discord_id', 'user_information.github_url')
                ->get()
                ->keyBy('discord_id');

            $matches = 0;
            $conflicts = 0;
            $newFromScrape = 0;
            $conflictDetails = [];

            foreach ($scrapedGithub as $discordId => $conn) {
                $scrapedGhUser = mb_strtolower((string) $conn['github_username']);

                if ($platformGithub->has($discordId)) {
                    $dbGithubUrl = mb_strtolower((string) $platformGithub->get($discordId)->github_url);

                    if (str_contains($dbGithubUrl, $scrapedGhUser)) {
                        $matches++;
                    } else {
                        $conflicts++;

                        if (count($conflictDetails) < 10) {
                            $conflictDetails[] = [
                                $conn['discord_username'],
                                $conn['github_username'],
                                $platformGithub->get($discordId)->github_url,
                            ];
                        }
                    }
                } else {
                    $newFromScrape++;
                }
            }

            table(
                headers: ['GitHub Metric', 'Count'],
                rows: [
                    ['GitHub from Scrape', number_format($githubConnections->count())],
                    ['GitHub in DB', number_format($platformGithub->count())],
                    ['Matches (same)', number_format($matches)],
                    ['Conflicts (different)', number_format($conflicts)],
                    ['New (scraped only)', number_format($newFromScrape)],
                ],
            );

            if ($conflicts > 0 && $conflictDetails !== []) {
                warning(sprintf('Found %d GitHub URL conflicts:', $conflicts));
                table(
                    headers: ['Discord User', 'Scraped GitHub', 'DB GitHub URL'],
                    rows: $conflictDetails,
                );
            }

            $githubReport = [
                'scraped_count' => $githubConnections->count(),
                'db_count' => $platformGithub->count(),
                'matches' => $matches,
                'conflicts' => $conflicts,
                'new_from_scrape' => $newFromScrape,
            ];
        } else {
            note('discord/github_connections.json not found. Skipping GitHub correlation.');
        }

        $this->report['discord_correlation'] = [
            'scraped_members' => $scrapedDiscordIds->count(),
            'platform_discord_users' => $platformDiscordIds->count(),
            'overlap' => $overlap->count(),
            'not_on_platform' => $notOnPlatform->count(),
            'not_in_discord' => $notInDiscord->count(),
            'github' => $githubReport,
        ];
    }

    private function saveReportJson(): void
    {
        $this->report['generated_at'] = now()->toISOString();

        Storage::disk('local')->put(
            'discord/community_report.json',
            json_encode($this->report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );

        info('Full report saved to storage/app/private/discord/community_report.json');
    }
}
