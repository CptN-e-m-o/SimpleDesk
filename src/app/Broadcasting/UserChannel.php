<?php

namespace App\Broadcasting;

use App\Models\User\User;

final class UserChannel
{
    public function join(User $user, int $userId): bool
    {
        return $user->id === $userId;
    }
}
