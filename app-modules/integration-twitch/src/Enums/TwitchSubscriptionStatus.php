<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TwitchSubscriptionStatus: string implements HasColor, HasLabel
{
    case Enabled = 'enabled';
    case VerificationPending = 'webhook_callback_verification_pending';
    case VerificationFailed = 'webhook_callback_verification_failed';
    case NotificationFailuresExceeded = 'notification_failures_exceeded';
    case AuthorizationRevoked = 'authorization_revoked';
    case ModeratorRemoved = 'moderator_removed';
    case UserRemoved = 'user_removed';
    case VersionRemoved = 'version_removed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Enabled => 'Enabled',
            self::VerificationPending => 'Verification Pending',
            self::VerificationFailed => 'Verification Failed',
            self::NotificationFailuresExceeded => 'Failures Exceeded',
            self::AuthorizationRevoked => 'Auth Revoked',
            self::ModeratorRemoved => 'Moderator Removed',
            self::UserRemoved => 'User Removed',
            self::VersionRemoved => 'Version Removed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Enabled => 'success',
            self::VerificationPending => 'warning',
            self::VerificationFailed,
            self::NotificationFailuresExceeded => 'danger',
            self::AuthorizationRevoked,
            self::ModeratorRemoved,
            self::UserRemoved,
            self::VersionRemoved => 'gray',
        };
    }
}
