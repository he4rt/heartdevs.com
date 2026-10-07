<?php

declare(strict_types=1);

return [
    'request_status' => [
        'pending' => [
            'label' => 'Pending',
            'description' => 'Waiting for a He4rt Delas moderator to decide.',
        ],
        'approved' => [
            'label' => 'Approved',
            'description' => 'The He4rt Delas tag shows on the profile.',
        ],
        'rejected' => [
            'label' => 'Rejected',
            'description' => 'Not approved. The person can request again after the cooldown.',
        ],
        'revoked' => [
            'label' => 'Removed',
            'description' => 'The tag was removed. The person can request again after the cooldown.',
        ],
    ],
    'action' => [
        'requested' => ['label' => 'Requested', 'description' => 'The person requested the He4rt Delas tag.'],
        'approved' => ['label' => 'Approved', 'description' => 'A pending request was approved.'],
        'rejected' => ['label' => 'Rejected', 'description' => 'A pending request was rejected.'],
        'granted' => ['label' => 'Granted', 'description' => 'The tag was granted directly, skipping the queue.'],
        'revoked' => ['label' => 'Removed the tag', 'description' => 'The tag was removed from someone who had it.'],
        'blocked' => ['label' => 'Blocked', 'description' => 'The person was prevented from requesting the tag.'],
        'unblocked' => ['label' => 'Unblocked', 'description' => 'The person can request the tag again.'],
        'moderator_added' => ['label' => 'Moderator added', 'description' => 'A lead gave the He4rt Delas moderator role.'],
        'reason_corrected' => ['label' => 'Reason corrected', 'description' => 'The reason of a decision was corrected; the original stays in the history.'],
        'moderator_removed' => ['label' => 'Moderator removed', 'description' => 'A lead removed the He4rt Delas moderator role.'],
    ],
    'triggered_by' => [
        'user' => ['label' => 'The person', 'description' => 'Done by the person who requested the tag.'],
        'moderator' => ['label' => 'Moderator', 'description' => 'Done by a He4rt Delas moderator.'],
        'lead' => ['label' => 'Lead', 'description' => 'Done by a He4rt Delas lead.'],
        'admin' => ['label' => 'Admin', 'description' => 'Done by a super admin.'],
        'system' => ['label' => 'System', 'description' => 'Done automatically by the platform.'],
    ],
    'eligibility' => [
        'can_request' => ['label' => 'Can request', 'description' => 'The person can request the He4rt Delas tag.'],
        'pending' => ['label' => 'Awaiting approval', 'description' => 'There is a pending request.'],
        'member' => ['label' => 'Member', 'description' => 'The person has the He4rt Delas tag.'],
        'cooldown' => ['label' => 'Cooldown', 'description' => 'A recent rejection or removal prevents a new request for now.'],
        'blocked' => ['label' => 'Unavailable', 'description' => 'The person is blocked from requesting the tag.'],
    ],
];
