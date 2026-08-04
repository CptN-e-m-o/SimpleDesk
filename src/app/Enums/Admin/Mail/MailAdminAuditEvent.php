<?php

namespace App\Enums\Admin\Mail;

enum MailAdminAuditEvent: string
{
    case MailboxCreated = 'mailbox_created';
    case MailboxUpdated = 'mailbox_updated';
    case MailboxDeleted = 'mailbox_deleted';
    case MailboxRestored = 'mailbox_restored';
    case MailboxForceDeleted = 'mailbox_force_deleted';

    case ReplyParsingRuleCreated = 'reply_parsing_rule_created';
    case ReplyParsingRuleUpdated = 'reply_parsing_rule_updated';
    case ReplyParsingRuleDeleted = 'reply_parsing_rule_deleted';
    case ReplyParsingRuleRestored = 'reply_parsing_rule_restored';
    case ReplyParsingRuleForceDeleted = 'reply_parsing_rule_force_deleted';

    case ChannelCreated = 'channel_created';
    case ChannelUpdated = 'channel_updated';
    case ChannelDeleted = 'channel_deleted';
    case ChannelConnectionTested = 'channel_connection_tested';

    case ProviderConnectionCreated = 'provider_connection_created';
    case ProviderConnectionUpdated = 'provider_connection_updated';
    case ProviderConnectionDeleted = 'provider_connection_deleted';
    case ProviderConnectionTested = 'provider_connection_tested';

    case AntivirusConnectionTested = 'antivirus_connection_tested';

    case OAuthIntegrationCreated = 'oauth_integration_created';
    case OAuthIntegrationUpdated = 'oauth_integration_updated';
    case OAuthAuthorizationStarted = 'oauth_authorization_started';
    case OAuthAccountConnected = 'oauth_account_connected';
    case OAuthTokenRefreshed = 'oauth_token_refreshed';
    case OAuthConnectionTested = 'oauth_connection_tested';
    case OAuthAccountDisconnected = 'oauth_account_disconnected';
    case OAuthIntegrationDeleted = 'oauth_integration_deleted';
    case OAuthIntegrationRestored = 'oauth_integration_restored';
    case OAuthIntegrationForceDeleted = 'oauth_integration_force_deleted';
    case OAuthAuthorizationFailed = 'oauth_authorization_failed';

    case MailboxSyncRequested = 'mailbox_sync_requested';
    case OutgoingMessageRetryRequested = 'outgoing_message_retry_requested';
    case AttachmentRescanRequested = 'attachment_rescan_requested';

    case QuarantineRetryRequested = 'quarantine_retry_requested';
    case QuarantineIgnored = 'quarantine_ignored';
}
