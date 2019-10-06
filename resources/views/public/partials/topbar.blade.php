<nav class="topbar navbar navbar-expand-md bg-white navbar-light shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            @svg('img/icons/logo_offenevergaben.svg','test')
        </a>

        <form class="search-form float-left" action="{{ route('public::suchen') }}" method="GET">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Suche" aria-label="suchen" name="suche" value="{{ request()->route() && request()->route()->getName() == 'public::suchen' ? request()->input('suche') : '' }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit" id="navbar-search-button">
                        @svg('/img/icons/suche.svg','search')
                    </button>
                </div>
            </div>
        </form>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Side Of Navbar -->
            <!--
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">test</li>
            </ul>
            -->

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ml-auto">
                @if(!app()->environment('production'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->route() && request()->segment(1) == 'branchen' ? 'active' : '' }}" href="{{ route('public::branchen') }}">{{ __('Branchen') }}</a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link {{ request()->route() && request()->segment(1) == 'auftraggeber' ? 'active' : '' }}" href="{{ route('public::auftraggeber') }}">{{ __('Auftraggeber') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->route() && request()->segment(1) == 'lieferanten' ? 'active' : '' }}" href="{{ route('public::lieferanten') }}">{{ __('Lieferanten') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->route() && request()->segment(1) == 'aufträge' ? 'active' : '' }}" href="{{ route('public::auftraege') }}">{{ __('Aufträge') }}</a>
                </li>
            </ul>
        </div>
    </div>
</nav>