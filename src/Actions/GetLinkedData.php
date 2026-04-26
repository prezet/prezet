<?php

namespace Prezet\Prezet\Actions;

use Illuminate\Support\Facades\Config;
use Prezet\Prezet\Data\DocumentData;
use Prezet\Prezet\Data\FrontmatterData;

class GetLinkedData
{
    /**
     * @return array<string, string|array<string, string>|array<string, string|array<string, string>>>
     */
    public function handle(DocumentData $document): array
    {
        $fm = $document->frontmatter;
        $author = $this->getAuthor($fm);
        $image = $this->getImage($fm);
        $publisher = $this->getPublisher();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $fm->title,
            'datePublished' => $document->createdAt->toIso8601String(),
            'dateModified' => $document->updatedAt->toIso8601String(),
            'author' => $author,
            'publisher' => $publisher,
            'image' => $image,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getAuthor(FrontmatterData $fm): array
    {
        $authors = Config::array('prezet.authors');

        // If frontmatter has an author and it exists in the config, use it
        if (isset($fm->author) && isset($authors[$fm->author])) {
            return $this->normalizeStringMap($authors[$fm->author]);
        }

        // Otherwise return the first author from the config
        return $this->normalizeStringMap(reset($authors));
    }

    private function getImage(FrontmatterData $fm): string
    {
        $publisher = $this->getPublisher();
        $publisherImage = $publisher['image'] ?? '';
        $publisherUrl = $publisher['url'] ?? '';

        // If no image in frontmatter, use publisher ogimage
        if (empty($fm->image)) {
            return is_string($publisherImage) ? $publisherImage : '';
        }

        $image = $fm->image;

        // Check if image already has an origin (scheme://host:port)
        if (! preg_match('/^[a-z]+:\/\/[^\/]+/i', $image)) {
            // No origin found, prepend publisher url (without trailing path)
            $publisherOrigin = is_string($publisherUrl)
                ? preg_replace('/^(https?:\/\/[^\/]+).*$/', '$1', $publisherUrl)
                : '';

            return $publisherOrigin.$image;
        }

        return $image;
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    private function getPublisher(): array
    {
        $publisher = [];

        foreach (Config::array('prezet.publisher') as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (is_string($value)) {
                $publisher[$key] = $value;
            } elseif (is_array($value)) {
                $publisher[$key] = $this->normalizeStringMap($value);
            }
        }

        return $publisher;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeStringMap(mixed $items): array
    {
        $strings = [];

        if (! is_array($items)) {
            return $strings;
        }

        foreach ($items as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $strings[$key] = $value;
            }
        }

        return $strings;
    }
}
