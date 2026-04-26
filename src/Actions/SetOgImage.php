<?php

namespace Prezet\Prezet\Actions;

use Illuminate\Support\Facades\Storage;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use Prezet\Prezet\Exceptions\FrontmatterMissingException;
use Prezet\Prezet\Models\Document;
use Prezet\Prezet\Prezet;

class SetOgImage
{
    public function handle(Document $doc, string $imgPath): void
    {
        $md = Prezet::getMarkdown($doc->filepath);
        $content = Prezet::parseMarkdown($md);

        if (! $content instanceof RenderedContentWithFrontMatter) {
            throw new FrontmatterMissingException($doc->filepath);
        }
        $fm = $this->normalizeFrontmatter($content->getFrontMatter());
        if (! $fm) {
            throw new FrontmatterMissingException($doc->filepath);
        }

        $fm['image'] = $imgPath;
        $newMd = Prezet::setFrontmatter($md, $fm);

        $storage = Storage::disk(Prezet::getPrezetDisk());
        $storage->put($doc->filepath, $newMd);
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
