@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4 bg-light min-vh-100">

        @foreach($sections as $title => $items)
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white fw-semibold fs-5">{{ $title }}</div>
                <div class="card-body">
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-4 justify-content-center">
                        @foreach($items as $item)
                            <div class="col d-flex justify-content-center">
                                <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}" class="text-center text-decoration-none text-dark">
                                    <div class="rounded-circle bg-light border shadow-sm d-flex align-items-center justify-content-center mx-auto"
                                         style="width: 100px; height: 100px;">
                                        <i class="bi {{ $item['icon'] }} fs-2"></i>
                                    </div>
                                    <div class="mt-2 small fw-semibold">{{ $item['label'] }}</div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

    </div>
@endsection
