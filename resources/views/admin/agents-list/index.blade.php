@extends('layouts.app')

@section('content')
    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-3 mt-3 ms-3">
        <h1 class="h4 mb-0">{{ __('lang.agents_list.agents') }}</h1>
    </div>

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('lang.agents_list.agents_list') }}</h5>

                    <div class="d-flex gap-2">
                        <a
                            id="toggleFiltersBtn"
                            class="text-center text-decoration-none text-dark d-flex align-items-center justify-content-center"
                            style="width: 40px; height: 40px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;"
                            role="button"
                            data-bs-toggle="tooltip"
                            title="{{ __('lang.agents_list.filter') }}">
                            <i class="bi bi-funnel fs-5"></i>
                        </a>

                        <x-create-button
                            routeName="{{ route('admin.agents.create') }}"
                            title="{{ __('lang.agents_list.create_agent') }}"
                        />
                    </div>
                </div>

                <div class="card shadow-sm mb-3 mt-1 d-none" id="agentFiltersCard">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ __('lang.agents_list.filter') }}</h5>
                        </div>
                    </div>

                    <form id="agentFilterForm">
                        <div class="row g-3 px-3 px-md-4 mt-1 px-lg-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">{{ __('lang.agents_list.role') }}</label>

                                <select name="role" class="form-select">
                                    <option value="">{{ __('lang.agents_list.default_option') }}</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label fw-bold">{{ __('lang.agents_list.status') }}</label>
                                <select id="statusFilter" name="status" class="form-select">
                                    <option value="">{{ __('lang.agents_list.default_option') }}</option>
                                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>
                                        {{ __('lang.agents_list.active') }}
                                    </option>
                                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>
                                        {{ __('lang.agents_list.inactive') }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">{{ __('lang.agents_list.department') }}</label>

                                <select name="department" class="form-select">
                                    <option value="">{{ __('lang.agents_list.default_option') }}</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">{{ __('lang.agents_list.team') }}</label>

                                <select name="team" class="form-select">
                                    <option value="">{{ __('lang.agents_list.default_option') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 px-lg-3 mt-3 mb-3">
                            <button type="submit" class="btn btn-outline-primary">{{ __('lang.agents_list.submit') }}</button>
                            <button type="button" id="resetFiltersBtn" class="btn btn-outline-danger">{{ __('lang.agents_list.reset') }}</button>
                            <button type="button" id="hideFiltersBtn" class="btn btn-outline-secondary">{{ __('lang.agents_list.hide_filters') }}</button>
                        </div>
                    </form>
                </div>

                <div class="mt-2 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <x-per-page-select
                            :per-page="$perPage"
                            :options="$options"
                        />

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
                    @include('admin.agents-list.partials.agent_table', ['agents' => $agents, 'head' => $head])
                </div>
            </div>
        </div>
    </div>
@endsection

<div class="modal fade" id="deactivateAgentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.agents_list.deactivate_agents') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="deactivateForm" action="{{ route('admin.agents.deactivate.bulk') }}" method="POST">
                    @csrf
                    <input type="hidden" name="selected_agents" id="selectedAgentsInput">

                    <div class="mb-4">
                        <p class="fw-bold">{{ __('lang.agents_list.choose_action_for_created_tickets') }}</p>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="requested_tickets_action" id="closeOpenTickets" value="close" checked>
                            <label class="form-check-label" for="closeOpenTickets">
                                {{ __('lang.agents_list.close_all_open_tickets') }}
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="requested_tickets_action" id="changeRequester" value="change_requester">
                            <label class="form-check-label" for="changeRequester">
                                {{ __('lang.agents_list.change_assignee') }}
                            </label>
                        </div>

                        <div id="requester-select-wrapper" class="mt-2 ms-4 d-none">
                            <label for="new_requester_id" class="form-label">
                                {{ __('lang.agents_list.reassign_to_agent') }}
                            </label>
                            <select class="form-select" name="new_requester_id" id="new_requester_id">
                                <option value="" disabled selected>{{ __('lang.agents_list.select_agent') }}</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}" data-agent-id="{{ $agent->id }}">{{ $agent->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <p class="fw-bold">{{ __('lang.agents_list.choose_action_for_assigned_tickets') }}</p>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="assigned_tickets_action" id="unassignTickets" value="unassign" checked>
                            <label class="form-check-label" for="unassignTickets">
                                {{ __('lang.agents_list.unassign_all_open_tickets') }}
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('lang.agents_list.close') }}</button>
                <button type="submit" form="deactivateForm" class="btn btn-dark">{{ __('lang.agents_list.deactivate') }}</button>
            </div>
        </div>
    </div>
</div>
