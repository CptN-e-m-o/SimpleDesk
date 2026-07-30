<?php

namespace App\Enums\Admin\Mail;

enum MailAdminAuditEvent: string
{
    case MailboxCreated = 'mailbox_created';
    case MailboxUpdated = 'mailbox_updated';
    case MailboxDeleted = 'mailbox_deleted';

    case ChannelCreated = 'channel_created';
    case ChannelUpdated = 'channel_updated';
    case ChannelDeleted = 'channel_deleted';
    case ChannelConnectionTested = 'channel_connection_tested';

    case ProviderConnectionCreated = 'provider_connection_created';
    case ProviderConnectionUpdated = 'provider_connection_updated';
    case ProviderConnectionDeleted = 'provider_connection_deleted';
    case ProviderConnectionTested = 'provider_connection_tested';

    case AntivirusConnectionTested = 'antivirus_connection_tested';

    case MailboxSyncRequested = 'mailbox_sync_requested';
    case OutgoingMessageRetryRequested = 'outgoing_message_retry_requested';
    case AttachmentRescanRequested = 'attachment_rescan_requested';

    case QuarantineRetryRequested = 'quarantine_retry_requested';
    case QuarantineIgnored = 'quarantine_ignored';
}
