<table class="table table-hover">
    <thead>
    <tr>
        @foreach($head as $column)
            @if($column['type'] ?? '' === 'checkbox')
                <th><input type="checkbox" id="selectAllCheckbox"></th>
            @elseif($column['sortable'] ?? false)
                <th scope="col">
                    <x-sortable-link :column="$column['column']" :title="$column['title']" />
                </th>
            @else
                <th scope="col" class="text-decoration-none text-secondary">
                    {{ $column['title'] }}
                </th>
            @endif
        @endforeach
    </tr>
    </thead>
    <tbody>
    {{ $slot }}
    </tbody>
</table>
