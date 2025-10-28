@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ __('lang.ticket_show_title', ['id' => $ticket->id, 'title' => $ticket->title]) }}</h4>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-2"></i>{{ __('lang.ticket_form_back_to_list') }}
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5>{{ __('lang.ticket_show_description_title') }}</h5>
                        <p>{{ $ticket->description }}</p>
                    </div>
                    <div class="col-md-4">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">{{ __('lang.ticket_show_author_label') }}</span>
                                <strong>{{ $ticket->user->name }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">{{ __('lang.ticket_show_status_label') }}</span>
                                <span class="badge bg-{{ $ticket->status->color() }}">
                                    {{ $ticket->status->label() }}
                                </span>
                            </li>

                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">{{ __('lang.ticket_show_priority_label') }}</span>
                                <span class="badge bg-{{ $ticket->priority->color() }}">
                                    {{ $ticket->priority->label() }}
                                </span>
                            </li>


                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">{{ __('lang.ticket_show_created_label') }}</span>
                                <span>{{ $ticket->created_at->format('d.m.Y H:i') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="h5 mb-3">{{ __('lang.ticket_show_replies_title') }}</div>

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
                                {{ __('lang.ticket_show_edit_reply_button') }}
                            </a>
                        @endcan

                        @can('delete', $reply)
                            <form action="{{ route('replies.destroy', $reply) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('lang.ticket_show_delete_reply_confirm') }}')">
                                    {{ __('lang.ticket_show_delete_reply_button') }}
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
                {{ __('lang.ticket_show_no_replies') }}
            </div>
        @endforelse

        @if($ticket->status->name !== 'Закрыта')
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">{{ __('lang.ticket_show_add_reply_title') }}</h5>
                    <form action="{{ route('tickets.replies.store', $ticket) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <textarea class="form-control @error('body') is-invalid @enderror" id="body" name="body" rows="3" placeholder="{{ __('lang.ticket_show_reply_placeholder') }}" required></textarea>
                            @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('lang.ticket_show_send_reply_button') }}</button>
                    </form>
                </div>
            </div>
        @else
            <div class="alert alert-warning text-center mt-4">
                {{ __('lang.ticket_show_ticket_closed_message') }}
            </div>
        @endif
    </div>
@endsection
