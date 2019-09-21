<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            @svg('img/icons/logo_offenevergaben.svg','test')
        </a>
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
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('public::auftraggeber') }}">{{ __('Auftraggeber') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('public::lieferanten') }}">{{ __('Lieferanten') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('public::auftraege') }}">{{ __('Aufträge') }}</a>
                </li>
            </ul>
        </div>
    </div>
</nav>