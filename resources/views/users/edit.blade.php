@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('lang.edit_user_page_title', ['name' => $user->name]) }}</div>
                    <div class="card-body">
                        @include('users._form', ['user' => $user])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
