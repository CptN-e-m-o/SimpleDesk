@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">

        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Список агентов</h5>
            </div>

            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox"></th>
                            <th>ФИО</th>
                            <th>Логин</th>
                            <th>Электронная почта</th>
                            <th>Телефон</th>
                            <th>Информация об аккаунте</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($agents as $agent)
                            <tr>
                                <td><input type="checkbox"></td>
                                <td>{{ $agent->full_name }}</td>
                                <td></td>
                                <td>{{ $agent->email }}</td>
                                <td>{{ $agent->phone_number }}</td>
                                <td></td>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
