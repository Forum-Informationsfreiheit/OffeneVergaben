<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Open Graph -->
    <meta property="og:title" content="@yield('page:title',config('app.name'))" />
    <meta property="og:description" content="@yield('page:description',__('global.meta.description'))" />
    <meta property="og:image" content="@yield('page:image',link_to_image('logo/logo_1200x630.png'))" />
    @if(!App::environment('production'))
        <meta name="robots" content="noindex, nofollow" />
    @endif
    @yield('meta:append')

    <title>@yield('page:title') | {{ config('app.name') }}</title>

    <!-- Manifest / Favicons -->
    <link rel="manifest" href="{{ url('/img/favicons/manifest.json') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('/img/favicons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('/img/favicons/favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('/img/favicons/favicon-32x32.png') }}">
    <link rel='shortcut icon' type='image/x-icon' href='{{ url('/img/favicons/favicon.ico') }}'>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Archivo" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ link_to_stylesheet('app',true) }}">
    <link rel="stylesheet" href="{{ link_to_stylesheet('vendor/fontawesome/all.min') }}">

    <!-- Scripts -->
    <script src="{{ link_to_script('app',true) }}"></script>

    @yield('head:append')
</head>
<body class="@yield('body:class')">
<div id="wrapper">

    @include('public.partials.topbar')

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <!-- Begin Page Content -->
            <div class="container page-content">
                @include('flash::message')

                @yield('page:content')
            </div>

        </div>

    </div>

    @include('public.partials.footer')

</div>
    @if(app()->environment('production') && env('GOOGLE_ANALYTICS_ID'))
        @include('public.partials.google-analytics', ['gaId' => env('GOOGLE_ANALYTICS_ID')])
    @endif
    @yield('body:append')
</body>
</html>
