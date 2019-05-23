<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1280">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('press::meta')

    <title>@yield('title', 'Press - Hot off the press')</title>

    <!-- Fonts -->

    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="font-sans">

@include('press::nav')

<div id="press" class="container mx-auto">
    <div class="">
        @yield('content')
    </div>
</div>

<!-- Scripts -->


</body>
</html>