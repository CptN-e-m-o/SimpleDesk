@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row d-flex justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            {{ isset($ticket) ? __('lang.ticket_form_edit_title', ['id' => $ticket->id]) : __('lang.ticket_form_create_title') }}
                        </h4>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="{{ isset($ticket) ? route('tickets.update', $ticket) : route('tickets.store') }}">
                            @csrf
                            @if(isset($ticket))
                                @method('PATCH')
                            @endif

                            <div class="mb-3">
                                <label for="title" class="form-label">{{ __('lang.ticket_form_subject_label') }}</label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $ticket->title ?? '') }}" required>
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">{{ __('lang.ticket_form_description_label') }}</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5" required>{{ old('description', $ticket->description ?? '') }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if(isset($currentUser) && $currentUser->isAdminOrAgent())
                                <div class="row">
                                    <div class="{{ isset($ticket) ? 'col-md-4' : 'col-md-6' }} mb-3">
                                        <label for="assigned_agent_id" class="form-label">{{ __('lang.ticket_form_agent_label') }}</label>
                                        <select class="form-select @error('assigned_agent_id') is-invalid @enderror" id="assigned_agent_id" name="assigned_agent_id">
                                            <option value="">{{ __('lang.ticket_form_agent_none') }}</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ old('assigned_agent_id', $ticket->assigned_agent_id ?? '') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->first_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('assigned_agent_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="{{ isset($ticket) ? 'col-md-4' : 'col-md-6' }} mb-3">
                                        <label for="priority_id" class="form-label">{{ __('lang.ticket_form_priority_label') }}</label>
                                        <select class="form-select @error('priority_id') is-invalid @enderror" id="priority_id" name="priority_id" required>
                                            @foreach($priorities as $priority)
                                                <option value="{{ $priority->id }}" {{ old('priority_id', $ticket->priority_id ?? '') == $priority->id ? 'selected' : '' }}>
                                                    {{ $priority->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('priority_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    @if(isset($ticket))
                                        <div class="col-md-4 mb-3">
                                            <label for="status_id" class="form-label">{{ __('lang.ticket_form_status_label') }}</label>
                                            <select class="form-select @error('status_id') is-invalid @enderror" id="status_id" name="status_id" required>
                                                @foreach($statuses as $status)
                                                    <option value="{{ $status->id }}" {{ old('status_id', $ticket->status_id ?? '') == $status->id ? 'selected' : '' }}>
                                                        {{ $status->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('status_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="d-flex justify-content-end border-top pt-3 mt-3">
                                <a href="{{ url()->previous() }}" class="btn btn-secondary me-2">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('lang.ticket_form_cancel_button') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>
                                    {{ isset($ticket) ? __('lang.ticket_form_save_button') : __('lang.ticket_form_create_button') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
