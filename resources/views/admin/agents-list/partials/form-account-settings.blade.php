<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('lang.agents_form.account_status_settings') }}</h5>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label for="department" class="form-label fw-bold">
                    {{ __('lang.agents_form.department') }} <span class="text-danger">*</span>
                </label>
                <input type="text" id="department" class="form-control"
                       placeholder="{{ __('lang.agents_form.department_placeholder') }}" required>
            </div>

            <div class="col-md-3">
                <label for="team" class="form-label fw-bold">
                    {{ __('lang.agents_form.team') }}:
                </label>
                <input type="text" id="team" class="form-control"
                       placeholder="{{ __('lang.agents_form.team_placeholder') }}">
            </div>

            <div class="col-md-3">
                <label for="type" class="form-label fw-bold">
                    {{ __('lang.agents_form.type') }}:
                </label>
                <input type="text" id="type" class="form-control"
                       placeholder="{{ __('lang.agents_form.type_placeholder') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">{{ __('lang.agents_form.role') }}:</label>
                <select name="role_id" class="form-select">
                    @foreach (collect(\App\Enums\UserRole::cases())->reject(fn($r) => $r === \App\Enums\UserRole::Client) as $role)
                        <option
                            value="{{ $role->value }}"
                            @selected(old('role_id', $agent?->role_id?->value ?? \App\Enums\UserRole::Agent->value) == $role->value)
                        >
                            {{ $role->toString() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
