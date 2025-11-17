@extends('layouts.app')

@push('styles')
    @vite(['resources/css/admin/agent-form.css'])
@endpush

@section('content')
    <x-alert/>

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Редактировать агента</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.agents.update', $agent) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.agents-list.partials.form-agent-info', ['agent' => $agent])
                    @include('admin.agents-list.partials.form-account-settings', ['agent' => $agent])
                    @include('admin.agents-list.partials.form-signature', ['agent' => $agent])

                    <div class="d-flex justify-content-start ms-3 mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Сохранить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/admin/agent-form.js')
@endpush
