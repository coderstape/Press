<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1280">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'LaraPress - Hot off the press')</title>

    <!-- Fonts -->

    <!-- Styles -->
    <link rel="stylesheet" href="#">
</head>

<body>

<div id="larapress">
    @yield('content')
</div>

<!-- Scripts -->

</body>
</html>