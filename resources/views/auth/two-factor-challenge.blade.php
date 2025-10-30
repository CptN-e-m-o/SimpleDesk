@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header">{{ __('Двухфакторная аутентификация') }}</div>

                    <div class="card-body">
                        <p class="text-muted">Пожалуйста, введите код из вашего приложения для аутентификации, чтобы войти.</p>

                        <form method="POST" action="{{ route('2fa.challenge.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="one_time_password" class="form-label">{{ __('Код подтверждения') }}</label>
                                <input id="one_time_password" type="text" class="form-control @error('one_time_password') is-invalid @enderror" name="one_time_password" required autofocus>

                                @error('one_time_password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Войти') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
