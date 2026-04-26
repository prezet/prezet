<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Prezet\Prezet\Actions\GetDocumentDataFromFiles;
use Prezet\Prezet\Data\DocumentData;
use Prezet\Prezet\Models\Document;
use Prezet\Prezet\Models\Heading;
use Prezet\Prezet\Models\Tag;
use Prezet\Prezet\Prezet;

test('throws exception when database missing', function () {
    unlink(Config::get('database.connections.prezet.database'));

    expect(fn () => Prezet::updateIndex())
        ->toThrow(RuntimeException::class, 'Prezet database not found');
});

test('throws exception when documents table missing', function () {
    Schema::connection('prezet')->dropIfExists('documents');

    expect(fn () => Prezet::updateIndex())
        ->toThrow(RuntimeException::class, 'Prezet database exists but is missing the \'documents\' table');
});

test('skips document when hash and filepath match', closure: function () {
    $doc = Document::factory()->create([
        'filepath' => 'test-doc.md',
        'hash' => 'abc123',
    ]);

    $mock = $this->mock(GetDocumentDataFromFiles::class);
    $mock->shouldReceive('handle')->once()->andReturn(collect([
        DocumentData::fromModel($doc),
    ]));

    Prezet::updateIndex();

    expect(Document::count())->toBe(1)
        ->and(Document::first()->hash)->toBe('abc123');
});

test('removes deleted documents and their relationships', function () {
    // Create initial documents
    $doc1 = Document::factory()->create([
        'slug' => 'doc-1',
        'hash' => 'abc123',
    ]);

    $doc2 = Document::factory()->create([
        'slug' => 'doc-2',
        'hash' => 'def456',
    ]);

    // Create headings
    Heading::create([
        'document_id' => $doc1->id,
        'text' => 'Heading 1',
        'level' => 1,
        'section' => '',
    ]);

    Heading::create([
        'document_id' => $doc2->id,
        'text' => 'Heading 2',
        'level' => 1,
        'section' => '',
    ]);

    // Create tags
    $tag1 = Tag::create(['name' => 'tag1']);
    $tag2 = Tag::create(['name' => 'tag2']);

    $doc1->tags()->attach($tag1);
    $doc2->tags()->attach([$tag1->id, $tag2->id]);

    // Mock getDocumentDataFromFiles to return only doc1
    $this->mock(GetDocumentDataFromFiles::class, function ($mock) use ($doc1) {
        $mock->shouldReceive('handle')->once()->andReturn(collect([
            DocumentData::fromModel($doc1)]));
    });

    Prezet::updateIndex();

    // Assert documents
    expect(Document::count())->toBe(1)
        ->and(Document::where('slug', 'doc-1')->exists())->toBeTrue()
        ->and(Document::where('slug', 'doc-2')->exists())->toBeFalse();

    // Assert headings
    expect(Heading::count())->toBe(1)
        ->and(Heading::where('text', 'Heading 1')->exists())->toBeTrue()
        ->and(Heading::where('text', 'Heading 2')->exists())->toBeFalse();

    // Assert tags
    expect(Tag::where('name', 'tag1')->exists())->toBeTrue()
        ->and(Tag::where('name', 'tag2')->exists())->toBeFalse();
});

test('updates document when hash changes', function () {
    // Create initial document
    $oldDoc = Document::factory()->create([
        'slug' => 'test-doc',
        'hash' => 'old-hash',
    ]);

    $t = Tag::firstOrCreate(
        ['name' => strtolower('old-tag')]
    );

    $oldDoc->tags()->attach($t->id);

    // Create a heading for the document
    Heading::create([
        'document_id' => $oldDoc->id,
        'text' => 'Old Heading',
        'level' => 1,
        'section' => '',
    ]);

    Storage::fake('prezet');
    $filepath = 'content/test-doc.md';
    Storage::disk(config('prezet.filesystem.disk'))->put($filepath, '---
title: Post 1
category: new-category
date: 2023-05-01
tags:
    - new-tag
excerpt: Post 1 Excerpt
---
# New Heading');

    Prezet::updateIndex();

    // Assert document was updated
    $updatedDoc = Document::where('filepath', $filepath)->first();
    expect($updatedDoc->hash)->toBe('90d0808a17c5949d40925284575235cd')
        ->and($updatedDoc->category)->toBe('new-category')
        ->and($updatedDoc->frontmatter->title)->toBe('Post 1')
        ->and($updatedDoc->frontmatter->excerpt)->toBe('Post 1 Excerpt');

    // Assert headings were updated
    $headings = $updatedDoc->headings()->get();
    expect($headings)->toHaveCount(1)
        ->and($headings[0]->text)->toBe('Post 1')
        ->and($headings[0]->level)->toBe(1);

    // Assert tags were updated
    $tags = $updatedDoc->tags()->pluck('name')->toArray();
    expect($tags)->toBe(['new-tag']);

    // Assert old tag was removed
    expect(Tag::where('name', 'old-tag')->exists())->toBeFalse();
});

test('creates new document when slug does not exist', function () {
    $filePath = 'content/new-doc.md';
    Storage::fake('prezet');
    Storage::disk(config('prezet.filesystem.disk'))->put($filePath, '---
title: Post 1
category: new-category
date: 2023-05-01
tags:
    - new-tag
    - test-tag
excerpt: Post 1 Excerpt
---
## New H2');

    Prezet::updateIndex();

    // Assert new document was created
    $newDoc = Document::where('filepath', $filePath)->first();
    expect($newDoc)->not->toBeNull()
        ->and($newDoc->hash)->toBe('4c214aa720579296e8e4ab2577bcc6d1');

    // Assert headings were created
    $headings = $newDoc->headings()->get();
    expect($headings)->toHaveCount(2)
        ->and($headings[0]->text)->toBe('Post 1')
        ->and($headings[0]->level)->toBe(1)
        ->and($headings[1]->text)->toBe('New H2')
        ->and($headings[1]->level)->toBe(2);

    // Assert tags were created
    $tags = $newDoc->tags()->pluck('name')->toArray();
    expect($tags)->toBe(['new-tag', 'test-tag']);
});
