@extends('layouts.app')

@section('content')
    <div class="container mt-4">

        <div class="container mt-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">
                    {{ __('lang.dashboard') }}
                </h1>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('tickets.index', 'open') }}" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle flex-shrink-0 me-3" style="width: 56px; height: 56px;">
                                <i class="bi bi-inbox fs-3"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ __('lang.open_tickets') }}</div>
                                <div class="fw-bold fs-5">{{ $counts['open'] }}</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('tickets.index', 'assigned') }}" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="d-flex align-items-center justify-content-center bg-info-subtle text-info rounded-circle flex-shrink-0 me-3" style="width: 56px; height: 56px;">
                                <i class="bi bi-person-lines-fill fs-3"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ __('lang.tickets_assigned_to_me') }}</div>
                                <div class="fw-bold fs-5">{{ $counts['assigned'] }}</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('tickets.index', 'overdue') }}" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="d-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle flex-shrink-0 me-3" style="width: 56px; height: 56px;">
                                <i class="bi bi-exclamation-triangle fs-3"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ __('lang.overdue_tickets') }}</div>
                                <div class="fw-bold fs-5">{{ $counts['overdue'] }}</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('tickets.index', 'unanswered') }}" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="d-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle flex-shrink-0 me-3" style="width: 56px; height: 56px;">
                                <i class="bi bi-arrow-return-left fs-3"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ __('lang.unanswered_tickets') }}</div>
                                <div class="fw-bold fs-5">{{ $counts['unanswered'] }}</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('tickets.index', 'unassigned') }}" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="d-flex align-items-center justify-content-center bg-secondary-subtle text-secondary rounded-circle flex-shrink-0 me-3" style="width: 56px; height: 56px;">
                                <i class="bi bi-person-x fs-3"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ __('lang.unassigned_tickets') }}</div>
                                <div class="fw-bold fs-5">{{ $counts['unassigned'] }}</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('tickets.index', 'pending_approvals') }}" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="d-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle flex-shrink-0 me-3" style="width: 56px; height: 56px;">
                                <i class="bi bi-patch-check fs-3"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ __('lang.pending_approvals') }}</div>
                                <div class="fw-bold fs-5">{{ $counts['pending_approvals'] }}</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-exclamation-circle-fill text-danger me-2"></i>
                        Требует внимания
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-bar-chart-line-fill me-2"></i>
                        Моя производительность
                    </div>
                    <div class="card-body">
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-list-check me-2"></i>
                        К исполнению
                    </div>
                    <div class="card-body">

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
