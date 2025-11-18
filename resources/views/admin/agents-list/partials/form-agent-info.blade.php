<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('lang.agents_form.agent_info') }}</h5>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-5 mb-3">
            <div class="col-md-6">
                <label for="email" class="form-label fw-bold">
                    {{ __('lang.agents_form.email') }} <span class="text-danger">*</span>
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="{{ __('lang.agents_form.email_placeholder') }}"
                       value="{{ old('email', $agent->email ?? '') }}">
                @error('email')
                <div class="invalid-feedback">{{ __('lang.agents_form.email_error') }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="login" class="form-label fw-bold">{{ __('lang.agents_form.login') }}:</label>
                <input type="text" id="login" name="login" class="form-control"
                       placeholder="{{ __('lang.agents_form.login_placeholder') }}"
                       value="{{ old('login', $agent->login ?? '') }}">
            </div>
        </div>

        <div class="row g-5 mb-3">
            <div class="col-md-4">
                <label for="last_name" class="form-label fw-bold">{{ __('lang.agents_form.last_name') }}:</label>
                <input type="text" id="last_name" name="last_name" class="form-control"
                       placeholder="{{ __('lang.agents_form.last_name_placeholder') }}"
                       value="{{ old('last_name', $agent->last_name ?? '') }}">
            </div>

            <div class="col-md-4">
                <label for="first_name" class="form-label fw-bold">
                    {{ __('lang.agents_form.first_name') }} <span class="text-danger">*</span>
                </label>
                <input type="text" id="first_name" name="first_name"
                       class="form-control @error('first_name') is-invalid @enderror"
                       placeholder="{{ __('lang.agents_form.first_name_placeholder') }}"
                       value="{{ old('first_name', $agent->first_name ?? '') }}">
                @error('first_name')
                <div class="invalid-feedback">{{ __('lang.agents_form.first_name_error') }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label for="patronymic" class="form-label fw-bold">{{ __('lang.agents_form.patronymic') }}:</label>
                <input type="text" id="patronymic" name="patronymic" class="form-control"
                       placeholder="{{ __('lang.agents_form.patronymic_placeholder') }}"
                       value="{{ old('patronymic', $agent->patronymic ?? '') }}">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-5th">
                <label for="location" class="form-label fw-bold">{{ __('lang.agents_form.location') }}:</label>
                <input type="text" id="location" class="form-control"
                       placeholder="{{ __('lang.agents_form.location_placeholder') }}">
            </div>

            <div class="col-md-5th d-flex flex-column">
                <label for="phone_number" class="form-label fw-bold">{{ __('lang.agents_form.phone_number') }}:</label>
                <input id="phone_number" type="tel" name="phone_number" class="form-control"
                       value="{{ old('phone_number', $agent->phone_number ?? '') }}">
                <input type="hidden" name="phone_number" id="phone_number_hidden"
                       value="{{ old('phone_number', $agent->phone_number ?? '') }}">
            </div>

            <div class="col-md-5th d-flex flex-column">
                <label for="mobile_phone" class="form-label fw-bold">{{ __('lang.agents_form.mobile_phone') }}:</label>
                <input id="mobile_phone" type="tel" name="mobile_phone" class="form-control"
                       value="{{ old('mobile_phone', $agent->mobile_phone ?? '') }}">
                <input type="hidden" name="mobile_phone" id="mobile_phone_hidden"
                       value="{{ old('mobile_phone', $agent->mobile_phone ?? '') }}">
            </div>

            <div class="col-md-5th">
                <label for="internal_number" class="form-label fw-bold">{{ __('lang.agents_form.internal_number') }}:</label>
                <input type="text" id="internal_number" name="internal_number" class="form-control"
                       placeholder="{{ __('lang.agents_form.internal_number_placeholder') }}"
                       value="{{ old('internal_number', $agent->internal_number ?? '') }}">
            </div>

            <div class="col-md-5th">
                <label class="form-label fw-bold">{{ __('lang.agents_form.timezone') }}:</label>
                <select name="timezone" class="form-select" required>
                    @foreach($timezones as $tz)
                        <option value="{{ $tz['identifier'] }}"
                            @selected(old('timezone', $agent->timezone ?? 'UTC') === $tz['identifier'])>
                            {{ $tz['display'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
