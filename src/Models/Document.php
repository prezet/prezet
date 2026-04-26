<?php

namespace Prezet\Prezet\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Prezet\Prezet\Data\FrontmatterData;
use Prezet\Prezet\Database\Factories\DocumentFactory;

/**
 * @property string $key
 * @property string $slug
 * @property string $filepath
 * @property string|null $category
 * @property string $hash
 * @property bool $draft
 * @property FrontmatterData $frontmatter
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * */
class Document extends Model
{
    /**
     * @use HasFactory<DocumentFactory>
     */
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'prezet';

    protected $guarded = [];

    /**
     * Create a new factory instance for the model.
     *
     * @return DocumentFactory<Document>
     */
    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string|false>
     */
    protected function casts(): array
    {
        return [
            'draft' => 'boolean',
            'frontmatter' => get_class(app(FrontmatterData::class)),
        ];
    }

    /**
     * @return BelongsToMany<Tag, $this, Pivot, 'pivot'>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'document_tags');
    }

    /**
     * @return HasMany<Heading, $this>
     */
    public function headings(): HasMany
    {
        return $this->hasMany(Heading::class);
    }
}
