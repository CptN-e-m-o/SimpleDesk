<?php

namespace App\Enums\Admin\Mail;

enum EmailMessageStatus: string
{
    case Preparing = 'preparing';

    case Received = 'received';

    case Queued = 'queued';

    case Processing = 'processing';

    case Processed = 'processed';

    case Sending = 'sending';

    case Sent = 'sent';
    case Delivered = 'delivered';

    case Failed = 'failed';
    case Rejected = 'rejected';
    case Bounced = 'bounced';
    case Complained = 'complained';
}
