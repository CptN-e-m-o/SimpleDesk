@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col">
                <h1>Список пользователей</h1>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered align-middle">
                <thead class="table-dark">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Имя</th>
                    <th scope="col">E-mail</th>
                    <th scope="col">Роль</th>
                    <th scope="col" class="text-center">Действия</th>
                </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <th scope="row">{{ $user->id }}</th>

                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-primary">{{ $user->role_id->toString() }}</span>
                        </td>

                        <td class="text-center">
                            <div class="btn-group" role="group" aria-label="Действия с пользователем">
                                <a href="{{-- route('users.edit', $user->id) --}}" class="btn btn-warning btn-sm">
                                    Редактировать
                                </a>

                                <form action="{{-- route('users.destroy', $user->id) --}}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить пользователя?')">
                                        Удалить
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Пользователи не найдены</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
