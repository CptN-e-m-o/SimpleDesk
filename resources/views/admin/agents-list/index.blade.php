@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">

        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Список агентов</h5>

                <div>
                    <label for="perPageSelect" class="form-label me-2">Показывать по:</label>
                    <select id="perPageSelect" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                        @php $options = [10, 20, 50]; @endphp
                        @foreach ($options as $option)
                            <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="card-body">
                <div id="agent-list-container">
                    @include('admin.agents-list.partials.agent_table', ['agents' => $agents])
                </div>
            </div>
        </div>
    </div>
@endsection
