<?php

namespace App\Enums\Admin\Mail;

enum EmailQuarantineStage: string
{
    case InboundTicketing = 'inbound_ticketing';

    case InboundNormalization = 'inbound_normalization';

    case AttachmentProcessing = 'attachment_processing';

    case OutgoingDelivery = 'outgoing_delivery';
}
