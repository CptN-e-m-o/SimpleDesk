<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('lang.app_title') }}</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <link href="{{ asset('css/user-profile.css') }}" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light text-dark">

@include('layouts.partials.header')

<main class="flex-grow-1">
    @yield('content')
</main>

@include('layouts.partials.footer')

<script src="{{ asset('js/user-profile.js') }}"></script>
</body>
</html>
