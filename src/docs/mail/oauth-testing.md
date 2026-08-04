# Тестирование OAuth mail

Автоматические тесты не обращаются к Google, Microsoft, IMAP или SMTP: применяются `Http::fake()`, mocks, test clocks и in-memory SQLite.

## Карта тестов

| Файл | Основные сценарии |
|---|---|
| `tests/Feature/Admin/Mail/OAuth/MailOAuthIntegrationTest.php` | CRUD/permissions, encrypted secret и отсутствие в props, blank secret, provider/client ID changes, tenant validation, callback/user binding, safe errors, disconnect, soft delete/restore/force policy, connection test. |
| `tests/Unit/Admin/Mail/OAuth/MailOAuthStateServiceTest.php` | state, PKCE S256, nonce storage, TTL, one-time consume, user binding, максимум 10 flows. |
| `tests/Unit/Admin/Mail/OAuth/MailOAuthProviderTest.php` | Google/Microsoft URLs/scopes, Microsoft verified `id_token`, token parsing, сохранение старого refresh token, redacted provider failures. |
| `tests/Unit/Admin/Mail/OAuth/MailOAuthTokenServiceTest.php` | expiry window, refresh, сохранение refresh token, безопасное исключение без previous provider exception. |
| `tests/Unit/Admin/Mail/OAuth/MicrosoftMailOAuthIdTokenValidatorTest.php` | подпись/JWKS, nonce, audience, expiry, issuer, tenant, email claims. |
| `tests/Unit/Admin/Mail/OAuth/MailOAuthChannelConfigurationTest.php` | runtime token в IMAP/SMTP и regression Password SMTP. |
| `tests/Unit/Admin/Mail/Drivers/Imap/ImapMailDriverOAuthRetryTest.php` | disconnect, один refresh/retry, отсутствие третьей попытки и Password retry. |
| `tests/Unit/Admin/Mail/Drivers/Smtp/SmtpMailDriverOAuthRetryTest.php` | stop/new transport/new message, один refresh/retry и Password regression. |
| `tests/Feature/Admin/Mail/Diagnostics/*.php` | snapshot, permissions, health, stuck и безопасные responses. |
| `tests/Feature/Admin/Mail/Settings/AdminMailConnectionTestingTest.php` | channel/provider/antivirus connection endpoints и audit/redaction. |

## Команды

Запускайте из `src/`:

```bash
php artisan test tests/Unit/Admin/Mail/OAuth
php artisan test tests/Feature/Admin/Mail/OAuth/MailOAuthIntegrationTest.php
php artisan test --filter=OAuth
php artisan test tests/Unit/Admin/Mail/Drivers/Smtp/SmtpMailDriverOAuthRetryTest.php
php artisan test tests/Unit/Admin/Mail/Drivers/Imap/ImapMailDriverOAuthRetryTest.php
php artisan test tests/Feature/Admin/Mail/Diagnostics
php artisan test
vendor/bin/pint --test
```

`--filter=Smtp` и `--filter=Imap` могут захватить не все нужные классы либо посторонние тесты; для retry надёжнее точные пути выше.

## Ручной end-to-end

1. Настройте Google integration и пройдите consent; убедитесь, что видны identity/expiry, но не tokens.
2. Создайте/привяжите incoming IMAP OAuth2 channel и выполните Test Connection, затем реальный sync.
3. Создайте/привяжите outgoing SMTP OAuth2 channel, выполните test и отправьте тестовый ответ обычным mail pipeline.
4. Повторите для Microsoft 365 с нужным tenant mode.
5. На тестовом окружении добейтесь expiry/повреждения access token при рабочем refresh token и подтвердите один refresh/retry.
6. Проверьте UI, JSON, audit и application logs на отсутствие `<client-secret>`, `<access-token>`, `<refresh-token>`, `<authorization-code>` и verifier.

