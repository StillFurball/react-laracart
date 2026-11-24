<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use XMLWriter;

class SeoController extends Controller
{
    /**
     * Paths with nothing for search engines: account, checkout and admin pages.
     *
     * @var list<string>
     */
    private const PRIVATE_PATHS = [
        '/admin', '/cart', '/checkout', '/orders', '/account', '/settings', '/dashboard',
        '/login', '/register', '/forgot-password', '/reset-password', '/email', '/user', '/two-factor-challenge',
    ];

    /**
     * Crawl rules. A deployment that should not be indexed (staging, demo) blocks everything.
     */
    public function robots(): Response
    {
        $lines = config('shop.seo.indexable')
            ? [
                'User-agent: *',
                ...array_map(fn (string $path): string => "Disallow: {$path}", self::PRIVATE_PATHS),
                '',
                'Sitemap: '.route('sitemap'),
            ]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Every public page: storefront pages, category listings and active products (streamed from the
     * database in chunks so a large catalogue stays cheap).
     */
    public function sitemap(): Response
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ([route('home'), route('products.index'), route('flash-sale.index'), route('terms'), route('privacy')] as $url) {
            self::url($xml, $url);
        }

        foreach (Category::orderBy('id')->lazy() as $category) {
            self::url($xml, route('products.index', ['category' => $category->slug]));
        }

        foreach (Product::active()->whereHas('variants')->orderBy('id')->lazy() as $product) {
            self::url($xml, route('products.show', $product), $product->updated_at?->toDateString());
        }

        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private static function url(XMLWriter $xml, string $location, ?string $lastModified = null): void
    {
        $xml->startElement('url');
        $xml->writeElement('loc', $location);

        if ($lastModified !== null) {
            $xml->writeElement('lastmod', $lastModified);
        }

        $xml->endElement();
    }
}
