@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Редактирование заявки</div>
                    <div class="card-body">
                        @include('tickets._form')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
