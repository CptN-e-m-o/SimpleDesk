@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div class="container mt-4">
                <div class="row">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>{{ __('lang.users_list_title') }}</h1>
                        <a href="{{ route('users.create') }}" class="btn btn-success">
                            {{ __('lang.users_list_add_button') }}
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered align-middle">
                        <thead class="table-dark">
                        <tr>
                            <th scope="col">{{ __('lang.users_list_header_id') }}</th>
                            <th scope="col">{{ __('lang.users_list_header_name') }}</th>
                            <th scope="col">{{ __('lang.users_list_header_email') }}</th>
                            <th scope="col">{{ __('lang.users_list_header_role') }}</th>
                            <th scope="col" class="text-center">{{ __('lang.users_list_header_actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($users as $user)
                            <tr>
                                <th scope="row">{{ $user->id }}</th>

                                <td>{{ $user->first_name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge bg-{{ $user->role_id->color() }}">{{ $user->role_id->toString() }}</span>
                                </td>

                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2" role="group" aria-label="{{ __('lang.users_list_actions_aria_label') }}">
                                        <a href="{{ route('users.show', $user->id) }}" class="btn btn-primary btn-sm">
                                            {{ __('lang.users_list_view_button') }}
                                        </a>

                                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm">
                                            {{ __('lang.users_list_edit_button') }}
                                        </a>

                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('lang.users_list_delete_confirm') }}')">
                                                {{ __('lang.users_list_delete_button') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">{{ __('lang.users_list_no_users_found') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    {{ $users->links('pagination::bootstrap-5') }}
                </div>
            </div>
@endsection
