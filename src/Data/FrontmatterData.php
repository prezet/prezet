<?php

namespace Prezet\Prezet\Data;

use Carbon\Carbon;
use WendellAdriel\ValidatedDTO\Attributes\Rules;
use WendellAdriel\ValidatedDTO\Casting\CarbonCast;
use WendellAdriel\ValidatedDTO\Concerns\EmptyRules;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

class FrontmatterData extends ValidatedDTO
{
    use EmptyRules;

    #[Rules(['required', 'string'])]
    public string $title;

    #[Rules(['required', 'string'])]
    public string $excerpt;

    #[Rules(['nullable', 'string'])]
    public ?string $category;

    #[Rules(['nullable', 'string'])]
    public ?string $image;

    #[Rules(['bool'])]
    public bool $draft;

    #[Rules(['required'])]
    public Carbon $date;

    #[Rules(['nullable', 'string'])]
    public ?string $author;

    #[Rules(['nullable', 'string'])]
    public ?string $slug;

    #[Rules(['nullable', 'string'])]
    public ?string $key;

    #[Rules(['string', 'in:article,category,video'])]
    public string $contentType;

    /**
     * @var array<int, string> $tags
     */
    #[Rules(['array'])]
    public array $tags;

    /**
     * @return array{tags: array<int, string>, draft: false, contentType: 'article'}
     */
    protected function defaults(): array
    {
        return [
            'tags' => [],
            'draft' => false,
            'contentType' => 'article',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function mapData(): array
    {
        return [
            'description' => 'excerpt',
            'content_type' => 'contentType',
        ];
    }

    /**
     * @return array<string, string|CarbonCast>
     */
    protected function casts(): array
    {
        return [
            'date' => new CarbonCast,
        ];
    }
}
