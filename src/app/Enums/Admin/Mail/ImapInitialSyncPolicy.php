<?php

namespace App\Enums\Admin\Mail;

enum ImapInitialSyncPolicy: string
{
    case FromNow = 'from_now';

    case Unseen = 'unseen';

    case RecentDays = 'recent_days';

    case All = 'all';

    public static function resolve(
        mixed $value
    ): self {
        if ($value instanceof self) {
            return $value;
        }

        if (!is_string($value)) {
            return self::FromNow;
        }

        return self::tryFrom(
            strtolower(
                trim($value)
            )
        ) ?? self::FromNow;
    }
}
