<?php

use Prezet\Prezet\Services\Sitemap;

test('it renders sitemap urls with fluent metadata', function () {
    $sitemap = Sitemap::create()
        ->add(
            Sitemap::url('https://example.com/posts/one?ref=prezet&topic=sitemap')
                ->setLastModificationDate(new DateTimeImmutable('2024-01-02 03:04:05 UTC'))
                ->setChangeFrequency(Sitemap::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.7)
        );

    expect($sitemap->render())->toBe(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/posts/one?ref=prezet&amp;topic=sitemap</loc>
        <lastmod>2024-01-02T03:04:05+00:00</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
</urlset>

XML);
});

test('it skips blank and duplicate urls', function () {
    $sitemap = Sitemap::create()
        ->add('')
        ->add('  ')
        ->add('https://example.com/posts/one')
        ->add('https://example.com/posts/one');

    expect($sitemap->urls())->toHaveCount(1)
        ->and($sitemap->hasUrl('https://example.com/posts/one'))->toBeTrue()
        ->and($sitemap->getUrl('https://example.com/posts/two'))->toBeNull();
});

test('it writes the rendered sitemap to a file', function () {
    $path = tempnam(sys_get_temp_dir(), 'prezet-sitemap-');

    Sitemap::create()
        ->add('https://example.com/posts/one')
        ->writeToFile($path);

    expect(file_get_contents($path))->toBe(Sitemap::create()
        ->add('https://example.com/posts/one')
        ->render());
});
