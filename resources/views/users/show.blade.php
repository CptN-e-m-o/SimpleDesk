@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row d-flex justify-content-center">
            <div class="col-md-8">
                <div class="card user-card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">{{ __('lang.user_show_title') }}</h4>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-arrow-left me-2"></i>{{ __('lang.user_show_back_to_list') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center">
                                <h5 class="card-title mb-1">{{ $user->name }}</h5>
                                <span class="badge bg-{{ $user->role_id->color() }} fs-6">{{ $user->role_id->toString() }}</span>
                            </div>
                            <div class="col-md-8">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>{{ __('lang.user_show_id_label') }}</strong>
                                        <span>{{ $user->id }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>{{ __('lang.user_show_email_label') }}</strong>
                                        <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>{{ __('lang.user_show_registered_at_label') }}</strong>
                                        <span>{{ $user->created_at->format('d.m.Y H:i') }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>{{ __('lang.user_sho
