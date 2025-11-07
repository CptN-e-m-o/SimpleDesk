<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">{{ $title ?? 'Заявки' }}</h6>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-light border"><i class="bi bi-grid-3x3-gap"></i></button>
            <button class="btn btn-light border"><i class="bi bi-list"></i></button>
            <button class="btn btn-light border"><i class="bi bi-funnel"></i></button>
            <button class="btn btn-light border"><i class="bi bi-arrow-repeat"></i></button>
        </div>
    </div>

    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox">
                </div>
                <select class="form-select form-select-sm" style="width: 80px;">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                </select>
                <select class="form-select form-select-sm">
                    <option>Обновлено Нисходящий</option>
                    <option>Обновлено Восходящий</option>
                </select>
            </div>

            <div class="d-flex align-items-center">
                <div class="input-group input-group-sm" style="width: 250px;">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Искать по</button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">id</a></li>
                        <li><a class="dropdown-item" href="#">названию</a></li>
                    </ul>
                    <input type="text" class="form-control" placeholder="is:id">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <tbody>
                @foreach($tickets as $ticket)
                    <tr>
                        <td class="border-start border-3 {{ $ticket['color'] ?? 'border-secondary' }}" style="width: 50px;">
                            <div class="rounded-circle bg-light d-flex justify-content-center align-items-center" style="width:40px; height:40px;">
                                <i class="bi bi-person fs-5 text-secondary"></i>
                            </div>
                        </td>
                        <td>
                            <div>
                                <a href="#" class="fw-semibold text-decoration-none">
                                    [#{{ $ticket['code'] }}] {{ $ticket['title'] }}
                                </a>
                                <div class="text-muted small">
                                    Запросивший: <span class="text-primary">{{ $ticket['requester'] }}</span> &nbsp;
                                    Назначено: <span class="text-secondary">{{ $ticket['agent'] }}</span> &nbsp;
                                    Отдел: <span class="text-secondary">{{ $ticket['department'] }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-end">
                            @if($ticket['status'] === 'overdue')
                                <span class="badge bg-danger">Просрочено</span>
                            @endif
                            <i class="bi bi-{{ $ticket['icon'] ?? 'globe' }} text-secondary ms-2"></i>
                            <i class="bi bi-trash text-danger ms-2"></i>
                            <div class="small text-muted mt-2">
                                Создать: {{ $ticket['created'] }}<br>
                                Обновлено: {{ $ticket['updated'] }}
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-muted small mt-3">{{ count($tickets) }} записей</div>
    </div>
</div>
