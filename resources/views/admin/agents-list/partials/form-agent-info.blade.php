<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">!Информация об агенте</h5>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-5 mb-3">
            <div class="col-md-6">
                <label for="email" class="form-label fw-bold">
                    !Электронная почта: <span class="text-danger">*</span>
                </label>

                <input type="email"
                       id="email"
                       name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="Введите email"
                       value="{{ old('email', $agent->email ?? '') }}"
                >

                @error('email')
                <div class="invalid-feedback">!Введите настоящий адрес e-mail</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="login" class="form-label fw-bold">!Логин:</label>
                <input type="text" id="login" name="login" class="form-control"
                       placeholder="!Введите логин"
                       value="{{ old('login', $agent->login ?? '') }}">
            </div>
        </div>
        <div class="row g-5 mb-3">
            <div class="col-md-4">
                <label for="last_name" class="form-label fw-bold">!Фамилия:</label>
                <input type="text" id="last_name" name="last_name" class="form-control"
                       placeholder="!Введите фамилию"
                       value="{{ old('last_name', $agent->last_name ?? '') }}">
            </div>

            <div class="col-md-4">
                <label for="first_name" class="form-label fw-bold">
                    !Имя: <span class="text-danger">*</span>
                </label>
                <input type="text"
                       id="first_name"
                       name="first_name"
                       class="form-control @error('first_name') is-invalid @enderror"
                       placeholder="Введите email"
                       value="{{ old('first_name', $agent->first_name ?? '') }}"
                >

                @error('first_name')
                <div class="invalid-feedback">!Это поле не может быть пустым</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label for="patronymic" class="form-label fw-bold">!Отчество:</label>
                <input type="text" id="patronymic" name="patronymic" class="form-control"
                       placeholder="!Введите отчество"
                       value="{{ old('patronymic', $agent->patronymic ?? '') }}">
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-5th">
                <label for="location" class="form-label fw-bold">!Выберите местоположение:</label>
                <input type="text" id="location" class="form-control" placeholder="!Введите значение для поиска">
            </div>

            <div class="col-md-5th d-flex flex-column">
                <label for="phone_number" class="form-label fw-bold">!Телефонный номер:</label>
                <input id="phone_number" type="tel" name="phone_number" class="form-control"
                       value="{{ old('phone_number', $agent->phone_number ?? '') }}">
                <input type="hidden" name="phone_number" id="phone_number_hidden"
                       value="{{ old('phone_number', $agent->phone_number ?? '') }}">
            </div>

            <div class="col-md-5th d-flex flex-column">
                <label for="mobile_phone" class="form-label fw-bold">!Мобильный телефон:</label>
                <input id="mobile_phone" type="tel" name="mobile_phone" class="form-control"
                       value="{{ old('mobile_phone', $agent->mobile_phone ?? '') }}">
                <input type="hidden" name="mobile_phone" id="mobile_phone_hidden"
                       value="{{ old('mobile_phone', $agent->mobile_phone ?? '') }}">
            </div>

            <div class="col-md-5th">
                <label for="internal_number" class="form-label fw-bold">!Введите внутренний номер:</label>
                <input type="text" id="internal_number" name="internal_number" class="form-control"
                       placeholder="123-456"
                       value="{{ old('internal_number', $agent->internal_number ?? '') }}">
            </div>

            <div class="col-md-5th">
                <label class="form-label fw-bold">!Введите часовой пояс:</label>

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
