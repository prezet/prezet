<?php

namespace BenBjurstrom\Prezet\Data;

use Carbon\Carbon;
use WendellAdriel\ValidatedDTO\Attributes\Rules;
use WendellAdriel\ValidatedDTO\Casting\CarbonCast;
use WendellAdriel\ValidatedDTO\Casting\DTOCast;
use WendellAdriel\ValidatedDTO\Concerns\EmptyRules;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

class DocumentData extends ValidatedDTO
{
    use EmptyRules;

    #[Rules(['required', 'string'])]
    public string $slug;

    #[Rules(['required', 'array'])]
    public FrontmatterData $frontmatter;

    #[Rules(['required'])]
    public Carbon $createdAt;

    #[Rules(['required'])]
    public Carbon $updatedAt;

    /**
     * @return array<string, array<int, null>|false>
     */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function mapData(): array
    {
        return [
            'created_at' => 'createdAt',
            'updated_at' => 'updatedAt',
        ];
    }

    /**
     * @return array<string, CarbonCast|DTOCast>
     */
    protected function casts(): array
    {
        return [
            'frontmatter' => new DTOCast(FrontmatterData::class),
            'createdAt' => new CarbonCast,
            'updatedAt' => new CarbonCast,
        ];
    }
}
