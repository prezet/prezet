<?php

namespace Prezet\Prezet;

use Illuminate\Support\Collection;
use League\CommonMark\Output\RenderedContentInterface;
use Prezet\Prezet\Actions\CreateIndex;
use Prezet\Prezet\Actions\GenerateOgImage;
use Prezet\Prezet\Actions\GetDocumentDataFromFile;
use Prezet\Prezet\Actions\GetDocumentDataFromFiles;
use Prezet\Prezet\Actions\GetDocumentModelFromSlug;
use Prezet\Prezet\Actions\GetFlatHeadings;
use Prezet\Prezet\Actions\GetHeadings;
use Prezet\Prezet\Actions\GetImage;
use Prezet\Prezet\Actions\GetLinkedData;
use Prezet\Prezet\Actions\GetMarkdown;
use Prezet\Prezet\Actions\GetPrezetDisk;
use Prezet\Prezet\Actions\GetSlugFromFilepath;
use Prezet\Prezet\Actions\GetSummary;
use Prezet\Prezet\Actions\ParseFrontmatter;
use Prezet\Prezet\Actions\ParseMarkdown;
use Prezet\Prezet\Actions\SearchHeadings;
use Prezet\Prezet\Actions\SetFrontmatter;
use Prezet\Prezet\Actions\SetKey;
use Prezet\Prezet\Actions\SetOgImage;
use Prezet\Prezet\Actions\UpdateIndex;
use Prezet\Prezet\Actions\UpdateSitemap;
use Prezet\Prezet\Data\DocumentData;
use Prezet\Prezet\Data\FrontmatterData;
use Prezet\Prezet\Data\HeadingData;
use Prezet\Prezet\Models\Document;

class Prezet
{
    public static function createIndex(): void
    {
        app(CreateIndex::class)->handle();
    }

    public static function generateOgImage(string $slug): string
    {
        return app(GenerateOgImage::class)->handle($slug);
    }

    /**
     * @return Collection<int,DocumentData>
     */
    public static function getDocumentDataFromFiles(): Collection
    {
        return app(GetDocumentDataFromFiles::class)->handle();
    }

    public static function getDocumentDataFromFile(string $filepath): DocumentData
    {
        return app(GetDocumentDataFromFile::class)->handle($filepath);
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    public static function getFlatHeadings(string $html): array
    {
        return app(GetFlatHeadings::class)->handle($html);
    }

    /**
     * @return array<int, array<string, array<int, array<string, string>>|string>>
     */
    public static function getHeadings(string $html): array
    {
        return app(GetHeadings::class)->handle($html);
    }

    public static function getImage(string $path): string
    {
        return app(GetImage::class)->handle($path);
    }

    /**
     * @return array<string, string|array<string, string>|array<string, string|array<string, string>>>
     */
    public static function getLinkedData(DocumentData $document): array
    {
        return app(GetLinkedData::class)->handle($document);
    }

    public static function getMarkdown(string $filePath): string
    {
        return app(GetMarkdown::class)->handle($filePath);
    }

    public static function getPrezetDisk(): string
    {
        return app(GetPrezetDisk::class)->handle();
    }

    /**
     * @return Collection<int, array{title: string, links: array<int, array<string, string>>}>
     */
    public static function getSummary(?string $filepath = null): Collection
    {
        return app(GetSummary::class)->handle($filepath);
    }

    public static function parseFrontmatter(string $content, string $filePath): FrontmatterData
    {
        return app(ParseFrontmatter::class)->handle($content, $filePath);
    }

    public static function parseMarkdown(string $md): RenderedContentInterface
    {
        return app(ParseMarkdown::class)->handle($md);
    }

    /**
     * @param  array<string, mixed>  $fm
     */
    public static function setFrontmatter(string $md, array $fm): string
    {
        return app(SetFrontmatter::class)->handle($md, $fm);
    }

    public static function setOgImage(Document $doc, string $imgPath): void
    {
        app(SetOgImage::class)->handle($doc, $imgPath);
    }

    public static function updateIndex(): void
    {
        app(UpdateIndex::class)->handle();
    }

    public static function updateSitemap(): void
    {
        app(UpdateSitemap::class)->handle();
    }

    /**
     * @return Collection<int, HeadingData>
     */
    public static function searchHeadings(string $query): Collection
    {
        return app(SearchHeadings::class)->handle($query);
    }

    public static function setKey(string $filepath, string $key): void
    {
        app(SetKey::class)->handle($filepath, $key);
    }

    public static function getSlugFromFilepath(string $filepath): string
    {
        return app(GetSlugFromFilepath::class)->handle($filepath);
    }

    public static function getDocumentModelFromSlug(string $slug): Document
    {
        return app(GetDocumentModelFromSlug::class)->handle($slug);
    }
}
