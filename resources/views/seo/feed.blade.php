<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ $title }}</title>
    <link>{{ $siteUrl->route('landing') }}</link>
    <description>{{ config('seo.default_description') }}</description>
    <language>pl-pl</language>
    <lastBuildDate>{{ $lastBuildDate }}</lastBuildDate>
    <atom:link href="{{ $selfUrl }}" rel="self" type="application/rss+xml"/>
@foreach ($ads as $ad)
    <item>
        <title>{{ $ad->title }}</title>
        <link>{{ $siteUrl->route('ads.show', ['slug' => $ad->slug]) }}</link>
        <guid isPermaLink="true">{{ $siteUrl->route('ads.show', ['slug' => $ad->slug]) }}</guid>
        <pubDate>{{ $ad->published_at?->toRssString() }}</pubDate>
        <category>{{ $ad->category->name }}</category>
        <description>{{ $text->feedDescription($ad) }}</description>
        @if ($ad->primaryImage !== null)
            <enclosure url="{{ $ad->primaryImage->url() }}" length="{{ $ad->primaryImage->size_bytes }}" type="{{ $ad->primaryImage->mimeType() }}"/>
        @endif
    </item>
@endforeach
</channel>
</rss>
