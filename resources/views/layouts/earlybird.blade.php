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
        .pagination-wrapper {
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .pagination-wrapper:after {
            display: block;
            content: '';
            clear: left;
        }
        ul.pagination {
            list-style: none;
        }
        ul.pagination li {
            float: left;
            padding-right: 5px;
        }
        .show-all-link {
            display: inline-block;
            margin-left: 20px;
        }
        nav {
            text-align: center;
        }
        nav ul {
            display: inline-block;
            list-style: none;
        }
        nav ul li {
            float: left;
        }
        nav ul li:after {
            display: inline-block;
            content: '|';
            margin-left: 5px;
            margin-right: 5px;
        }
        nav ul li:last-of-type:after {
            display: none;
        }
        nav ul:after {
            content: '';
            display: block;
            clear: both;
        }
    </style>
    @yield('styles:head')
    @yield('scripts:head')
</head>
<body class="@yield('body:class')">
<nav>
    <ul>
        <li><a href="{{ url('/datasets') }}">Datasets</a></li>
        <li><a href="{{ url('/bekanntgaben') }}">Bekanntgaben</a></li>
        <li><a href="{{ url('/organizations') }}">Organisationen</a></li>
        <li><a href="{{ url('/cpvs') }}">CPV Codes</a></li>
    </ul>
</nav>
    <h1>Offene Vergaben</h1>
@yield('body')
@yield('scripts:bottom')
@yield('body:close')
</body>
</html>