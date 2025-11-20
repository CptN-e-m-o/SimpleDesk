@extends('layouts.app')

@section('content')
    <x-alert/>

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">{{ __('lang.teams_list.create_team') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.teams.store') }}" novalidate>
                    @csrf
                    @include('admin.teams.partials.form', ['team' => null, 'agents' => $agents])
                    <div class="d-flex justify-content-start ms-3 mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> {{ __('lang.teams_form.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/common/rich-editor.js')
    @vite('resources/js/common/combobox.js')
@endpush
