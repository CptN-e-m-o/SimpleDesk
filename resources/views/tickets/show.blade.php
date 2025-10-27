@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Заявка #{{ $ticket->id }}: {{ $ticket->title }}</h4>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-2"></i>К списку заявок
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5>Описание</h5>
                        <p>{{ $ticket->description }}</p>
                    </div>
                    <div class="col-md-4">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Автор:</span>
                                <strong>{{ $ticket->user->name }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Статус:</span>
                                <span class="badge bg-{{ $ticket->status->name == 'Открыта' ? 'primary' : ($ticket->status->name == 'В работе' ? 'warning' : 'success') }}">{{ $ticket->status->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Приоритет:</span>
                                <span class="badge bg-{{ $ticket->priority->name == 'Низкий' ? 'info' : ($ticket->priority->name == 'Средний' ? 'warning' : 'danger') }}">{{ $ticket->priority->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Создана:</span>
                                <span>{{ $ticket->created_at->format('d.m.Y H:i') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="h5 mb-3">Переписка по заявке</div>

        @forelse($ticket->replies as $reply)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $reply->author->name }}</strong>
                        <small class="text-muted ms-2">{{ $reply->created_at->diffForHumans() }}</small>
                    </div>

                    <div class="d-flex gap-2">
                        @can('update', $reply)
                            <a href="#" class="btn btn-sm btn-outline-secondary">
                                Редактировать
                            </a>
                        @endcan

                        @can('delete', $reply)
                            <form action="{{ route('replies.destroy', $reply) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить этот ответ?')">
                                    Удалить
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    {{ $reply->body }}
                </div>
            </div>
        @empty
            <div class="alert alert-light text-center mb-4">
                В этой заявке пока нет ответов.
            </div>
        @endforelse

        @if($ticket->status->name !== 'Закрыта')
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Добавить ответ</h5>
                    <form action="{{ route('tickets.replies.store', $ticket) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <textarea class="form-control @error('body') is-invalid @enderror" id="body" name="body" rows="3" placeholder="Напишите ваш ответ здесь..." required></textarea>
                            @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Отправить</button>
                    </form>
                </div>
            </div>
        @else
            <div class="alert alert-warning text-center mt-4">
                Заявка закрыта, добавление новых ответов невозможно.
            </div>
        @endif
    </div>
@endsection
