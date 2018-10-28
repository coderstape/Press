<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1280">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('larapress::meta')

    <title>@yield('title', 'LaraPress - Hot off the press')</title>

    <!-- Fonts -->

    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="font-sans">

@include('larapress::nav')

<div id="larapress" class="container">
    @yield('content')
</div>

<!-- Scripts -->


</body>
</html>