<?php

namespace Prezet\Prezet\Actions;

use Illuminate\Support\Facades\Storage;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use Prezet\Prezet\Exceptions\FrontmatterMissingException;
use Prezet\Prezet\Prezet;

class SetKey
{
    public function handle(string $filepath, string $key): void
    {
        // Get the markdown content
        $md = Prezet::getMarkdown($filepath);
        $content = Prezet::parseMarkdown($md);

        if (! $content instanceof RenderedContentWithFrontMatter) {
            throw new FrontmatterMissingException($filepath);
        }

        $fm = $this->normalizeFrontmatter($content->getFrontMatter());
        if (! $fm) {
            throw new FrontmatterMissingException($filepath);
        }

        // Add the key to frontmatter
        $fm['key'] = $key;
        $newMd = Prezet::setFrontmatter($md, $fm);

        // Save the updated markdown
        $storage = Storage::disk(Prezet::getPrezetDisk());
        $storage->put($filepath, $newMd);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeFrontmatter(mixed $frontmatter): ?array
    {
        if (! is_array($frontmatter)) {
            return null;
        }

        $normalized = [];

        foreach ($frontmatter as $key => $value) {
            if (! is_string($key)) {
                return null;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
