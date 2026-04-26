<?php

namespace Prezet\Prezet\Actions;

use Illuminate\Support\Facades\Route;
use Prezet\Prezet\Models\Document;
use Prezet\Prezet\Services\Sitemap;

class UpdateSitemap
{
    public function handle(): void
    {
        $this->ensureRoutesAreRegistered();

        $docs = app(Document::class)::query()
            ->orderBy('date', 'desc')
            ->where('draft', false)
            ->get();

        $sitemap = Sitemap::create();

        foreach ($docs as $doc) {
            $sitemapUrl = config('prezet.sitemap.origin');
            $sitemapUrl = is_string($sitemapUrl) ? $sitemapUrl : '';

            $sitemap->add(Sitemap::url($sitemapUrl.route('prezet.show', $doc->slug, false))
                ->setLastModificationDate($doc->updated_at)
                ->setChangeFrequency(Sitemap::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.7)
            );
        }

        if (config('app.env') !== 'testing') {
            $sitemap->writeToFile(public_path('prezet_sitemap.xml'));
        }
    }

    public function ensureRoutesAreRegistered(): void
    {
        Route::get('prezet/{slug}', function ($slug) {})
            ->name('prezet.show')
            ->where('slug', '.*');
    }
}
