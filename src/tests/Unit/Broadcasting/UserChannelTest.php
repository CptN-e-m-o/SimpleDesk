<?php

namespace Tests\Unit\Broadcasting;

use App\Broadcasting\UserChannel;
use App\Models\User\User;
use PHPUnit\Framework\TestCase;

class UserChannelTest extends TestCase
{
    public function test_user_can_join_their_own_channel(): void
    {
        $user = new User();
        $user->id = 15;

        $channel = new UserChannel();

        $this->assertTrue(
            $channel->join($user, 15),
        );
    }

    public function test_user_cannot_join_another_users_channel(): void
    {
        $user = new User();
        $user->id = 15;

        $channel = new UserChannel();

        $this->assertFalse(
            $channel->join($user, 16),
        );
    }
}
