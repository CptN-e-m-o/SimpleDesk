<a href="{{ $href }}"
   class="text-decoration-none sortable-link {{ $isActive ? 'text-dark' : 'text-secondary' }}">
    {{ $title }}

    <i class="bi {{ $iconDirection == 'asc' ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
</a>
