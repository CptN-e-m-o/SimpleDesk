@extends('layouts.app')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm p-4">
                    <h2 class="text-center mb-4">{{ __('lang.login_title') }}</h2>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="login" class="form-label">{{ __('lang.login_email_label') }}</label>
                            <input type="text" id="login" name="login" class="form-control" required autofocus placeholder="{{ __('lang.login_enter_email_label') }}">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('lang.login_password_label') }}</label>
                            <input type="password" id="password" name="password" class="form-control" required placeholder="{{ __('lang.login_enter_password_label') }}">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('lang.login_submit_button') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
