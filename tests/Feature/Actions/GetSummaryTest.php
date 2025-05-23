<?php

use Illuminate\Support\Facades\Storage;
use Prezet\Prezet\Prezet;

it('can parse the SUMMARY.md file', function () {
    $filename = 'SUMMARY.md';
    Storage::fake('prezet');
    Storage::disk(config('prezet.filesystem.disk'))->put('SUMMARY.md', <<<'EOT'
## 🚀 Features

-  [Markdown Powered](content/markdown)
-  [Blade Components](content/blade)
-  [Optimized Images](content/images)
-  [SEO](content/seo)

## 🎨 Customize
-   [Routes](content/customize/routes)
-   [Front Matter](content/customize/frontmatter)
-   [Blade Views](content/customize/blade-views)
-   [Controllers](content/customize/controllers)
EOT);

    $result = Prezet::getSummary($filename);

    expect($result)->toEqual(collect([
        [
            'title' => '🚀 Features',
            'links' => [
                ['title' => 'Markdown Powered', 'slug' => 'markdown'],
                ['title' => 'Blade Components', 'slug' => 'blade'],
                ['title' => 'Optimized Images', 'slug' => 'images'],
                ['title' => 'SEO', 'slug' => 'seo'],
            ],
        ],
        [
            'title' => '🎨 Customize',
            'links' => [
                ['title' => 'Routes', 'slug' => 'customize/routes'],
                ['title' => 'Front Matter', 'slug' => 'customize/frontmatter'],
                ['title' => 'Blade Views', 'slug' => 'customize/blade-views'],
                ['title' => 'Controllers', 'slug' => 'customize/controllers'],
            ],
        ],
    ]));
});
