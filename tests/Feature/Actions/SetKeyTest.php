<?php

use Illuminate\Support\Facades\Storage;
use Prezet\Prezet\Exceptions\FrontmatterMissingException;
use Prezet\Prezet\Prezet;

it('sets the key frontmatter', function () {
    // Setup
    Storage::fake('prezet');
    $initialContent = <<<'MD'
---
title: Test Post
excerpt: A test post
date: 2024-08-24
category: Getting Started
---
# Test Content
MD;

    $filepath = 'content/welcome-to-prezet.md';
    Storage::disk('prezet')->put($filepath, $initialContent);

    // Act
    Prezet::setKey($filepath, 'new-test-key');

    // Assert
    $updatedContent = Storage::disk('prezet')->get($filepath);
    expect($updatedContent)->toContain('key: new-test-key')
        ->and($updatedContent)->toContain('title: Test Post')
        ->and($updatedContent)->toContain('excerpt: A test post')
        ->and($updatedContent)->toContain('# Test Content');
});

it('updates existing key', function () {
    // Setup
    Storage::fake('prezet');
    $initialContent = <<<'MD'
---
title: Test Post
excerpt: A test post
date: 2024-08-24
category: Getting Started
key: existing-key
---
# Test Content
MD;

    $filepath = 'content/welcome-to-prezet.md';
    Storage::disk('prezet')->put($filepath, $initialContent);

    // Act
    Prezet::setKey($filepath, 'updated-key');

    // Assert
    $updatedContent = Storage::disk('prezet')->get($filepath);
    expect($updatedContent)->toContain('key: updated-key')
        ->and($updatedContent)->not->toContain('key: existing-key');
});

it('throws exception when frontmatter is missing', function () {
    Storage::fake('prezet');
    $contentWithoutFrontmatter = '# Just some markdown without frontmatter';

    $filepath = 'content/welcome-to-prezet.md';
    Storage::disk('prezet')->put($filepath, $contentWithoutFrontmatter);

    Prezet::setKey($filepath, 'test-key');
})->throws(FrontmatterMissingException::class);
