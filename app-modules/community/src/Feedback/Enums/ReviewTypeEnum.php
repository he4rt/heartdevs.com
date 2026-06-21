<?php

declare(strict_types=1);

namespace He4rt\Community\Feedback\Enums;

use App\Enums\Concerns\StringifyEnum;

enum ReviewTypeEnum: string
{
    use StringifyEnum;

    case APPROVED = 'approved';
    case DECLINED = 'declined';

    /**
     * @return array<int, string>
     */
    public static function getTypes(): array
    {
        return [
            self::APPROVED->value,
            self::DECLINED->value,
        ];
    }
}
