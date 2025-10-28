@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div class="container mt-4">
                <div class="row">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>{{ __('lang.tickets_list_title') }}</h1>
                        <a href="{{ route('tickets.create') }}" class="btn btn-success">
                            {{ __('lang.tickets_list_add_button') }}
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered align-middle">
                        <thead class="table-dark">
                        <tr>
                            <th scope="col">{{ __('lang.tickets_list_header_id') }}</th>
                            <th scope="col">{{ __('lang.tickets_list_header_subject') }}</th>
                            <th scope="col">{{ __('lang.tickets_list_header_author') }}</th>
                            <th scope="col">{{ __('lang.tickets_list_header_status') }}</th>
                            <th scope="col">{{ __('lang.tickets_list_header_priority') }}</th>
                            <th scope="col">{{ __('lang.tickets_list_header_created') }}</th>
                            <th scope="col" class="text-center">{{ __('lang.tickets_list_header_actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <th scope="row">{{ $ticket->id }}</th>

                                <td>{{ $ticket->title }}</td>

                                <td>{{ $ticket->user->name }}</td>

                                <td>
                                    {{-- Примечание: названия статусов и приоритетов приходят из БД --}}
                                    <span class="badge bg-{{ $ticket->status->name == 'Открыта' ? 'primary' : ($ticket->status->name == 'В работе' ? 'warning' : 'success') }}">
                                        {{ $ticket->status->name }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-{{ $ticket->priority->name == 'Низкий' ? 'info' : ($ticket->priority->name == 'Средний' ? 'warning' : 'danger') }}">
                                        {{ $ticket->priority->name }}
                                    </span>
                                </td>

                                <td>{{ $ticket->created_at->format('d.m.Y H:i') }}</td>

                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2" role="group">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('tickets.destroy', $ticket) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('lang.tickets_list_delete_confirm') }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">{{ __('lang.tickets_list_no_tickets_found') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    {{ $tickets->links('pagination::bootstrap-5') }}
                </div>
            </div>
@endsection
