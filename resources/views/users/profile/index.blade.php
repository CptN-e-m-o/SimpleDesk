@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0">
                        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                                    {{ __('lang.profile_tab_info') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                    {{ __('lang.profile_tab_2fa') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                                    {{ __('lang.profile_tab_login_history') }}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body tab-content" id="profileTabsContent">
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                @if (session('success'))
                                    <div class="alert alert-success" role="alert">
                                        {{ session('success') }}
                                    </div>
                                @endif
                                @if ($errors->any())
                                    <div class="alert alert-danger" role="alert">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="row align-items-center mb-4">
                                    <div class="col-md-4 text-center position-relative">
                                        <div class="avatar-wrapper position-relative d-inline-block rounded-circle"
                                             style="width: 250px; height: 250px;"
                                             data-upload-url="{{ route('profile.avatar.update') }}">

                                            <img src="{{ Auth::user()->avatar_url ?? asset('images/default-avatar.png') }}"
                                                 alt="avatar"
                                                 class="rounded-circle shadow-sm w-100 h-100"
                                                 style="object-fit: cover;"
                                                 id="avatar-preview">

                                            <label for="avatar" class="avatar-overlay position-absolute bottom-0 start-0 w-100 h-100 d-flex align-items-end justify-content-center"
                                                   style="cursor: pointer;">
                                                <div class="camera-icon-background d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-camera text-white" style="font-size: 20px;"></i>
                                                </div>
                                            </label>
                                            <input type="file" id="avatar" name="avatar" accept="image/*" class="d-none">

                                            <div id="avatar-spinner" class="position-absolute top-50 start-50 translate-middle d-none">
                                                <div class="spinner-border text-light" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="first_name" class="form-label">{{ __('lang.profile_first_name') }}</label>
                                                <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', Auth::user()->first_name) }}" required>
                                                @error('first_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="last_name" class="form-label">{{ __('lang.profile_last_name') }}</label>
                                                <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', Auth::user()->last_name) }}">
                                                @error('last_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="patronymic" class="form-label">{{ __('lang.profile_patronymic') }}</label>
                                                <input type="text" name="patronymic" id="patronymic" class="form-control @error('patronymic') is-invalid @enderror" value="{{ old('patronymic', Auth::user()->patronymic) }}">
                                                @error('patronymic')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ __('lang.profile_email') }}</label>
                                                <input type="email" name="email" class="form-control" value="{{ Auth::user()->email }}" disabled>
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ __('lang.profile_timezone') }}</label>
                                                <select name="timezone" class="form-select @error('timezone') is-invalid @enderror" required>
                                                    @foreach($timezones as $tz)
                                                        <option value="{{ $tz['identifier'] }}" @selected(Auth::user()->timezone === $tz['identifier'])>
                                                            {{ $tz['display'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('timezone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            @if(Auth::user()->isAdminOrAgent())
                                                <div class="col-md-12 mb-3">
                                                    <label for="phone_number" class="form-label">{{ __('lang.profile_phone_number') }}</label>
                                                    <input id="phone_number" type="tel" name="phone_number_input" class="form-control" value="{{ old('phone_number', Auth::user()->phone_number) }}">

                                                    <input type="hidden" name="phone_number" id="phone_number_hidden">
                                                </div>

                                                <div class="col-md-12 mb-3">
                                                    <label for="signature" class="form-label">{{ __('lang.profile_signature') }}</label>
                                                    <textarea name="signature" id="signature" rows="4" class="form-control">{{ old('signature', Auth::user()->signature) }}</textarea>
                                                    <div class="form-text">{{ __('lang.profile_signature_help') }}</div>
                                                </div>
                                            @endif

                                            <hr>
                                            <div class="col-md-12 mb-3">
                                                <label for="current_password" class="form-label">{{ __('lang.profile_current_password') }}</label>
                                                <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror">
                                                @error('current_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="password" class="form-label">{{ __('lang.profile_enter_password') }}</label>
                                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                                                @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="password_confirmation" class="form-label">{{ __('lang.profile_repeat_password') }}</label>
                                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                                            </div>
                                        </div>

                                        <div class="text-end mt-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-1"></i> {{ __('lang.profile_save_changes') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                            <h5>{{ __('lang.profile_2fa_title') }}</h5>
                            <p>{{ __('lang.profile_2fa_description') }}</p>

                            @if (Auth::user()->google2fa_enabled)
                                <div class="alert alert-success">{{ __('lang.profile_2auth_enabled') }}</div>
                                <form method="POST" action="{{ route('profile.2fa.disable') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="current_password_2fa" class="form-label">{{ __('lang.profile_current_password') }}</label>
                                        <input type="password" name="current_password" id="current_password_2fa" class="form-control @error('current_password') is-invalid @enderror" required>
                                        @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn btn-danger">{{ __('lang.profile_disable_2auth') }}</button>
                                </form>
                            @else
                                <p><strong>{{ __('lang.profile_step_1') }}</strong>{{ __('lang.profile_scan_qr_code') }}</p>
                                <div class="my-3">
                                    {!! QrCode::size(200)->generate($qrCodeUrl) !!}
                                </div>
                                <p>{{ __('lang.profile_enter_code_manually') }}<code>{{ $secretKey }}</code></p>

                                <hr>

                                <p><strong>{{ __('lang.profile_step_2') }}</strong>{{ __('lang.profile_enter_code_finish_settings') }}</p>
                                <form method="POST" action="{{ route('profile.2fa.enable') }}" class="mt-3">
                                    @csrf
                                    <div class="mb-3" style="max-width: 250px;">
                                        <label for="one_time_password" class="form-label">{{ __('lang.profile_confirmation_code') }}</label>
                                        <input type="text" name="one_time_password" id="one_time_password" class="form-control @error('one_time_password') is-invalid @enderror" required>
                                        @error('one_time_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn btn-primary">{{ __('lang.profile_enable_2fa') }}</button>
                                </form>
                            @endif
                        </div>

                        <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                            <h5>{{ __('lang.profile_login_history') }}</h5>
                            <table class="table table-sm table-striped mt-3">
                                <thead>
                                <tr>
                                    <th>{{ __('lang.profile_login_ip') }}</th>
                                    <th>{{ __('lang.profile_login_device') }}</th>
                                    <th>{{ __('lang.profile_login_time') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($logins ?? [] as $login)
                                    <tr>
                                        <td>{{ $login->ip_address }}</td>
                                        <td>{{ $login->user_agent }}</td>
                                        <td>{{ \Carbon\Carbon::parse($login->login_at)->setTimezone(Auth::user()->timezone)->format('Y-m-d H:i:s') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">{{ __('lang.no_login_history') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
