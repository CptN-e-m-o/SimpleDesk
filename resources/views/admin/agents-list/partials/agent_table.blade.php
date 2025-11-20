<div class="table-responsive mb-4">
    <x-table :head="$head">
        @foreach($agents as $agent)
            <tr>
                <td>
                    <input type="checkbox" class="row-checkbox form-check-input"
                           value="{{ $agent->id }}"
                        {{ Auth::id() == $agent->id ? 'disabled' : '' }}>
                </td>

                <td>{{ $agent->full_name }}</td>
                <td>{{ $agent->login ?? '-' }}</td>
                <td>{{ $agent->email }}</td>
                <td>{{ $agent->phone_number ?? '-' }}</td>

                <td>
                    <div>
                        <span class="badge bg-{{ $agent->role_id->color() }}">
                            {{ $agent->role_id->toString() }}
                        </span>
                    </div>

                    <div class="mt-2 d-flex align-items-center gap-2">
                        <i class="bi {{ $agent->email_verified_at ? 'bi-envelope-check-fill text-success' : 'bi-envelope-slash-fill text-danger' }}"
                           data-bs-toggle="tooltip" title="{{ __('lang.agents_list.email_verified') }}"></i>

                        <i class="bi {{ $agent->phone_verified_at ? 'bi-telephone-fill text-success' : 'bi-telephone-x-fill text-danger' }}"
                           data-bs-toggle="tooltip" title="{{ __('lang.agents_list.phone_verified') }}"></i>

                        <i class="bi {{ $agent->google2fa_enabled ? 'bi-shield-lock-fill text-success' : 'bi-shield-slash-fill text-danger' }}"
                           data-bs-toggle="tooltip" title="{{ __('lang.agents_list.two_factor') }}"></i>
                    </div>
                </td>

                <td>
                    <div class="agent-actions d-flex gap-2">
                        <a href="{{ route('admin.agents.edit', $agent) }}"
                           class="text-center text-decoration-none text-dark d-flex align-items-center justify-content-center"
                           style="width: 40px; height: 40px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;"
                           data-bs-toggle="tooltip"
                           title="{{ __('lang.agents_list.edit') }}">
                            <i class="bi bi-pencil-square fs-5"></i>
                        </a>

                        <div class="dropdown">
                            <button type="button"
                                    class="text-dark d-flex align-items-center justify-content-between px-2"
                                    style="width: 60px; height: 40px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    title="{{ __('lang.agents_list.view') }}">
                                <i class="bi bi-eye fs-5"></i>
                                <i class="bi bi-caret-down-fill fs-6 ms-1"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">{{ __('lang.agents_list.view_as_agent') }}</a></li>
                                <li><a class="dropdown-item" href="#">{{ __('lang.agents_list.view_as_user') }}</a></li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>
</div>

<div class="d-flex justify-content-end">
    {{ $agents->links() }}
</div>
