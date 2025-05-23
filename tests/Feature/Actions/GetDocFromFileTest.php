<?php

use Illuminate\Support\Facades\Storage;
use Prezet\Prezet\Exceptions\FrontmatterMissingException;
use Prezet\Prezet\Models\Document;
use Prezet\Prezet\Prezet;

it('can get docdata from a markdown file', function () {
    Storage::fake('prezet');
    Storage::disk(config('prezet.filesystem.disk'))->put('content/post1.md', '---
title: Post 1
date: 2023-05-01
excerpt: Post 1 Excerpt
---
# Post 1 Content');

    $doc = Prezet::getDocumentDataFromFile('content/post1.md');

    expect($doc->frontmatter)->toHaveKey('title', 'Post 1');
});

it('can get docdata from existing document record', function () {
    $hash = 'e13b8284a991ef208cd5675661918a13';
    $filepath = 'content/post1.md';
    $doc1 = Document::factory()->create([
        'filepath' => $filepath,
        'hash' => $hash,
        'frontmatter' => [
            'title' => 'Old title',
        ],
    ]);

    Storage::fake('prezet');
    Storage::disk(config('prezet.filesystem.disk'))->put($filepath, '---
title: New Title
date: 2023-05-01
excerpt: Post 1 Excerpt
---
# Post 1 Content');

    $doc = Prezet::getDocumentDataFromFile($filepath);

    // Expect record has an id since it came from the database
    expect($doc->id)->tobe($doc1->id);
    expect($doc->hash)->tobe($hash);
    // Expect frontmatter was not updated since hash and slug were the same
    expect($doc->frontmatter)->toHaveKey('title', 'Old title');
});

it('throws an exception if frontmatter keys are missing', function () {
    Storage::fake('prezet');
    Storage::disk(config('prezet.filesystem.disk'))->put('content/post1.md', '# Post 1 Content');

    Prezet::getDocumentDataFromFile('content/post1.md');
})->expectException(FrontmatterMissingException::class);
