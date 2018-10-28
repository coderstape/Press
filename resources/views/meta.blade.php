<meta name="robots" content="index, follow" />
<meta http-equiv="expires" content="31536000"/>

<meta name="description" content="{{ app('LaraPress')->meta('description') }}">
<meta name="author" content="{{ app('LaraPress')->meta('author') }}" />
<meta name="copyright" content="{{ app('LaraPress')->meta('copyright') }}" />
<meta name="keywords" content="{{ app('LaraPress')->meta('keywords') }}" />

<meta property="og:title" content="{{ app('LaraPress')->meta('title') }}" />
<meta property="og:type" content="article" />
<meta property="og:url" content="{{ config('app.url') . config('larapress.path') }}" />
<meta property="og:description" content="{{ app('LaraPress')->meta('description') }}" />
<meta property="og:locale" content="{{ app('LaraPress')->meta('locale') }}" />
<meta property="og:site_name" content="{{ app('LaraPress')->meta('site_name') }}" />
<meta property="og:image" content="{{ app('LaraPress')->meta('image') }}" />