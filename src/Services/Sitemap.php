<?php

namespace Prezet\Prezet\Services;

use DateTimeInterface;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Response;
use Stringable;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class Sitemap implements Renderable, Responsable
{
    public const CHANGE_FREQUENCY_ALWAYS = 'always';

    public const CHANGE_FREQUENCY_HOURLY = 'hourly';

    public const CHANGE_FREQUENCY_DAILY = 'daily';

    public const CHANGE_FREQUENCY_WEEKLY = 'weekly';

    public const CHANGE_FREQUENCY_MONTHLY = 'monthly';

    public const CHANGE_FREQUENCY_YEARLY = 'yearly';

    public const CHANGE_FREQUENCY_NEVER = 'never';

    /**
     * @var array<int, SitemapUrl>
     */
    private array $urls = [];

    public static function create(): static
    {
        return new self;
    }

    public static function url(string|Stringable $url): SitemapUrl
    {
        return new SitemapUrl((string) $url);
    }

    /**
     * @param  string|Stringable|SitemapUrl|iterable<int, string|Stringable|SitemapUrl>  $url
     */
    public function add(string|Stringable|SitemapUrl|iterable $url): static
    {
        if (is_iterable($url)) {
            foreach ($url as $item) {
                $this->add($item);
            }

            return $this;
        }

        if (! $url instanceof SitemapUrl) {
            $url = static::url($url);
        }

        if ($url->isEmpty() || $this->hasUrl($url->location())) {
            return $this;
        }

        $this->urls[] = $url;

        return $this;
    }

    /**
     * @return array<int, SitemapUrl>
     */
    public function urls(): array
    {
        return $this->urls;
    }

    public function hasUrl(string $url): bool
    {
        return $this->getUrl($url) instanceof SitemapUrl;
    }

    public function getUrl(string $url): ?SitemapUrl
    {
        foreach ($this->urls as $sitemapUrl) {
            if ($sitemapUrl->location() === $url) {
                return $sitemapUrl;
            }
        }

        return null;
    }

    public function render(): string
    {
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->urls as $url) {
            $xml[] = '    <url>';
            $xml[] = '        <loc>'.$this->escape(url($url->location())).'</loc>';

            if ($url->lastModificationDate() instanceof DateTimeInterface) {
                $xml[] = '        <lastmod>'.$url->lastModificationDate()->format(DATE_ATOM).'</lastmod>';
            }

            if ($url->changeFrequency() !== null) {
                $xml[] = '        <changefreq>'.$this->escape($url->changeFrequency()).'</changefreq>';
            }

            if ($url->priority() !== null) {
                $xml[] = '        <priority>'.number_format($url->priority(), 1).'</priority>';
            }

            $xml[] = '    </url>';
        }

        $xml[] = '</urlset>';

        return implode(PHP_EOL, $xml).PHP_EOL;
    }

    public function writeToFile(string $path): static
    {
        file_put_contents($path, $this->render());

        return $this;
    }

    public function toResponse($request): SymfonyResponse
    {
        return Response::make($this->render(), 200, [
            'Content-Type' => 'text/xml',
        ]);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}

class SitemapUrl
{
    private string $location;

    private ?DateTimeInterface $lastModificationDate = null;

    private ?string $changeFrequency = null;

    private ?float $priority = null;

    public function __construct(string $location)
    {
        $this->location = $location;
    }

    public function setUrl(string|Stringable $url): static
    {
        $this->location = (string) $url;

        return $this;
    }

    public function setLastModificationDate(DateTimeInterface $lastModificationDate): static
    {
        $this->lastModificationDate = $lastModificationDate;

        return $this;
    }

    public function setChangeFrequency(string $changeFrequency): static
    {
        $this->changeFrequency = $changeFrequency;

        return $this;
    }

    public function setPriority(float $priority): static
    {
        $this->priority = max(0, min($priority, 1));

        return $this;
    }

    public function location(): string
    {
        return $this->location;
    }

    public function lastModificationDate(): ?DateTimeInterface
    {
        return $this->lastModificationDate;
    }

    public function changeFrequency(): ?string
    {
        return $this->changeFrequency;
    }

    public function priority(): ?float
    {
        return $this->priority;
    }

    public function isEmpty(): bool
    {
        return trim($this->location) === '';
    }
}
