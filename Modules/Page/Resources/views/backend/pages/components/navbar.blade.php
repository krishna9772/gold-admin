@section('navbar')
<!-- Example Navbar HTML Structure -->
<nav
class="nav navbar navbar-expand-xl navbar-light iq-navbar header-hover-menu left-border {{ !empty(getCustomizationSetting('navbar_show')) ? getCustomizationSetting('navbar_show') : '' }} {{ getCustomizationSetting('header_navbar') }}">
    <div class="container-fluid navbar-inner">
    <a href="{{ route('backend.home') }}" class="navbar-brand">
            <div class="logo-main">
                <div class="logo-mini d-none">
                    <img src="{{ asset(setting('mini_logo')) }}" height="30" alt="{{ app_name() }}">
                </div>

                <div class="logo-dark">
                    <img src="{{ asset(setting('dark_logo')) }}" height="30" alt="{{ app_name() }}">
                </div>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">

                {{-- YIELD NAV-ITEMS --}}
                @yield('nav-item')

            </ul>
        </div>
    </div>
</nav>

@endsection
