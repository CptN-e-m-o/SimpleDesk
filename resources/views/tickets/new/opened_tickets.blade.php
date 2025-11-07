@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Открытые заявки</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#">{{ __('lang.dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('lang.open_tickets') }}</li>
                </ol>
            </nav>
        </div>

        @include('tickets._table', [
            'title' => 'Открытые заявки',
            'tickets' => [
                [
                    'code' => 'HDSK-AAAA-0369',
                    'title' => 'Unable to log in the dashboard. (1)',
                    'requester' => 'Priya Kumar',
                    'agent' => 'Demo Agent',
                    'department' => 'Operation',
                    'status' => 'overdue',
                    'color' => 'border-warning',
                    'icon' => 'facebook',
                    'created' => '5 days ago',
                    'updated' => '3 days ago',
                ],
                [
                    'code' => 'HDSK-AAAA-0361',
                    'title' => 'test (1)',
                    'requester' => 'Demo Admin',
                    'agent' => 'Неназначенные',
                    'department' => 'Support',
                    'status' => 'normal',
                    'color' => 'border-success',
                    'icon' => 'globe',
                    'created' => '7 days ago',
                    'updated' => '7 days ago',
                ],
            ]
        ])
    </div>
@endsection
