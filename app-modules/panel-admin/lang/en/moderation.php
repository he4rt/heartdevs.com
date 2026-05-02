<?php

declare(strict_types=1);

return [
    'navigation' => [
        'cluster' => 'Moderation',
        'cluster_breadcrumb' => 'Moderation',
        'back_to_admin' => 'Back to Admin',
        'group_moderation' => 'Moderation',
        'group_overview' => 'Overview',
        'group_config' => 'Configuration',
        'group_system' => 'System',
        'dashboard' => 'Mod Dashboard',
        'queue' => 'Queue',
        'appeals_queue' => 'Appeals Queue',
        'cases' => 'Cases',
        'rules' => 'Rules',
        'appeals' => 'Appeals',
        'audit_log' => 'Audit Log',
    ],

    'cases' => [
        'label' => 'Case',
        'plural' => 'Cases',
        'actions' => [
            'take_action' => 'Take Action',
            'dismiss' => 'Dismiss',
        ],
    ],

    'rules' => [
        'label' => 'Rule',
        'plural' => 'Rules',
        'actions' => [
            'test' => 'Test Rule',
            'test_input' => 'Paste a sample text to test against this rule.',
            'test_match' => 'Match! Violation: :violation | Severity: :severity | Action: :action',
            'test_no_match' => 'No match — the text did not trigger this rule.',
        ],
    ],

    'appeals' => [
        'label' => 'Appeal',
        'plural' => 'Appeals',
        'actions' => [
            'uphold' => 'Uphold',
            'overturn' => 'Overturn',
        ],
    ],

    'dashboard' => [
        'heading' => 'Moderation Overview',
    ],

    'queue' => [
        'navigation_label' => 'Queue',
        'heading' => 'Moderation Queue',
        'cases_count' => ':count case(s)',
        'cases_label' => 'cases',
        'priority_sort' => 'Priority ↓',
        'no_cases' => 'No cases found',
        'no_case_selected' => 'No case selected',
        'no_case_selected_subtitle' => 'Select a case from the list to see details.',
        'filters' => [
            'status' => 'Status',
            'platform' => 'Platform',
            'violation' => 'Violation',
            'severity' => 'Severity',
            'all' => 'All',
        ],
        'detail' => [
            'content' => 'Content',
            'ai_scores' => 'AI Scores',
            'reports' => 'Reports',
            'author' => 'Author',
            'member_since' => 'Member since :date',
            'infractions' => 'Infractions',
            'past_actions' => 'Past Actions',
            'suggested_action' => 'Suggestion: :action',
            'prior_offenses' => ':count prior offense(s) in the last 30 days',
            'no_history' => 'Clean record',
            'matched_rules' => 'Rules: :rules',
            'actions_heading' => 'Actions',
        ],
        'actions' => [
            'take_action' => 'Take Action',
            'escalate' => 'Escalate',
            'dismiss' => 'Dismiss',
            'success' => 'Action executed successfully',
            'escalated' => 'Case escalated',
            'dismissed' => 'Case dismissed',
        ],
    ],

    'appeal_queue' => [
        'navigation_label' => 'Appeals Queue',
        'heading' => 'Appeals Queue',
        'appeals_count' => ':count appeal(s)',
        'appeals_label' => 'appeals',
        'sla_sort' => 'SLA deadline ↑',
        'no_appeals' => 'No appeals found',
        'no_appeal_selected' => 'No appeal selected',
        'no_appeal_selected_subtitle' => 'Select an appeal from the list to review.',
        'filters' => [
            'status' => 'Status',
            'all' => 'All',
        ],
        'detail' => [
            'appeal' => 'Appeal',
            'reason' => 'Reason',
            'appellant' => 'Appellant',
            'sla_deadline' => 'SLA Deadline',
            'sla_overdue' => 'Overdue',
            'sla_remaining' => ':time remaining',
            'original_action' => 'Original Action',
            'action_type' => 'Action',
            'action_reason' => 'Reason',
            'action_moderator' => 'Moderator',
            'action_duration' => 'Duration',
            'original_case' => 'Original Case',
            'content' => 'Content',
            'ai_scores' => 'AI Scores',
            'violation_type' => 'Violation',
            'severity' => 'Severity',
            'reviewer_notes' => 'Reviewer Notes',
            'decision' => 'Decision',
        ],
        'actions' => [
            'uphold' => 'Uphold Decision',
            'overturn' => 'Overturn Decision',
            'upheld' => 'Decision upheld',
            'overturned' => 'Decision overturned',
        ],
    ],
];
