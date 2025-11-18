@extends('layouts.app')

@section('content')
    <x-alert />

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">!Список агентов</h5>

                    <div class="d-flex gap-2">
                        <a
                            id="toggleFiltersBtn"
                            class="text-center text-decoration-none text-dark d-flex align-items-center justify-content-center"
                            style="width: 40px; height: 40px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;"
                            role="button"
                            data-bs-toggle="tooltip"
                            title="!Фильтр">
                            <i class="bi bi-funnel fs-5"></i>
                        </a>

                        <a href="{{ route('admin.agents.create') }}"
                           class="text-center text-decoration-none text-dark d-flex align-items-center justify-content-center"
                           style="width: 40px; height: 40px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;"
                           data-bs-toggle="tooltip"
                           title="!Создать агента">
                            <i class="bi bi-plus-circle fs-5"></i>
                        </a>
                    </div>
                </div>

                <div class="card shadow-sm mb-3 d-none" id="agentFiltersCard">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">!Фильтр</h5>
                        </div>
                    </div>

                    <form id="agentFilterForm">
                        <div class="row g-3 px-3 px-md-4 px-lg-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">!Роль:</label>

                                <select name="role" class="form-select">
                                    <option value="">— Все —</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label fw-bold">!Статус:</label>
                                <select id="statusFilter" name="status" class="form-select">
                                    <option value="">— Все —</option>
                                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>
                                        !Активный
                                    </option>
                                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>
                                        !Неактивный
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">!Отдел:</label>

                                <select name="department" class="form-select">
                                    <option value="">— Все —</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">!Команда:</label>

                                <select name="team" class="form-select">
                                    <option value="">— Все —</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 px-lg-3 mt-3 mb-3">
                            <button type="submit" class="btn btn-outline-primary">
                                Применить
                            </button>
                            <button type="button" class="btn btn-outline-danger">
                                Сбросить
                            </button>
                            <button type="button" class="btn btn-outline-secondary">
                                Скрыть фильтры
                            </button>
                        </div>
                    </form>
                </div>

                <div class="mt-2 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <label for="perPageSelect" class="form-label mb-0 align-middle">!Показывать по:</label>
                        <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;">
                            @foreach ($options as $option)
                                <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>

                        <button type="button" id="deactivate-selected-btn" class="btn btn-light border v-popper--has-tooltip d-none"
                                data-bs-toggle="modal" data-bs-target="#deactivateAgentModal">
                            <i class="fas fa-trash"></i>
                            !Отключить выбранных
                        </button>
                    </div>

                    <div>
                        <input type="text" class="form-control form-control-sm"
                               placeholder="!Введите текст и нажмите Enter для поиска..."
                               style="width: 350px;">
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div id="agent-list-container">
                    @include('admin.agents-list.partials.agent_table', ['agents' => $agents])
                </div>
            </div>
        </div>
    </div>
@endsection

<div class="modal fade" id="deactivateAgentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">!Деактивировать агентов</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="deactivateForm" action="{{ route('admin.agents.deactivate.bulk') }}" method="POST">
                    @csrf
                    <input type="hidden" name="selected_agents" id="selectedAgentsInput">

                    <div class="mb-4">
                        <p class="fw-bold">!Выберите что должно произойти с заявками, созданными Агентом</p>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="requested_tickets_action" id="closeOpenTickets" value="close" checked>
                            <label class="form-check-label" for="closeOpenTickets">!Закрыть все открытые заявки</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="requested_tickets_action" id="changeRequester" value="change_requester">
                            <label class="form-check-label" for="changeRequester">!Сменить назначенного</label>
                        </div>

                        <div id="requester-select-wrapper" class="mt-2 ms-4 d-none">
                            <label for="new_requester_id" class="form-label">!Переназначить агенту:</label>
                            <select class="form-select" name="new_requester_id" id="new_requester_id">
                                <option value="" disabled selected>!-- Select an agent --</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}" data-agent-id="{{ $agent->id }}">{{ $agent->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <p class="fw-bold">!Выберите что нужно сделать с заявками, назначенными Агенту</p>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="assigned_tickets_action" id="unassignTickets" value="unassign" checked>
                            <label class="form-check-label" for="unassignTickets">!Снять назначение со всех открытых заявок</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">!Закрыть</button>
                <button type="submit" form="deactivateForm" class="btn btn-dark">!Деактивировать</button>
            </div>
        </div>
    </div>
</div>
