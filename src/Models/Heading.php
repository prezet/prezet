<?php

namespace Prezet\Prezet\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Prezet\Prezet\Prezet;

/**
 * @property int $id
 * @property int $level
 * @property int $document_id
 * @property string $text
 * @property string $section
 */
class Heading extends Model
{
    /**
     * Get the database connection name for the model.
     * Uses 'prezet' for SQLite strategy, or configured connection for shared strategy.
     */
    public function getConnectionName(): ?string
    {
        return Prezet::getDatabaseConnection();
    }

    /**
     * Get the table associated with the model.
     * Uses 'headings' for SQLite strategy, or prefixed table for shared strategy.
     */
    public function getTable(): string
    {
        return Prezet::getTableName('headings');
    }

    protected $guarded = [];

    public $timestamps = false;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Document, Heading>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        $fragment = Str::slug($this->text);
        $fragment = $this->section ? "#content-{$fragment}" : '';

        return new Attribute(
            get: fn () => route('prezet.show', $this->document?->slug, false).$fragment
        );
    }
}
