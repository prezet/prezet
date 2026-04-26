<?php

namespace Prezet\Prezet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $name
 * */
class Tag extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'prezet';

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
     * @return BelongsToMany<Document, $this, Pivot, 'pivot'>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_tags');
    }
}
