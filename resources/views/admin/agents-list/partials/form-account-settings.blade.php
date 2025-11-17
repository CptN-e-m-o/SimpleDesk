<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">!Статус аккаунта и настройки</h5>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label for="department" class="form-label fw-bold">
                    !Отдел: <span class="text-danger">*</span>
                </label>
                <input type="text" id="department" class="form-control" placeholder="!Выберите отдел"
                       required>
            </div>
            <div class="col-md-3">
                <label for="team" class="form-label fw-bold">
                    !Команда:
                </label>
                <input type="text" id="team" class="form-control" placeholder="!Выберите команду">
            </div>
            <div class="col-md-3">
                <label for="team" class="form-label fw-bold">
                    !Тип:
                </label>
                <input type="text" id="type" class="form-control" placeholder="!Выберите тип">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">!Роль:</label>

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
