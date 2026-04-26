<?php

use Illuminate\Support\Facades\Storage;
use Prezet\Prezet\Exceptions\FrontmatterMissingException;
use Prezet\Prezet\Models\Document;
use Prezet\Prezet\Prezet;

it('sets the ogimage frontmatter', function () {
    // Setup
    Storage::fake('prezet');
    $initialContent = <<<'MD'
---
title: Test Post
description: A test post
---
# Test Content
MD;
    $filepath = 'content/welcome-to-prezet.md';
    Storage::disk('prezet')->put($filepath, $initialContent);

    $doc = Document::factory()->create([
        'filepath' => $filepath,
    ]);

    // Act
    Prezet::setOgImage($doc, 'test-image.jpg');

    // Assert
    $updatedContent = Storage::disk('prezet')->get('content/welcome-to-prezet.md');
    expect($updatedContent)->toContain('image: test-image.jpg')
        ->and($updatedContent)->toContain('title: Test Post')
        ->and($updatedContent)->toContain('description: A test post')
        ->and($updatedContent)->toContain('# Test Content');
});

it('throws exception when frontmatter is missing', function () {
    Storage::fake('prezet');
    $contentWithoutFrontmatter = '# Just some markdown without frontmatter';

    Storage::disk('prezet')->put('content/welcome-to-prezet.md', $contentWithoutFrontmatter);

    $doc = Document::factory()->create([
        'slug' => 'welcome-to-prezet',
        'filepath' => 'content/welcome-to-prezet.md',
    ]);

    Prezet::setOgImage($doc, 'test-image.jpg');
})->throws(FrontmatterMissingException::class);
