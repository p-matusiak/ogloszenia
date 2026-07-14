<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($staticPages as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        <changefreq>{{ $url['changefreq'] }}</changefreq>
    </url>
@endforeach
@foreach ($categories as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @if ($url['lastmod'] !== null)
            <lastmod>{{ $url['lastmod'] }}</lastmod>
        @endif
        <changefreq>{{ $url['changefreq'] }}</changefreq>
    </url>
@endforeach
@foreach ($sellers as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @if ($url['lastmod'] !== null)
            <lastmod>{{ $url['lastmod'] }}</lastmod>
        @endif
        <changefreq>{{ $url['changefreq'] }}</changefreq>
    </url>
@endforeach
@foreach ($ads as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @if ($url['lastmod'] !== null)
            <lastmod>{{ $url['lastmod'] }}</lastmod>
        @endif
        <changefreq>{{ $url['changefreq'] }}</changefreq>
    </url>
@endforeach
</urlset>
