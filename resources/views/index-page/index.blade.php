@extends('layouts.app')

@section('content')
    <header class="py-5 bg-primary text-white text-center">
        <div class="container">
            <h1 class="fw-bold mb-3">{{ __('lang.header_title') }}</h1>
            <p class="lead mb-4">{{ __('lang.header_subtitle') }}</p>
            <a href="{{ route('tickets.create') }}" class="btn btn-light btn-lg me-2">{{ __('lang.create_ticket_button') }}</a>
            <a href="{{ route('tickets.index', ['category' => 'my']) }}" class="btn btn-outline-light btn-lg">
                {{ __('lang.my_tickets') }}
            </a>
        </div>
    </header>

    <section class="py-5">
        <div class="container text-center">
            <h2 class="fw-semibold mb-4">{{ __('lang.features_title') }}</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">{{ __('lang.features_client_title') }}</h5>
                            <p class="card-text">
                                {{ __('lang.features_client_text') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">{{ __('lang.features_agent_title') }}</h5>
                            <p class="card-text">
                                {{ __('lang.features_agent_text') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">{{ __('lang.features_admin_title') }}</h5>
                            <p class="card-text">
                                {{ __('lang.features_admin_text') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-5">
        <div class="container text-center">
            <h2 class="fw-semibold mb-4">{{ __('lang.how_it_works_title') }}</h2>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <ol class="list-group list-group-numbered shadow-sm">
                        <li class="list-group-item">{{ __('lang.how_it_works_step1') }}</li>
                        <li class="list-group-item">{{ __('lang.how_it_works_step2') }}</li>
                        <li class="list-group-item">{{ __('lang.how_it_works_step3') }}</li>
                        <li class="list-group-item">{{ __('lang.how_it_works_step4') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
@endsection
