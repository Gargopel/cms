<title>{{ $seo->title }}</title>
<meta name="description" content="{{ $seo->description }}">
@if ($seo->canonical)
    <link rel="canonical" href="{{ $seo->canonical }}">
@endif
@if ($seo->robots)
    <meta name="robots" content="{{ $seo->robots }}">
@endif
@if ($seo->ogTitle)
    <meta property="og:title" content="{{ $seo->ogTitle }}">
@endif
@if ($seo->ogDescription)
    <meta property="og:description" content="{{ $seo->ogDescription }}">
@endif
@if ($seo->ogType)
    <meta property="og:type" content="{{ $seo->ogType }}">
@endif
@if ($seo->ogUrl)
    <meta property="og:url" content="{{ $seo->ogUrl }}">
@endif
@if ($seo->ogImage)
    <meta property="og:image" content="{{ $seo->ogImage }}">
@endif
