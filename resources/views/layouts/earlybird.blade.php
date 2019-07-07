<!DOCTYPE html>
<html>
<head>
    <title>@yield('title','') | Offene Vergaben</title>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <meta name="language" content="de" />
    <meta name="country" content="Austria" />
    <meta name="robots" content="@yield('meta.robots','noindex, nofollow')" />
    <meta content="text/plain" charset="UTF-8">
    <style>
        table { border-spacing: 0; }
        table td, table th { text-align: left; }
        table td { border-top: 1px solid #ddd; padding: 3px 5px; vertical-align: top; }
        ul { list-style: normal; margin: 0; padding: 0 0 0 18px; }
    </style>
    @yield('styles:head')
    @yield('scripts:head')
</head>
<body class="@yield('body:class')">
    <h1>Offene Vergaben</h1>
@yield('body')
@yield('scripts:bottom')
@yield('body:close')
</body>
</html>