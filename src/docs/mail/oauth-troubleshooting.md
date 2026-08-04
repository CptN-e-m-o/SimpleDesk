# Устранение проблем OAuth

Никогда не публикуйте в логах или тикетах client secret, code, verifier, access/refresh token, `Authorization` header, cookies или полный provider response. Для корреляции используйте integration/channel ID, безопасный error code, timestamp и `health_status`.

| Проблема | Где определяется | Причина и безопасное исправление |
|---|---|---|
| Invalid state | `MailOAuthStateService::consume()` | Другой browser/session/user, повторный callback или подмена. Начните Connect Account заново; state не копируйте. |
| Expired OAuth flow | `MailOAuthStateService` | Прошло более 10 минут. Запустите новый flow. |
| Nonce mismatch | `MicrosoftMailOAuthIdTokenValidator` | `id_token` не относится к flow. Повторите authorization в той же session. |
| Invalid Microsoft `id_token` | validator/JWKS | Неверная подпись, claims или key. Проверьте app/tenant/clock; не декодируйте token в публичном тикете. |
| Wrong audience | `audienceMatches()` | `aud`/`azp` не равны configured `client_id`. Исправьте Client ID и заново авторизуйтесь. |
| Wrong issuer | `validatedIdentity()` | `iss` не соответствует `tid` v2.0. Проверьте authority/account type. |
| Wrong tenant | `assertSpecificTenant()` | `tid` не равен configured GUID. Исправьте `<tenant-id>` или mode и reauthorize. |
| Missing email claim | validator / Google UserInfo | Нет валидного `email`, `preferred_username` или `upn`. Проверьте scopes/account policy. |
| Missing refresh token | `MailOAuthTokenService` | Provider не выдал offline token или он удалён. Reauthorize; Google flow уже использует `prompt=consent`. |
| Account must be authorized again | token service | Нет refresh token. Выполните Connect/Reauthorize. |
| IMAP authentication failed | `ImapExceptionMapper`, `ImapMailDriver` | Token/scope/username/server policy. Driver refresh-ит и повторяет один раз; затем проверьте channel и provider consent. |
| SMTP authentication failed | `SmtpExceptionMapper`, `SmtpMailDriver` | Token/scope, SMTP AUTH policy, username или TLS. После одного retry проверьте tenant/mailbox policy. |
| Provider rejected refresh | provider + token service | Revoked consent, expired refresh token или client mismatch. Health получает `oauth_token_refresh_failed`; reauthorize. |
| Callback URL mismatch | provider authorization/token endpoint | Зарегистрированный URI не совпадает с `route('admin.email.oauth-integrations.callback')`. Скопируйте точный Redirect URL из UI. |
| Google verification | Google consent | `https://mail.google.com/` — широкий scope. Добавьте test users либо завершите production verification. |
| Microsoft consent | Entra | Не выданы delegated IMAP/SMTP permissions или tenant запрещает consent. Получите admin consent по политике организации. |
| JWKS retrieval failure | `MicrosoftMailOAuthIdTokenValidator::keys()` | Сеть/прокси/Microsoft unavailable. Проверьте HTTPS egress и повторите позже. |
| Key rotation | validator `validate()` | Первый decode не находит актуальный key; код очищает cache и загружает JWKS один раз повторно. |
| Concurrent refresh | `MailOAuthTokenService` | Несколько workers обновляют один connection. Cache lock сериализует операции; проверьте общий cache backend и lock timeout. |
| Channel disabled after config change | `MailOAuthIntegrationService::update()` | Изменены provider/client ID/tenant settings; tokens очищены намеренно. Reauthorize, test, затем явно включите channels. |

## Дополнительная диагностика

Страница Email Diagnostics показывает безопасные `last_error_code`/`last_error_message`, а integration test проверяет связанные включённые каналы. Если каналов нет, provider connection получает `warning` и `provider_connection_has_no_channels`; это не подтверждение работы IMAP/SMTP. Команда `php artisan simpledesk:mail:refresh-oauth-tokens` обрабатывает активные токены, истекающие в пределах 10 минут, продолжает после единичной ошибки и выводит только ID integration.

