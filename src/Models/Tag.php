<?php

namespace Prezet\Prezet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Prezet\Prezet\Prezet;

/**
 * @property string $name
 * */
class Tag extends Model
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
     * Uses 'tags' for SQLite strategy, or prefixed table for shared strategy.
     */
    public function getTable(): string
    {
        return Prezet::getTableName('tags');
    }

    protected $guarded = [];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * @return BelongsToMany<Document>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, Prezet::getTableName('document_tags'));
    }
}
