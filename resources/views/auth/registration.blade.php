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
                    <h2 class="text-center mb-4">{{ __('lang.register_title') }}</h2>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('lang.register_name_label') }}</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('lang.register_email_label') }}</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('lang.register_password_label') }}</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ __('lang.register_password_confirmation_label') }}</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('lang.register_submit_button') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
