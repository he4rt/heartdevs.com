<?php

declare(strict_types=1);

return [
    'classification' => [
        'article' => ['tier' => 'high', 'coins_min' => 100, 'coins_max' => 300],
        'pr_merged' => ['tier' => 'high', 'coins_min' => 80, 'coins_max' => 250],
        'mentoring' => ['tier' => 'high', 'coins_min' => 50, 'coins_max' => 150],
        'squad_project' => ['tier' => 'high', 'coins_min' => 200, 'coins_max' => 500],
        'referral' => ['tier' => 'medium', 'coins_min' => 20, 'coins_max' => 30],
        'peer_review' => ['tier' => 'medium', 'coins_min' => 10, 'coins_max' => 25],
        'call_participation' => ['tier' => 'medium', 'coins_min' => 15, 'coins_max' => 30],
        'forum_debate' => ['tier' => 'medium', 'coins_min' => 10, 'coins_max' => 20],
        'content_share' => ['tier' => 'low', 'coins_min' => 5, 'coins_max' => 10],
        'engagement' => ['tier' => 'low', 'coins_min' => 1, 'coins_max' => 3],
        'repo_star' => ['tier' => 'low', 'coins_min' => 2, 'coins_max' => 2],
        'message' => ['tier' => 'low', 'coins_min' => 1, 'coins_max' => 2],
        'voice' => ['tier' => 'low', 'coins_min' => 1, 'coins_max' => 3],
    ],

    'auto_approve_tiers' => ['low', 'medium'],

    'engagement_formula' => [
        'reactions_multiplier' => 0.5,
        'reactions_cap' => 25,
        'bookmarks_multiplier' => 1.0,
        'bookmarks_cap' => 15,
        'comments_multiplier' => 2.0,
        'comments_cap' => 30,
    ],

    'xp_multiplier' => 1,
];
