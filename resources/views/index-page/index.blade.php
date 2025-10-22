@extends('layouts.app')

@section('content')
    <header class="py-5 bg-primary text-white text-center">
        <div class="container">
            <h1 class="fw-bold mb-3">Добро пожаловать в SimpleDesk</h1>
            <p class="lead mb-4">Система для управления заявками и общения с техподдержкой.</p>
            <a href="#" class="btn btn-light btn-lg me-2">Создать тикет</a>
            <a href="#" class="btn btn-outline-light btn-lg">Войти в систему</a>
        </div>
    </header>

    <section class="py-5">
        <div class="container text-center">
            <h2 class="fw-semibold mb-4">Что умеет SimpleDesk</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Клиент</h5>
                            <p class="card-text">
                                Создавайте тикеты, следите за статусом своих заявок и получайте помощь от команды поддержки.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Агент</h5>
                            <p class="card-text">
                                Берите тикеты в работу, отвечайте клиентам и помогайте быстро решать проблемы пользователей.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Администратор</h5>
                            <p class="card-text">
                                Управляйте пользователями, назначайте роли и следите за эффективностью работы службы поддержки.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-5">
        <div class="container text-center">
            <h2 class="fw-semibold mb-4">Как это работает</h2>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <ol class="list-group list-group-numbered shadow-sm">
                        <li class="list-group-item">Пользователь создаёт заявку с описанием проблемы.</li>
                        <li class="list-group-item">Агент берёт заявку в работу и отвечает пользователю.</li>
                        <li class="list-group-item">Обсуждение продолжается, пока заявку не будет решён.</li>
                        <li class="list-group-item">Администратор следит за процессом и управляет ролями.</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
@endsection
