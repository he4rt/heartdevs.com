<?php

declare(strict_types=1);

namespace He4rt\Community\Feedback\Enums;

enum ReviewTypeEnum: string
{
    case APPROVED = 'approved';
    case DECLINED = 'declined';

    public static function getTypes(): array
    {
        return [
            self::APPROVED->value,
            self::DECLINED->value,
        ];
    }
}
