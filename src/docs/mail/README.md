# Почтовый модуль SimpleDesk

Этот раздел описывает реализованную почтовую подсистему SimpleDesk: получение писем по IMAP, отправку по SMTP, создание обращений и ответов, диагностику, правила разбора ответов и OAuth 2.0 для Google и Microsoft.

## Модель данных и связи

- `App\Models\Admin\Mail\Mailbox` — логический почтовый ящик SimpleDesk, связанный с отделом и адресом поддержки.
- `App\Models\Admin\Mail\MailboxChannel` — конкретный входящий (`imap`) или исходящий (`smtp`) канал. Канал хранит транспортные настройки, направление, приоритет/failover и состояние здоровья.
- `App\Models\Admin\Mail\MailProviderConnection` — общая конфигурация провайдера. Для OAuth она хранит client configuration, зашифрованные токены и identity аккаунта; одна запись может обслуживать несколько IMAP/SMTP-каналов через `provider_connection_id`.

Парольные каналы продолжают работать независимо от OAuth. OAuth не заменяет существующий mail pipeline и не использует Gmail API или Microsoft Graph для передачи почты.

## Реализованные подсистемы

| Раздел | Назначение |
|---|---|
| [Reply Parsing](reply-parsing.md) | Отделяет новый текст входящего ответа от цитат и подписей перед созданием `TicketReply`. |
| [Email Diagnostics](email-diagnostics.md) | Показывает snapshot каналов и сообщений, запускает одиночные IMAP/SMTP/ClamAV-проверки. |
| [OAuth Integrations](oauth-integrations.md) | Управляет OAuth-приложениями Google/Microsoft и подключёнными аккаунтами. |
| [OAuth Security](oauth-security.md) | Описывает state, PKCE, nonce, шифрование и redaction. |
| [OAuth Providers](oauth-providers.md) | Endpoints, scopes и настройка Google Cloud/Microsoft Entra. |
| [OAuth Mail Drivers](oauth-mail-drivers.md) | Runtime-интеграция XOAUTH2 с IMAP и SMTP. |
| [OAuth Testing](oauth-testing.md) | Автоматические и ручные проверки. |
| [OAuth Troubleshooting](oauth-troubleshooting.md) | Диагностика типичных ошибок без утечки секретов. |

## Текущее состояние и внешняя проверка

CRUD, callback, refresh, redaction, connection tests и retry покрыты автоматическими тестами с fake/mocks. Реальные end-to-end проверки требуют собственных `<client-id>`, `<client-secret>`, Google Workspace/Gmail или Microsoft 365 аккаунта, разрешённых callback URL, доступных IMAP/SMTP endpoints и, для antivirus, доступного ClamAV. Команда `simpledesk:mail:refresh-oauth-tokens` реализована, но в `MailAutomationServiceProvider` автоматически не планируется.

