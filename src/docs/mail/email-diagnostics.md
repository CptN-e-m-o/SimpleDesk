# Email Diagnostics

## Назначение и snapshot

`App\Services\Admin\Mail\Diagnostics\MailDiagnosticsService::dashboard()` формирует read-only snapshot без сетевых запросов: активные mailboxes, доступность и health каналов, статистику `EmailMessage` за 24 часа, зависшие состояния, последние 10 ошибок, antivirus и безопасные параметры очередей. Порог incoming processing берётся из `simpledesk-mail-ticketing.processing_lock_seconds`, outgoing — из `simpledesk-mail-ticketing.outgoing_replies.job.lock_seconds`.

Overall status вычисляется backend: `critical` при stuck messages, отсутствии рабочего incoming/outgoing при включённой функции или недоступном настроенном antivirus; `warning` при warning/failed/unknown доступном канале, ошибках за сутки или ненастроенном antivirus; иначе `healthy`.

## Health

Значения `MailboxHealthStatus`:

| Значение | Фактическое использование |
|---|---|
| `unknown` | Проверка ещё не дала подтверждённого результата. |
| `healthy` | Последняя проверка/операция успешна. |
| `warning` | Частичный успех provider connection либо нет активных связанных каналов. |
| `failed` | Проверка канала или всех связанных каналов завершилась ошибкой. |
| `disabled` | Сущность недоступна из-за отключённой конфигурации. |

## Маршруты, permissions и контроллеры

| Route name | Метод/URI | Permission | Обработчик |
|---|---|---|---|
| `admin.email.diagnostics.index` | `GET /admin/email/diagnostics` | `admin.mail.view_diagnostics` или `admin.mail.test_connections` или `admin.mail.view` | `Diagnostics\MailDiagnosticsController@index` |
| `admin.email.diagnostics.channels.test` | `POST /admin/email/diagnostics/channels/{channel}/test` | `admin.mail.test_connections` | `MailboxChannelConnectionTestController` |
| `admin.email.diagnostics.antivirus.test` | `POST /admin/email/diagnostics/antivirus/test` | `admin.mail.test_connections` | `AttachmentAntivirusConnectionTestController` |
| `admin.email.settings.provider-connections.test` | существующий provider test | `admin.mail.test_connections` | `MailProviderConnectionTestController` |

IMAP/SMTP проверяются через `MailChannelConnectionTestService` и реальный зарегистрированный driver; ClamAV — через существующий antivirus connection service. `MailProviderConnectionTester` последовательно проверяет только включённые связанные каналы и агрегирует результат: все успешны — `healthy`, часть — `warning`, ни один — `failed`, отсутствие каналов — `warning`.

## Результат и запись состояния

`MailConnectionTestResultData` содержит `successful`, `message`, `latencyMilliseconds`, `details`; `MailConnectionTestResource` формирует JSON. `MailChannelHealthRecorder::markSuccess()` выставляет `health_status=healthy`, `last_checked_at`, `last_success_at`, очищает error fields. `markFailure()` выставляет `failed`, `last_checked_at`, `last_error_at`, `last_error_code`, безопасный `last_error_message`. Provider health обновляется вместе с каналом и дополнительно агрегируется `MailProviderConnectionTester`.

`MailConnectionTestResultSanitizer` и `MailSensitiveDataRedactor` очищают message/details: sensitive keys, credentials в URL, Bearer/Basic headers, password/secret/token-подобные значения. Неожиданные исключения репортятся сервером, но пользователю возвращается обобщённая строка.

## Тесты

```bash
php artisan test tests/Feature/Admin/Mail/Diagnostics
php artisan test tests/Feature/Admin/Mail/Settings/AdminMailConnectionTestingTest.php
```

Они проверяют permissions, counts/stuck/overall status, soft-deleted records, отсутствие секретов, безопасные exception responses и работу существующих connection services без реальных сетевых подключений.

