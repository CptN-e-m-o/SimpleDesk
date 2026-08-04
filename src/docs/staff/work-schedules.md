# Work Schedules

## Назначение

Work Schedule — повторяющийся недельный шаблон рабочего времени агента, а не календарная смена. Он независим от department `business_hours`: часы отдела применяются к SLA/работе отдела, график агента описывает его персональную доступность.

## Данные и время

| Таблица | Содержимое |
|---|---|
| `work_schedules` | Имя, описание, IANA `timezone`, active/archive state и авторы изменений. |
| `work_schedule_intervals` | Недельные интервалы; `day_of_week` использует ISO 1=Monday … 7=Sunday. |
| `work_schedule_assignments` | Связь с агентом и inclusive-период `effective_from`/`effective_until`. |
| `work_schedule_exceptions` | Персональное исключение назначения на дату. |
| `work_schedule_exception_intervals` | Нормализованные часы исключения. |

`starts_at`/`ends_at` — wall-clock time в timezone графика. Ночной интервал явно имеет `ends_next_day=true`; resolver также проверяет предыдущую локальную дату. Соседние интервалы допустимы, пересечения одного дня и через полночь запрещены.

## Назначения и исключения

Назначения не пересекаются по inclusive calendar dates; `effective_until=null` означает бессрочный период. Назначить можно только пользователя с ролью `type=agent`, только на активный неархивный график. Текущее назначение завершается датой; удалить можно только будущее.

Приоритет исключения:

- `day_off` удаляет все интервалы даты;
- `custom_hours` заменяет базовые интервалы;
- `extra_shift` добавляет непересекающиеся интервалы.

## Сервисы

- `WorkScheduleService` — transactional CRUD, interval sync, duplicate, archive/restore.
- `WorkScheduleAssignmentService` — single/bulk assignment, overlap locks, завершение/удаление.
- `WorkScheduleConflictChecker` — weekly, overnight, assignment и exception conflicts.
- `WorkScheduleExceptionService` — тип/date/interval validation и transactional sync.
- `AgentScheduleResolver` — `resolveAssignment`, `intervalsForDate`, `isWorking`, `nextWorkingInterval` с timezone и исключениями.

Пример:

```php
$working = app(AgentScheduleResolver::class)->isWorking($agent, now('UTC'));
$next = app(AgentScheduleResolver::class)->nextWorkingInterval($agent, now('UTC'));
```

## Доступ и маршруты

Permissions: `admin.staff.work_schedules.view`, `.create`, `.update`, `.archive`, `.manage_assignments`, `.manage_exceptions`. Основные names: `admin.work-schedules.index|create|store|show|edit|update|duplicate|toggle|destroy|restore`, `admin.work-schedules.assignments.store`, `admin.work-schedule-assignments.*`, `admin.work-schedule-exceptions.*`.

## Seeder и тесты

`WorkScheduleSeeder` идемпотентно создаёт Standard Support, Night Support и Weekend Support, детерминированно назначает до трёх существующих агентов и не падает при их отсутствии.

```bash
php artisan test tests/Unit/Admin/WorkSchedules
php artisan test tests/Feature/Admin/WorkSchedules
npm run type-check
```

Модуль пока не связан с Agent Statuses, Skills или ticket auto-assignment; `AgentScheduleResolver` является подготовленной границей для будущей маршрутизации.
