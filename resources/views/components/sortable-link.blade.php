<a href="{{ $href }}"
   class="text-decoration-none sortable-link {{ $isActive ? 'text-dark' : 'text-secondary' }}">
    {{ $title }}

    <i class="bi
        @if($isActive)
            {{ $iconDirection == 'asc' ? 'bi-arrow-up' : 'bi-arrow-down' }}
        @else
            bi-arrow-down-up
        @endif
    "></i>
</a>
