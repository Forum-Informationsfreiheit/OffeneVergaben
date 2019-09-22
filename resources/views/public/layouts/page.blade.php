<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if(!App::environment('production'))
        <meta name="robots" content="noindex, nofollow" />
    @endif

    <title>@yield('page:title')| {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Archivo" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ link_to_stylesheet('app',true) }}">

    <!-- Scripts -->
    <script src="{{ link_to_script('app',true) }}"></script>
</head>
<body class="@yield('body:class')">
<div id="wrapper">

    @include('public.partials.topbar')

            <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            @yield('page:header')

            <!-- Begin Page Content -->
            <div class="container page-content">
                <div class="row">
                    <div class="offset-2 col-md-8">
                        @yield('page:content')
                    </div>
                </div>
            </div>

        </div>

    </div>

    @include('public.partials.footer')

</div>
</body>
</html>
