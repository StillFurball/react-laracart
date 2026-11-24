<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script nonce="{{ Vite::cspNonce() }}">
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #ffffff;
            }

            html.dark {
                background-color: #0b0b0c;
            }
        </style>

        {{--
            Rendered on the server for link previews (WhatsApp, Facebook, X) and crawlers, which do not
            run JavaScript. Controllers pass $seo through withViewData(); everything has a default.
        --}}
        @php
            $seo ??= [];
            $siteName = config('app.name');
            $metaDescription = $seo['description'] ?? config('shop.seo.description');
            $canonical = $seo['canonical'] ?? url()->current();
        @endphp
        <meta name="description" content="{{ $metaDescription }}" data-inertia="description">
        @if (! config('shop.seo.indexable') || ($seo['noindex'] ?? false))
            <meta name="robots" content="noindex, follow">
        @endif
        <link rel="canonical" href="{{ $canonical }}">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:locale" content="id_ID">
        <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
        <meta property="og:title" content="{{ $seo['title'] ?? $siteName }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:image" content="{{ $seo['image'] ?? asset('images/og-default.png') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="theme-color" content="#008ECC">
        @isset($seo['schema'])
            {{-- JSON_HEX_TAG turns "<" into <, so data can never close this script element. --}}
            <script type="application/ld+json">@json($seo['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)</script>
        @endisset

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&display=swap">

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ isset($seo['title']) ? $seo['title'].' - '.$siteName : $siteName }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
