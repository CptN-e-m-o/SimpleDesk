<form method="POST" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}">
    @csrf
    @if(isset($user))
        @method('PATCH')
    @endif

    <div class="mb-3">
        <label for="name" class="form-label">Имя</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required>
        @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
        @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="role_id" class="form-label">Роль</label>
        <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
            <option value="" disabled {{ !isset($user) ? 'selected' : '' }}>Выберите роль...</option>
            @foreach($roles as $role)
                <option value="{{ $role->value }}" {{ old('role_id', $user->role_id->value ?? '') == $role->value ? 'selected' : '' }}>
                    {{ $role->toString() }}
                </option>
            @endforeach
        </select>
        @error('role_id')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Пароль</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" {{ !isset($user) ? 'required' : '' }}>
        @if(isset($user))
            <div class="form-text">Оставьте поле пустым, если не хотите менять пароль.</div>
        @endif
        @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Подтвердите пароль</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" {{ !isset($user) ? 'required' : '' }}>
    </div>

    <div class="d-flex justify-content-end">
        <a href="{{ route('users.index') }}" class="btn btn-secondary me-2">Отмена</a>
        <button type="submit" class="btn btn-primary">
            {{ isset($user) ? 'Сохранить изменения' : 'Создать пользователя' }}
        </button>
    </div>
</form>
