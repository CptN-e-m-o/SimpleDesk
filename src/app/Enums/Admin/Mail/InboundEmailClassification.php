<?php

namespace App\Enums\Admin\Mail;

enum InboundEmailClassification: string
{
    case Human = 'human';

    case Loop = 'loop';

    case AutoReply = 'auto_reply';

    case DeliveryStatus = 'delivery_status';

    case Bulk = 'bulk';
}
