<?php

namespace App\Enums\Admin\System;

enum BroadcastDriverType: string
{
    case Reverb = 'reverb';
    case Pusher = 'pusher';
    case Ably = 'ably';
}
