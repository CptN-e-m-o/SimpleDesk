<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('lang.app_title') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <link href="{{ asset('css/user-profile.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css"/>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100 bg-light text-dark">

@if(Auth::check() && (Auth::user()->isAdminOrAgent()) && Str::contains(request()->path(), 'panel'))
    @include('layouts.partials.agent_header')
@elseif(Auth::check() && (Auth::user()->isAdmin()) && Str::contains(request()->path(), 'admin'))
    @include('layouts.partials.admin_header')
@else
    @include('layouts.partials.header')
@endif

<main class="flex-grow-1">
    @yield('content')
</main>

@include('layouts.partials.footer')

<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@stack('scripts')
</body>
</html>
