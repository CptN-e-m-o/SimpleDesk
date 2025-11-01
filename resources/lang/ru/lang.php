<?php

return [
    // --- Главная страница (Index Page) ---

    // Секция Header
    'header_title' => 'Добро пожаловать в SimpleDesk',
    'header_subtitle' => 'Система для управления заявками и общения с техподдержкой.',
    'create_ticket_button' => 'Создать тикет',
    'login_button' => 'Войти в систему',

    // Секция "Что умеет SimpleDesk"
    'features_title' => 'Что умеет SimpleDesk',
    'features_client_title' => 'Клиент',
    'features_client_text' => 'Создавайте тикеты, следите за статусом своих заявок и получайте помощь от команды поддержки.',
    'features_agent_title' => 'Агент',
    'features_agent_text' => 'Берите тикеты в работу, отвечайте клиентам и помогайте быстро решать проблемы пользователей.',
    'features_admin_title' => 'Администратор',
    'features_admin_text' => 'Управляйте пользователями, назначайте роли и следите за эффективностью работы службы поддержки.',

    // Секция "Как это работает"
    'how_it_works_title' => 'Как это работает',
    'how_it_works_step1' => 'Пользователь создаёт заявку с описанием проблемы.',
    'how_it_works_step2' => 'Агент берёт заявку в работу и отвечает пользователю.',
    'how_it_works_step3' => 'Обсуждение продолжается, пока заявка не будет решена.',
    'how_it_works_step4' => 'Администратор следит за процессом и управляет ролями.',

    // --- Страница авторизации (Login Page) ---
    'login_title' => 'Авторизация',
    'login_email_label' => 'Email',
    'login_password_label' => 'Пароль',
    'login_submit_button' => 'Войти',

    // --- Страница регистрации (Register Page) ---
    'register_title' => 'Регистрация',
    'register_name_label' => 'Имя',
    'register_email_label' => 'Email',
    'register_password_label' => 'Пароль',
    'register_password_confirmation_label' => 'Подтверждение пароля',
    'register_submit_button' => 'Зарегистрироваться',

    // --- Футер (Footer) ---
    'footer_copyright' => '© :year SimpleDesk — система тикетов поддержки',

    // --- Навигационная панель (Navbar) ---
    'navbar_brand' => 'SimpleDesk',
    'navbar_toggle_navigation' => 'Переключить навигацию',
    'navbar_login' => 'Войти',
    'navbar_register' => 'Регистрация',
    'navbar_my_tickets' => 'Мои тикеты',
    'navbar_all_tickets' => 'Все тикеты',
    'navbar_users' => 'Пользователи',
    'navbar_my_profile' => 'Мой профиль',
    'navbar_logout' => 'Выйти',

    // --- Глобальные (App Level) ---
    'app_title' => 'SimpleDesk — Тикет-система поддержки',

    // --- Форма создания/редактирования тикета (Ticket Form) ---
    'ticket_form_create_title' => 'Создание новой заявки',
    'ticket_form_edit_title' => 'Редактирование заявки #:id',
    'ticket_form_back_to_list' => 'К списку заявок',
    'ticket_form_subject_label' => 'Тема',
    'ticket_form_description_label' => 'Описание',
    'ticket_form_agent_label' => 'Исполнитель',
    'ticket_form_agent_none' => 'Не назначен',
    'ticket_form_priority_label' => 'Приоритет',
    'ticket_form_status_label' => 'Статус',
    'ticket_form_cancel_button' => 'Отмена',
    'ticket_form_create_button' => 'Создать заявку',
    'ticket_form_save_button' => 'Сохранить изменения',

    // --- Страница создания тикета (Wrapper) ---
    'create_ticket_page_title' => 'Добавление новой заявки',

    // --- Страница редактирования тикета (Wrapper) ---
    'edit_ticket_page_title' => 'Редактирование заявки',

    // --- Страница списка тикетов (Tickets List Page) ---
    'tickets_list_title' => 'Список заявок',
    'tickets_list_add_button' => 'Добавить заявку',
    'tickets_list_header_id' => 'ID',
    'tickets_list_header_subject' => 'Тема',
    'tickets_list_header_author' => 'Автор',
    'tickets_list_header_status' => 'Статус',
    'tickets_list_header_priority' => 'Приоритет',
    'tickets_list_header_created' => 'Создана',
    'tickets_list_header_actions' => 'Действия',
    'tickets_list_no_tickets_found' => 'Заявки не найдены',
    'tickets_list_delete_confirm' => 'Вы уверены, что хотите удалить заявку?',

    // --- Страница просмотра тикета (Ticket Show Page) ---
    'ticket_show_title' => 'Заявка #:id: :title',
    'ticket_show_description_title' => 'Описание',
    'ticket_show_author_label' => 'Автор:',
    'ticket_show_status_label' => 'Статус:',
    'ticket_show_priority_label' => 'Приоритет:',
    'ticket_show_created_label' => 'Создана:',
    'ticket_show_replies_title' => 'Переписка по заявке',
    'ticket_show_edit_reply_button' => 'Редактировать',
    'ticket_show_delete_reply_button' => 'Удалить',
    'ticket_show_delete_reply_confirm' => 'Удалить этот ответ?',
    'ticket_show_no_replies' => 'В этой заявке пока нет ответов.',
    'ticket_show_add_reply_title' => 'Добавить ответ',
    'ticket_show_reply_placeholder' => 'Напишите ваш ответ здесь...',
    'ticket_show_send_reply_button' => 'Отправить',
    'ticket_show_ticket_closed_message' => 'Заявка закрыта, добавление новых ответов невозможно.',

    // --- Форма пользователя (User Form) ---
    'user_form_name_label' => 'Имя',
    'user_form_email_label' => 'E-mail',
    'user_form_role_label' => 'Роль',
    'user_form_select_role_placeholder' => 'Выберите роль...',
    'user_form_password_label' => 'Пароль',
    'user_form_password_help' => 'Оставьте поле пустым, если не хотите менять пароль.',
    'user_form_password_confirmation_label' => 'Подтвердите пароль',
    'user_form_cancel_button' => 'Отмена',
    'user_form_create_button' => 'Создать пользователя',
    'user_form_save_button' => 'Сохранить изменения',

    // --- Страница создания пользователя (Wrapper) ---
    'create_user_page_title' => 'Добавление нового пользователя',

    // --- Страница редактирования пользователя (Wrapper) ---
    'edit_user_page_title' => 'Редактирование пользователя: :name',

    // --- Страница списка пользователей (Users List Page) ---
    'users_list_title' => 'Список пользователей',
    'users_list_add_button' => 'Добавить пользователя',
    'users_list_header_id' => 'ID',
    'users_list_header_name' => 'Имя',
    'users_list_header_email' => 'E-mail',
    'users_list_header_role' => 'Роль',
    'users_list_header_actions' => 'Действия',
    'users_list_actions_aria_label' => 'Действия с пользователем',
    'users_list_view_button' => 'Просмотр',
    'users_list_edit_button' => 'Редактировать',
    'users_list_delete_button' => 'Удалить',
    'users_list_delete_confirm' => 'Вы уверены, что хотите удалить пользователя?',
    'users_list_no_users_found' => 'Пользователи не найдены',

    // --- Страница просмотра пользователя (User Show Page) ---
    'user_show_title' => 'Профиль пользователя',
    'user_show_back_to_list' => 'К списку пользователей',
    'user_show_id_label' => 'ID пользователя:',
    'user_show_email_label' => 'E-mail:',
    'user_show_registered_at_label' => 'Дата регистрации:',
    'user_show_updated_at_label' => 'Последнее обновление:',
    'user_show_delete_confirm' => 'Вы уверены, что хотите удалить этого пользователя?',

    // --- Приоритеты тикетов (Ticket Priorities) ---
    'ticket_priority_low' => 'Низкий',
    'ticket_priority_medium' => 'Средний',
    'ticket_priority_high' => 'Высокий',

    // --- Статусы тикетов (Ticket Statuses) ---
    'ticket_status_open' => 'Открыта',
    'ticket_status_in_progress' => 'В работе',
    'ticket_status_closed' => 'Закрыта',

    // --- Роли пользователей (User Roles) ---
    'user_role_client' => 'Пользователь',
    'user_role_agent' => 'Агент',
    'user_role_admin' => 'Администратор',

    // --- Сообщения контроллера ответов (Reply Controller Messages) ---
    'reply_closed_ticket_error' => 'Нельзя ответить на закрытую заявку.',
    'reply_added_success' => 'Ваш ответ был добавлен.',
    'reply_updated_success' => 'Ответ был успешно обновлен.',
    'reply_deleted_success' => 'Ответ был удален.',

    // --- Сообщения контроллера заявок (Ticket Controller Messages) ---
    'ticket_created_success' => 'Заявка успешно создана!',
    'ticket_updated_success' => 'Заявка #:id успешно обновлена!',
    'ticket_deleted_success' => 'Заявка #:id была успешно удалена.',

    // --- Сообщения контроллера пользователей (User Controller Messages) ---
    'user_created_success' => 'Пользователь успешно создан!',
    'user_updated_success' => 'Данные пользователя успешно обновлены!',
    'user_deleted_success' => 'Пользователь успешно удален!',
    'user_delete_permission_denied' => 'У вас нет прав для выполнения этого действия.',
    'user_delete_self_error' => 'Вы не можете удалить свою собственную учетную запись!',

    // --- Сообщения контроллера профиля (Profile Controller Messages) ---
    'profile_updated' => 'Информация профиля была успешно обновлена',
    'profile_current_password_is_incorrect' => 'Текущий пароль введен неверно.',
    'profile_current_password_required' => 'Для смены пароля необходимо ввести текущий пароль.',
    'profile_avatar_updated' => 'Аватар успешно обновлен!',
    'profile_2fa_enabled_successfully' => 'Двухфакторная аутентификация успешно включена!',
    'profile_2fa_disabled_successfully' => 'Двухфакторная аутентификация отключена.',
    'profile_2fa_incorrect_code' => 'Неверный код подтверждения. Попробуйте еще раз.',

    // --- Сообщения Middleware ---
    'access_denied' => 'У вас нет прав для доступа к этой странице.',
    'email_not_verified' => 'Ваш e-mail не подтверждён',

    // --- Сообщения валидации (Validation Messages) ---
    'validation_name_required' => 'Поле "Имя" обязательно для заполнения.',
    'validation_email_required' => 'Поле "E-mail" обязательно для заполнения.',
    'validation_email_unique' => 'Пользователь с таким E-mail уже существует.',
    'validation_password_required' => 'Поле "Пароль" обязательно для заполнения.',
    'validation_password_confirmed' => 'Пароли не совпадают.',
    'validation_role_required' => 'Необходимо выбрать роль для пользователя.',

    // --- Профиль пользователя ---
    'profile_tab_info' => 'Профиль пользователя',
    'profile_tab_2fa' => 'Двухфакторная аутентификация',
    'profile_tab_login_history' => 'История входов',
    'profile_first_name' => 'Имя',
    'profile_last_name' => 'Фамилия',
    'profile_patronymic' => 'Отчество',
    'profile_email' => 'Электронная почта',
    'profile_timezone' => 'Часовой пояс',
    'profile_save_changes' => 'Сохранить изменения',
    'profile_current_password' => 'Текущий пароль',
    'profile_enter_password' => 'Новый пароль',
    'profile_repeat_password' => 'Подтверждение пароля',
    'profile_2fa_title' => 'Подключить двухфакторную аутентификацию',
    'profile_2fa_description' => 'Google Authenticator',
    'profile_enable_2fa' => 'Активировать двухфакторную аутентификацию',
    'profile_login_history' => 'История входов в аккаунт',
    'profile_login_ip' => 'IP',
    'profile_login_device' => 'Устройство',
    'profile_login_time' => 'Последний вход',
    'profile_2auth_enabled' => 'Двухфакторная аутентификация для вашей учетной записи включена.',
    'profile_disable_2auth' => 'Отключить 2FA',
    'profile_step_1' => 'Шаг 1:',
    'profile_scan_qr_code' => ' Отсканируйте этот QR-код с помощью приложения (например, Google Authenticator).',
    'profile_enter_code_manually' => 'Или введите этот ключ вручную: ',
    'profile_step_2' => 'Шаг 2:',
    'profile_enter_code_finish_settings' => ' Введите код из приложения, чтобы завершить настройку.',
    'profile_confirmation_code' => 'Код подтверждения',
    'no_login_history' => 'Нет истории входов',
    'profile_phone_number' => 'Номер телефона',
    'profile_signature' => 'Подпись агента',
    'profile_signature_help' => 'Эта подпись будет автоматически добавляться в конце ваших ответов в заявках.',
];
