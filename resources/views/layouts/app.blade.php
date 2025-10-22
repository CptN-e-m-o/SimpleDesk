<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimpleDesk — Тикет-система поддержки</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="d-flex flex-column min-vh-100 bg-light text-dark">

@include('layouts.partials.header')

<main class="flex-grow-1">
    @yield('content')
</main>

@include('layouts.partials.footer')

</body>
</html>
