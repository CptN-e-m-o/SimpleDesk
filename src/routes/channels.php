<?php

use App\Broadcasting\UserChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}', UserChannel::class);
