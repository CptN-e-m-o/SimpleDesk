@extends('layouts.app')

@section('content')
    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-3 mt-3 ms-3">
        <h1 class="h4 mb-0">{{ __('lang.teams_list.teams') }}</h1>
    </div>

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('lang.teams_list.teams_list') }}</h5>

                    <div class="d-flex gap-2">

                        <x-create-button
                            routeName="{{ route('admin.teams.create') }}"
                            title="{{ __('lang.teams_list.create_team') }}"
                        />
                    </div>
                </div>

                <div class="mt-2 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <label for="perPageSelect" class="form-label mb-0 align-middle">{{ __('lang.agents_list.show_by') }}</label>
                        <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;">

                        </select>

                        <button type="button" id="deactivate-selected-btn" class="btn btn-light border v-popper--has-tooltip d-none"
                                data-bs-toggle="modal" data-bs-target="#deactivateAgentModal">
                            <i class="fas fa-trash"></i>
                            {{ __('lang.agents_list.deactivate_chosen') }}
                        </button>
                    </div>

                    <div>
                        <input
                            type="text"
                            id="agentSearchInput"
                            class="form-control form-control-sm"
                            placeholder="{{ __('lang.agents_list.enter_text_press_enter') }}"
                            style="width: 350px;">
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div id="agent-list-container">

                </div>
            </div>
        </div>
    </div>
@endsection
