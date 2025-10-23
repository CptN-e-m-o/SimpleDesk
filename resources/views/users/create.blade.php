@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Добавление нового пользователя</div>
                    <div class="card-body">
                        @include('users._form')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
