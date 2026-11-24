<?php

use App\Models\Category;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Models\ProductVariant;

test('robots.txt keeps private pages out and announces the sitemap', function () {
    config(['shop.seo.indexable' => true]);

    $response = $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect($response->getContent())
        ->toContain("Disallow: /admin\n")
        ->toContain("Disallow: /checkout\n")
        ->toContain('Sitemap: '.route('sitemap'))
        ->not->toContain("Disallow: /\n");
});

test('a deployment that must not be indexed blocks every crawler', function () {
    config(['shop.seo.indexable' => false]);

    expect($this->get('/robots.txt')->getContent())->toBe("User-agent: *\nDisallow: /\n");

    $this->get(route('home'))->assertSee('<meta name="robots" content="noindex, follow">', false);
});

test('the sitemap lists storefront pages, categories and active products only', function () {
    $category = Category::factory()->create(['slug' => 'audio']);
    $active = Product::factory()->for($category)->withVariant()->create(['slug' => 'speaker-mini']);
    Product::factory()->draft()->withVariant()->create(['slug' => 'masih-draft']);

    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $xml = simplexml_load_string($response->getContent());
    // Every child is named "url", so keep positional keys or they overwrite each other.
    $locations = collect(iterator_to_array($xml->url, false))->map(fn ($url) => (string) $url->loc);

    expect($locations)
        ->toContain(route('home'))
        ->toContain(route('flash-sale.index'))
        ->toContain(route('products.index', ['category' => 'audio']))
        ->toContain(route('products.show', $active))
        ->not->toContain(route('products.show', 'masih-draft'));
});

test('a product page renders share tags and schema.org data with the price paid today', function () {
    $offer = FlashSaleItem::factory()->create();
    $product = $offer->variant->product;
    ProductVariant::factory()->for($product)->create(['price' => 150000, 'stock' => 0]);

    $response = $this->get(route('products.show', $product))->assertOk();

    $response->assertSee('<meta property="og:type" content="product">', false)
        ->assertSee('<meta property="og:title" content="'.e($product->name).'">', false)
        ->assertSee('<link rel="canonical" href="'.route('products.show', $product).'">', false);

    preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $response->getContent(), $match);
    $schema = json_decode($match[1], true);

    expect($schema['@type'])->toBe('Product');
    expect($schema['offers'])
        ->lowPrice->toBe(80000)
        ->highPrice->toBe(150000)
        ->availability->toBe('https://schema.org/InStock');
});

test('a product name cannot break out of the structured data script', function () {
    $product = Product::factory()->withVariant()->create(['name' => 'Kaos </script><script>alert(1)</script>']);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertDontSee('</script><script>alert(1)', false)
        ->assertSee('</script>', false);
});

test('search results are kept out of the index while category listings are canonical', function () {
    config(['shop.seo.indexable' => true]);
    Category::factory()->create(['slug' => 'laptop']);

    $this->get(route('products.index', ['q' => 'kabel']))
        ->assertSee('<meta name="robots" content="noindex, follow">', false);

    $this->get(route('products.index', ['category' => 'laptop', 'sort' => 'price_asc']))
        ->assertDontSee('noindex', false)
        ->assertSee('<link rel="canonical" href="'.route('products.index', ['category' => 'laptop']).'">', false);
});

test('the terms and privacy pages are public', function (string $route, string $component) {
    $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    ['terms', 'legal/terms'],
    ['privacy', 'legal/privacy'],
]);

test('the store only calls itself a demo while the payment simulator is on', function (bool $simulator) {
    config(['shop.payment_simulator' => $simulator]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('demoMode', $simulator));
})->with([true, false]);
