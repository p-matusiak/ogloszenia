<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="/favicon.png" sizes="64x64">
    <link rel="apple-touch-icon" href="/logo.png">

    <title>{{ $meta->title }}</title>
    <meta name="description" content="{{ $meta->description }}">
    <meta name="robots" content="{{ $meta->indexable ? 'index, follow' : 'noindex, follow' }}">
    <link rel="canonical" href="{{ $meta->canonical }}">

    {{-- Crawlery społecznościowe nie wykonują JavaScriptu: bez tych tagów każdy
         udostępniony link pokazuje nazwę serwisu zamiast tytułu ogłoszenia. --}}
    <meta property="og:type" content="{{ $meta->openGraphType }}">
    <meta property="og:site_name" content="{{ config('seo.site_name') }}">
    <meta property="og:locale" content="pl_PL">
    @if (filled(config('services.facebook.client_id')))
        <meta property="fb:app_id" content="{{ config('services.facebook.client_id') }}">
    @endif
    <meta property="og:title" content="{{ $meta->title }}">
    <meta property="og:description" content="{{ $meta->description }}">
    <meta property="og:url" content="{{ $meta->canonical }}">
    @if ($meta->imageUrl !== null)
        <meta property="og:image" content="{{ $meta->imageUrl }}">
    @endif

    <meta name="twitter:card" content="{{ $meta->imageUrl === null ? 'summary' : 'summary_large_image' }}">
    <meta name="twitter:title" content="{{ $meta->title }}">
    <meta name="twitter:description" content="{{ $meta->description }}">
    @if ($meta->imageUrl !== null)
        <meta name="twitter:image" content="{{ $meta->imageUrl }}">
    @endif

    @if ($meta->feedUrl !== null)
        <link rel="alternate" type="application/rss+xml"
              title="{{ $meta->feedTitle }}"
              href="{{ $meta->feedUrl }}">
    @endif

    <link rel="alternate" type="application/rss+xml"
          title="{{ config('seo.site_name') }} — najnowsze ogłoszenia"
          href="{{ route('feed') }}">

    @if ($meta->structuredData !== null)
        <script type="application/ld+json">{!! $meta->structuredData !!}</script>
    @endif

    @php
        $authenticatedUser = request()->user();
        $initialAuthUser = $authenticatedUser === null
            ? null
            : (new \App\Http\Resources\UserResource($authenticatedUser))->resolve(request());
    @endphp

    <script>
        (() => {
            const stored = localStorage.getItem('zunto:theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const shouldUseDark = stored === 'dark' || (stored !== 'light' && prefersDark);

            if (shouldUseDark) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script id="app-bootstrap" type="application/json">
        {!! json_encode(['user' => $initialAuthUser], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="antialiased">
    <div id="app"></div>
</body>
</html>
