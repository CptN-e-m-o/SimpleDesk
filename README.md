# SimpleDesk

SimpleDesk — это helpdesk-система для обработки обращений пользователей.
Проект реализуется как pet-проект на Laravel, Inertia.js, React и TailwindCSS

## О проекте

SimpleDesk помогает организовать работу с пользовательскими обращениями: создавать тикеты, распределять их между сотрудниками поддержки, отслеживать статусы и управлять внутренней структурой службы поддержки.

Проект сделан с акцентом на backend-архитектуру, чистую структуру кода и современный интерфейс на React.

## Технологический стек

### Backend

* PHP
* Laravel
* PostgreSQL
* Eloquent ORM
* Laravel Migrations
* Form Requests
* Middleware
* Role-based access control

### Frontend

* React
* TypeScript
* Inertia.js
* Tailwind CSS
* Vite

### Инфраструктура

* Docker
* Docker Compose
* PostgreSQL

## Архитектура

Проект построен как монолитное fullstack-приложение на Laravel с использованием Inertia.js.

Backend отвечает за бизнес-логику, авторизацию, права доступа, работу с базой данных и валидацию. Frontend реализован на React и получает данные через Inertia без необходимости писать отдельный REST API для каждой страницы.
## Статус проекта

Проект находится в разработке.
