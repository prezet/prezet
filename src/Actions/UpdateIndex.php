<?php

namespace Prezet\Prezet\Actions;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Prezet\Prezet\Data\DocumentData;
use Prezet\Prezet\Models\Document;
use Prezet\Prezet\Models\Heading;
use Prezet\Prezet\Models\Tag;
use Prezet\Prezet\Prezet;

class UpdateIndex
{
    public function handle(): void
    {
        $this->ensureDatabaseExists();

        $docs = Prezet::getDocumentDataFromFiles();

        // Get all current paths from filesystem
        $currentFiles = $this->normalizePaths($docs->pluck('filepath')->all());

        // Remove documents that no longer exist in the filesystem
        $this->removeDeletedDocuments($currentFiles);

        // Update or create documents
        $docs->each(function (DocumentData $doc) {
            $doc = $this->ensureDocumentHasKey($doc);
            $this->upsertDocument($doc);
        });

        $this->cleanupOrphanedTags();

        Prezet::updateSitemap();
    }

    protected function ensureDocumentHasKey(DocumentData $docData): DocumentData
    {
        if (! Config::boolean('prezet.slug.keyed')) {
            return $docData;
        }

        if ($docData->key) {
            return $docData;
        }

        $key = substr($docData->hash, -8);
        Prezet::setKey($docData->filepath, $key);

        // The hash has changed so best to completely reload document data
        return Prezet::getDocumentDataFromFile($docData->filepath);
    }

    /**
     * Remove documents that no longer exist in the filesystem
     *
     * @param  array<int, string>  $currentPaths
     */
    protected function removeDeletedDocuments(array $currentPaths): void
    {
        app(Document::class)::whereNotIn('filepath', $currentPaths)->each(function (Document $document) {
            // This will automatically delete related headings due to cascade
            $document->tags()->detach();
            $document->delete();
        });
    }

    /**
     * @param  array<mixed>  $paths
     * @return array<int, string>
     */
    protected function normalizePaths(array $paths): array
    {
        $normalized = [];

        foreach ($paths as $path) {
            if (is_string($path)) {
                $normalized[] = $path;
            }
        }

        return $normalized;
    }

    protected function upsertDocument(DocumentData $docData): void
    {
        // Check if document exists with same filepath and hash
        $existingDoc = app(Document::class)::where('filepath', $docData->filepath)
            ->where('hash', $docData->hash)
            ->first();

        // If document exists with same hash, no need to update
        if ($existingDoc) {
            return;
        }

        // Find document by filepath to update, or create new one
        $document = app(Document::class)::where('filepath', $docData->filepath)->first() ?? new Document;

        $this->updateDocumentAttributes($document, $docData);
        $this->updateHeadings($document, $docData->content);

        if ($docData->frontmatter->tags) {
            $this->setTags($document, $docData->frontmatter->tags);
        }
    }

    protected function updateDocumentAttributes(Document $document, DocumentData $docData): void
    {
        $document->fill([
            'filepath' => $docData->filepath,
            'key' => $docData->key,
            'slug' => $docData->slug,
            'category' => $docData->category,
            'draft' => $docData->draft,
            'hash' => $docData->hash,
            'content_type' => $docData->contentType,
            'frontmatter' => $docData->frontmatter,
            'created_at' => $docData->createdAt,
            'updated_at' => $docData->updatedAt,
        ]);

        $document->save();
    }

    protected function updateHeadings(Document $document, ?string $content): void
    {
        if (! $content) {
            // This shouldn't happen, but just in case
            throw new \RuntimeException('Cannot create headings. Document content is empty.');
        }

        // Delete existing headings
        $document->headings()->delete();

        $html = Prezet::parseMarkdown($content);
        $headings = Prezet::getFlatHeadings($html);
        $title = $document->frontmatter->title;

        // Add title as first heading
        array_unshift($headings, [
            'text' => $title,
            'level' => 1,
            'section' => '',
        ]);

        // Insert new headings
        foreach ($headings as $heading) {
            app(Heading::class)::create([
                'document_id' => $document->id,
                'text' => $heading['text'],
                'level' => $heading['level'],
                'section' => $heading['section'],
            ]);
        }
    }

    /**
     * @param  array<int, string>  $tags
     */
    protected function setTags(Document $document, array $tags): void
    {
        // Detach existing tags
        $document->tags()->detach();

        foreach ($tags as $tag) {
            $t = app(Tag::class)::firstOrCreate(
                ['name' => strtolower($tag)]
            );

            $document->tags()->attach($t->id);
        }
    }

    /**
     * Remove tags that aren't associated with any documents
     */
    protected function cleanupOrphanedTags(): void
    {
        app(Tag::class)::whereDoesntHave('documents')->delete();
    }

    private function ensureDatabaseExists(): void
    {
        $dbPath = Config::string('database.connections.prezet.database');

        if (! file_exists($dbPath)) {
            throw new \RuntimeException(
                "Prezet database not found at $dbPath.\n".
                "Please run 'php artisan prezet:index --fresh' to create the database."
            );
        }

        if (! Schema::connection('prezet')->hasTable('documents')) {
            throw new \RuntimeException(
                "Prezet database exists but is missing the 'documents' table.\n".
                "Please run 'php artisan prezet:index --fresh' to create the database."
            );
        }
    }
}
