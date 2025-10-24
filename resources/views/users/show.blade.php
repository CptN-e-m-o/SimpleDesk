@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row d-flex justify-content-center">
            <div class="col-md-8">
                <div class="card user-card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Профиль пользователя</h4>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-arrow-left me-2"></i>К списку пользователей
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center">
                                <h5 class="card-title mb-1">{{ $user->name }}</h5>
                                <span class="badge bg-{{ $user->role_id->color() }} fs-6">{{ $user->role_id->toString() }}</span>
                            </div>
                            <div class="col-md-8">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>ID пользователя:</strong>
                                        <span>{{ $user->id }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>E-mail:</strong>
                                        <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Дата регистрации:</strong>
                                        <span>{{ $user->created_at->format('d.m.Y H:i') }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Последнее обновление:</strong>
                                        <span>{{ $user->updated_at->diffForHumans() }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center bg-light">
                        <div class="d-flex justify-content-center gap-2" role="group">
                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning">
                                <i class="bi bi-pencil-square me-2"></i>Редактировать
                            </a>
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')">
                                    <i class="bi bi-trash me-2"></i>Удалить
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
