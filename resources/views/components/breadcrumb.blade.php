<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        @foreach($items as $index => $item)
            @if(isset($item['url']) && $index !== count($items) - 1)
                <li class="breadcrumb-item">
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                </li>
            @else
                <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
            @endif
        @endforeach
    </ol>
    {{--
        How to use it
            <x-breadcrumb :items="[
            ['label' => __('Dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('Agents'), 'url' => route('admin.agents.index')]
        ]" />
        </div>
    --}}
</nav>
