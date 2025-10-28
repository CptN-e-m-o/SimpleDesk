<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ url('/') }}">{{ __('lang.navbar_brand') }}</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="{{ __('lang.navbar_toggle_navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">

                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('authorization') }}">{{ __('lang.navbar_login') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="{{ route('registration') }}">{{ __('lang.navbar_register') }}</a>
                    </li>
                @endguest

                @auth
                    <li class="nav-item">
                        <a class="nav-link" href="#">{{ __('lang.navbar_my_tickets') }}</a>
                    </li>

                    @if (Auth::user()->role_id !== \App\Enums\UserRole::Client->value)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('tickets.index') }}">{{ __('lang.navbar_all_tickets') }}</a>
                        </li>
                    @endif

                    @if (Auth::user()->role_id === \App\Enums\UserRole::Admin)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('users.index') }}">{{ __('lang.navbar_users') }}</a>
                        </li>
                    @endif

                        <li class="nav-item dropdown ms-3">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @if (session('locale') === 'en')
                                    🇬🇧 English
                                @else
                                    🇷🇺 Русский
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="{{ route('locale.switch', 'ru') }}">
                                        🇷🇺 <span class="ms-2">Русский</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="{{ route('locale.switch', 'en') }}">
                                        🇬🇧 <span class="ms-2">English</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item dropdown ms-3">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item disabled" href="#">
                                    {{ __('lang.navbar_my_profile') }}
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        {{ __('lang.navbar_logout') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
