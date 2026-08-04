# OAuth-провайдеры

Реализации выбираются `MailOAuthProviderRegistry`: `GoogleMailOAuthProvider` и `MicrosoftMailOAuthProvider`, обе наследуют безопасную HTTP/token обработку `AbstractMailOAuthProvider`. Запросы используют Laravel HTTP client с connect timeout 5 секунд и timeout 10 секунд.

## Google

| Назначение | Значение |
|---|---|
| Authorization | `https://accounts.google.com/o/oauth2/v2/auth` |
| Token | `https://oauth2.googleapis.com/token` |
| Revoke | `https://oauth2.googleapis.com/revoke` |
| UserInfo | `https://openidconnect.googleapis.com/v1/userinfo` |
| Scopes | `openid`, `email`, `https://mail.google.com/` |

Authorization URL включает `access_type=offline`, `prompt=consent`, state, nonce и PKCE S256. После exchange identity (`sub`, `email`) получается через OpenID UserInfo с access token; текущая реализация не валидирует Google `id_token`/nonce. При refresh отсутствие нового `refresh_token` не затирает сохранённый. Gmail scope предоставляет широкий доступ к почте; публичному production-приложению может потребоваться Google OAuth verification.

Настройка:

1. В Google Cloud создайте OAuth 2.0 Client типа Web application и настройте consent screen.
2. Разрешите scopes из таблицы; не добавляйте лишние API scopes.
3. В Authorized redirect URIs добавьте URL, который показывает форма SimpleDesk: `<simpledesk-base-url>/admin/email/oauth-integrations/callback`.
4. В SimpleDesk укажите Integration Name, Google, `<client-id>`, `<client-secret>`, включите integration и выполните Connect Account.

## Microsoft

Authority выбирается по `MailOAuthTenantMode`: `common`, `organizations` либо URL-encoded `tenant_identifier` для `specific`. Endpoints:

`https://login.microsoftonline.com/{authority}/oauth2/v2.0/authorize`

`https://login.microsoftonline.com/{authority}/oauth2/v2.0/token`

Scopes: `openid`, `email`, `offline_access`, `https://outlook.office.com/IMAP.AccessAsUser.All`, `https://outlook.office.com/SMTP.Send`. Exchange response обязан содержать проверяемый `id_token`; identity берётся из его claims. Exchange access token предназначен для Exchange Online XOAUTH2, поэтому Graph UserInfo не вызывается. На refresh identity сохраняется из существующей записи. `revoke()` в текущей Microsoft реализации пуст; disconnect всё равно локально очищает токены.

Настройка:

1. В Microsoft Entra ID → App registrations создайте приложение.
2. Supported account types согласуйте с mode: multi-tenant для `common`/`organizations`, tenant-bound registration для `specific`.
3. Добавьте Web redirect URI `<simpledesk-base-url>/admin/email/oauth-integrations/callback`.
4. В API permissions добавьте перечисленные delegated Exchange Online permissions и OpenID/offline scopes; предоставьте consent согласно политике tenant.
5. Создайте client secret и внесите в SimpleDesk `<client-id>`, `<client-secret>`, mode и, для `specific`, `<tenant-id>`.

Redirect URL формируется `route('admin.email.oauth-integrations.callback')`; регистрируйте точное отображаемое приложением значение, включая scheme, host, port и base path.
