<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAllCheckbox"></th>
                <th scope="col">
                    <x-sortable-link column="last_name" title="ФИО" />
                </th>
                <th scope="col">
                    <x-sortable-link column="login" title="Логин" />
                </th>
                <th scope="col">
                    <x-sortable-link column="email" title="Электронная почта" />
                </th>
                <th class="text-decoration-none text-secondary">Телефон</th>
                <th class="text-decoration-none text-secondary">Информация об аккаунте</th>
                <th class="text-decoration-none text-secondary">Действия</th>
            </tr>
        </thead>
        <tbody>
        @foreach($agents as $agent)
            <tr>
                <td><input type="checkbox" class="row-checkbox" value="{{ $agent->id }}" {{ Auth::id() == $agent->id ? 'disabled' : '' }}></td>
                <td>{{ $agent->full_name }}</td>
                <td>{{ $agent->login ?? '-' }}</td>
                <td>{{ $agent->email }}</td>
                <td>{{ $agent->phone_number ?? '-'}}</td>
                <td>
                    <div>
                                        <span class="badge bg-{{ $agent->role_id->color() }}">
                                            {{ $agent->role_id->toString() }}
                                        </span>
                    </div>

                    <div class="mt-2">
                                        <span class="me-2" data-bs-toggle="tooltip" title="Email">
                                            @if($agent->email_verified_at)
                                                <i class="bi bi-envelope-check-fill text-success"></i>
                                            @else
                                                <i class="bi bi-envelope-slash-fill text-danger"></i>
                                            @endif
                                        </span>

                        <span class="me-2" data-bs-toggle="tooltip" title="Телефон">
                                            @if($agent->phone_verified_at)
                                <i class="bi bi-telephone-fill text-success"></i>
                            @else
                                <i class="bi bi-telephone-x-fill text-danger"></i>
                            @endif
                                        </span>

                        <span data-bs-toggle="tooltip" title="2FA">
                                            @if($agent->google2fa_enabled)
                                <i class="bi bi-shield-lock-fill text-success"></i>
                            @else
                                <i class="bi bi-shield-slash-fill text-danger"></i>
                            @endif
                                        </span>
                    </div>
                </td>
                <td></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-end">
    {{ $agents->links() }}
</div>
