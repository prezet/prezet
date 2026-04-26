<?php

namespace Prezet\Prezet\Database\Factories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Prezet\Prezet\Models\Document;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Factory<TModel>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * @return array<string, array<string, array|bool|string>|bool|Carbon|string>
     */
    public function definition(): array
    {
        $createdAt = Carbon::now()->subDays(rand(1, 100));
        $hash = md5(random_bytes(16));
        $key = $key = substr($hash, -8);
        $slug = $this->faker->unique()->slug;

        return [
            'filepath' => 'content/'.$slug.'.md',
            'slug' => $slug,
            'category' => $this->faker->optional()->word,
            'draft' => $this->faker->boolean,
            'hash' => $hash,
            'key' => $key,
            'content_type' => 'article',
            'frontmatter' => [
                'title' => $this->faker->sentence,
                'excerpt' => $this->faker->paragraph,
                'category' => $this->faker->optional()->word,
                'tags' => $this->faker->words(3),
                'image' => $this->faker->optional()->imageUrl(),
                'draft' => $this->faker->boolean,
                'date' => $createdAt->toIso8601String(),
                'key' => $key,
            ],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @return Collection<int, TModel>|TModel
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        if (is_array($attributes)
            && isset($attributes['frontmatter'])
            && is_array($attributes['frontmatter'])) {
            $defaults = $this->definition()['frontmatter'];

            if (is_array($defaults)) {
                $attributes['frontmatter'] = array_merge($defaults, $attributes['frontmatter']);
            }
        }

        return parent::create($attributes, $parent);
    }
}
