<?php

namespace App\Policies;

use App\Models\Reply;
use App\Models\User;

class ReplyPolicy
{
    public function update(User $user, Reply $reply): bool
    {
        if ($user->isAdminOrAgent()) {
            return true;
        }

        return $user->id === $reply->author_id;
    }

    public function delete(User $user, Reply $reply): bool
    {
        return $user->isAdminOrAgent() || $user->id === $reply->author_id;
    }
}
