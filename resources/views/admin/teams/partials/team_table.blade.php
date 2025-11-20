<div class="table-responsive mb-4">
    <x-table :head="$head">
        @foreach($teams as $team)
            <tr>
                <td>{{ $team->name }}</td>
                <td>{{ $team->members_count }}</td>
                <td>
                    @if($team->is_active)
                        <span class="badge bg-success">!Активна</span>
                    @else
                        <span class="badge bg-danger">!Неактивна</span>
                    @endif
                </td>
                <td></td>
                <td></td>
            </tr>
        @endforeach
    </x-table>
</div>
