{{-- resources/views/emails/user-generated-password.blade.php --}}
    <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>!Доступ к SimpleDesk</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f9fc; padding: 20px; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { background: #4f46e5; color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 26px; }
        .body { padding: 40px 30px; }
        .body p { font-size: 16px; line-height: 1.6; margin: 0 0 20px; }
        .password { background: #f3f4f6; padding: 15px 20px; border-radius: 8px; font-size: 18px; font-family: monospace; text-align: center; margin: 25px 0; }
        .btn { display: inline-block; background: #4f46e5; color: white; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
        .footer { background: #f8fafc; padding: 25px; text-align: center; font-size: 13px; color: #94a3b8; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>SimpleDesk</h1>
    </div>

    <div class="body">
        <p>!Ваш аккаунт в системе <strong>SimpleDesk</strong> успешно создан.</p>
        <p>!Для входа используйте следующие данные:</p>

        <div class="password">
            <strong>{{ $password }}</strong>
        </div>

        <p style="text-align: center;">
            <a href="{{ route('login') }}" class="btn">!Войти в систему</a>
        </p>

        <p style="color: #e11d48; font-size: 14px;">
            !После первого входа обязательно смените этот временный пароль!
        </p>
    </div>

    <div class="footer">
        © {{ date('Y') }} !SimpleDesk<br>
        !Это письмо отправлено автоматически
    </div>
</div>
</body>
</html>
