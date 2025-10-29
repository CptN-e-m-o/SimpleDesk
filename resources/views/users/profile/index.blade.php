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

                                <div class="row align-items-center mb-4">
                                    <div class="col-md-4 text-center position-relative">
                                        <div class="avatar-wrapper position-relative d-inline-block rounded-circle"
                                             style="width: 150px; height: 150px;"
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
                                                <input type="text" name="first_name" id="first_name" class="form-control" value="{{ old('first_name', Auth::user()->first_name) }}" required>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="last_name" class="form-label">{{ __('lang.profile_last_name') }}</label>
                                                <input type="text" name="last_name" id="last_name" class="form-control" value="{{ old('last_name', Auth::user()->last_name) }}">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="patronymic" class="form-label">{{ __('lang.profile_patronymic') }}</label>
                                                <input type="text" name="patronymic" id="patronymic" class="form-control" value="{{ old('patronymic', Auth::user()->patronymic) }}">
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ __('lang.profile_email') }}</label>
                                                <input type="email" name="email" class="form-control" value="{{ Auth::user()->email }}" disabled>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ __('lang.profile_timezone') }}</label>
                                                <select name="timezone" class="form-select" required>
                                                    @foreach($timezones as $tz)
                                                        <option value="{{ $tz['identifier'] }}" @selected(Auth::user()->timezone === $tz['identifier'])>
                                                            {{ $tz['display'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
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
                            <button class="btn btn-outline-primary">{{ __('lang.profile_enable_2fa') }}</button>
                        </div>

                        <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                            <h5>{{ __('lang.profile_login_history') }}</h5>
                            <table class="table table-sm table-striped mt-3">
                                <thead>
                                <tr>
                                    <th>{{ __('lang.login_ip') }}</th>
                                    <th>{{ __('lang.login_device') }}</th>
                                    <th>{{ __('lang.login_time') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($logins ?? [] as $login)
                                    <tr>
                                        <td>{{-- $login->ip_address --}}</td>
                                        <td>{{-- $login->user_agent --}}</td>
                                        <td>{{-- $login->created_at->format('Y-m-d H:i:s') --}}</td>
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
