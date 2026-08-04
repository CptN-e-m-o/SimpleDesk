# Безопасность OAuth

## OAuth flow и хранение

`MailOAuthStateService` использует Authorization Code Flow с PKCE S256. В session под `mail_oauth_flows` хранятся случайные state (64 символа), verifier (96), nonce (64), `connection_id`, `user_id` и expiry. TTL — 10 минут, максимум — 10 активных flows. `consume()` удаляет state **до** валидации, поэтому callback нельзя повторить; flow также привязан к текущему администратору. Nonce передаётся обоим провайдерам, но в текущем коде явно валидируется только Microsoft `id_token`; Google identity получается через authenticated UserInfo и Google nonce отдельно не проверяется.

`MailProviderConnection.secret_configuration` имеет cast `encrypted:array` и входит в `$hidden`. Там находятся `client_secret`, `access_token`, `refresh_token`; frontend получает только `has_client_secret`. Access token не записывается в `MailboxChannel`: factories запрашивают его непосредственно перед runtime authentication.

`MailSensitiveDataRedactor` очищает чувствительные ключи, URL credentials, Bearer/Basic authorization и token/password/secret-like assignments. Connection result дополнительно проходит `MailConnectionTestResultSanitizer`. OAuth exceptions используют фиксированные безопасные сообщения; provider response не включается. В refresh catch исходное исключение намеренно не передаётся как `previous`, чтобы chain не раскрыл response или credentials.

## Refresh

`MailOAuthTokenService` считает token пригодным только если expiry дальше чем 5 минут. `Cache::lock("mail-oauth-refresh:{id}", 30)->block(10, …)` сериализует refresh. После lock service перечитывает запись и использует уже обновлённый конкурентом token. Новый refresh token сохраняется только если provider его вернул. При неудаче существующие encrypted tokens не очищаются; health переводится в `failed`, записывается безопасный `oauth_token_refresh_failed`.

## Проверка Microsoft `id_token`

`MicrosoftMailOAuthIdTokenValidator` использует `firebase/php-jwt`. JWKS загружается с authority `/discovery/v2.0/keys`, кешируется 3600 секунд; при первой decode-ошибке cache очищается и JWKS загружается повторно для ротации ключа. `JWK::parseKeySet(..., 'RS256')` выбирает ключ, включая `kid`, а `JWT::decode` проверяет подпись и временные claims библиотеки.

После decode код явно проверяет:

| Claim | Проверка |
|---|---|
| `aud` | содержит/равен `client_id`; при нескольких audiences `azp` обязан совпасть |
| `nonce` | constant-time совпадение с session nonce |
| `tid` | обязательный GUID; для `specific` совпадает с configured tenant GUID |
| `iss` | строго `https://login.microsoftonline.com/{tid}/v2.0` |
| `ver` | если присутствует, только `2.0` |
| `exp` | обязателен и находится в будущем |
| `iat` | обязателен и не более чем на 60 секунд в будущем |
| `sub` | обязателен как provider account id |
| email | первый валидный из `email`, `preferred_username`, `upn` |

Код отдельно не проверяет `nbf`; применимость registered claims при decode определяется поведением установленной `firebase/php-jwt`. Microsoft Exchange access token не декодируется как identity и не отправляется в Graph.

## Audit и ответы

Routes используют `mail.audit` для mutations/tests; callback success/failure пишет audit через `MailAdminAuditLogger`. Контекст проходит redaction и не должен содержать code, verifier, tokens, authorization headers или provider body. Секреты следует обозначать только placeholders: `<client-secret>`, `<access-token>`, `<refresh-token>`.
