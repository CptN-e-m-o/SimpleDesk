@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('lang.create_ticket_page_title') }}</div>
                    <div class="card-body">
                        @include('tickets._form')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
