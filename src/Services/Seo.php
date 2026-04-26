<?php

declare(strict_types=1);

namespace Prezet\Prezet\Services;

use Closure;
use Illuminate\Support\Str;

/**
 * @method $this title(string $title = null, ...$args) Set the title.
 * @method $this description(string $description = null, ...$args) Set the description.
 * @method $this keywords(string $keywords = null, ...$args) Set the keywords.
 * @method $this url(string $url = null, ...$args) Set the canonical URL.
 * @method $this site(string $site = null, ...$args) Set the site name.
 * @method $this image(string $url = null, ...$args) Set the cover image.
 * @method $this type(string $type = null, ...$args) Set the page type.
 * @method $this locale(string $locale = null, ...$args) Set the page locale.
 * @method $this twitterCreator(string $username = null, ...$args) Set the Twitter author.
 * @method $this twitterSite(string $username = null, ...$args) Set the Twitter author.
 * @method $this twitterTitle(string $title = null, ...$args) Set the Twitter title.
 * @method $this twitterDescription(string $description = null, ...$args) Set the Twitter description.
 * @method $this twitterImage(string $url = null, ...$args) Set the Twitter cover image.
 */
class Seo
{
    /**
     * Value modifiers.
     *
     * @var array<string, Closure>
     */
    protected array $modifiers = [];

    /**
     * Default values.
     *
     * @var array<string, string|Closure>
     */
    protected array $defaults = [];

    /**
     * User-configured values.
     *
     * @var array<string, string|Closure|null>
     */
    protected array $values = [];

    /**
     * Metadata for additional features.
     *
     * @var array<string, mixed>
     */
    protected array $meta = [];

    /**
     * Extra head tags.
     *
     * @var array<string, string>
     */
    protected array $tags = [];

    /**
     * Get all used values.
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        $values = [];

        foreach ($this->getKeys() as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * Get a list of used keys.
     *
     * @return array<int, string>
     */
    protected function getKeys(): array
    {
        $keys = collect([
            'site', 'title', 'image', 'description', 'url', 'type', 'locale',
            'twitter.creator', 'twitter.site', 'twitter.title', 'twitter.image', 'twitter.description',
        ])
            ->merge(array_keys($this->defaults))
            ->merge(array_keys($this->values))
            ->unique();

        $strings = [];

        foreach ($keys as $key) {
            $strings[] = $key;
        }

        return $strings;
    }

    /**
     * Get a modified value.
     */
    protected function modify(string $key): ?string
    {
        if (isset($this->modifiers[$key])) {
            $value = $this->values[$key];
            if ($value instanceof Closure) {
                $value = $value();
            }
            if (is_string($value) || is_null($value)) {
                return $this->modifiers[$key]($value);
            }

            return null;
        }

        $value = $this->values[$key];
        if ($value instanceof Closure) {
            $value = $value();
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Set one or more values.
     *
     * @param  string|array<string, string|Closure|null>  $key
     * @return string|array<string, string|null>|null
     */
    public function set(string|array $key, string|Closure|null $value = null): string|array|null
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }

            $values = [];

            foreach (array_keys($key) as $k) {
                $values[$k] = $this->get($k);
            }

            return $values;
        }

        $this->values[$key] = $value;

        return $this->get($key);
    }

    /**
     * Resolve a value.
     */
    public function get(string $key): ?string
    {
        if (isset($this->values[$key])) {
            return $this->modify($key);
        }

        if (isset($this->defaults[$key])) {
            $value = $this->defaults[$key];
            if ($value instanceof Closure) {
                $value = $value();
            }

            return is_string($value) ? $value : null;
        }

        if (Str::contains($key, '.')) {
            return $this->get(Str::after($key, '.'));
        }

        return null;
    }

    /**
     * Get a value without modifications.
     */
    public function raw(string $key): ?string
    {
        if (isset($this->values[$key])) {
            $value = $this->values[$key];
            if ($value instanceof Closure) {
                $value = $value();
            }

            return is_string($value) ? $value : null;
        }

        if (isset($this->defaults[$key])) {
            $value = $this->defaults[$key];
            if ($value instanceof Closure) {
                $value = $value();
            }

            return is_string($value) ? $value : null;
        }

        if (Str::contains($key, '.')) {
            return $this->get(Str::after($key, '.'));
        }

        return null;
    }

    /**
     * Append canonical URL tags to the document head.
     */
    public function withUrl(): static
    {
        $this->url(request()->url());

        return $this;
    }

    /**
     * Get all extra head tags.
     *
     * @return array<string, string>
     */
    public function tags(): array
    {
        return $this->tags;
    }

    /**
     * Has a specific tag been set?
     */
    public function hasRawTag(string $key): bool
    {
        return isset($this->tags[$key]);
    }

    /**
     * Has a specific meta tag been set?
     */
    public function hasTag(string $property): bool
    {
        return $this->hasRawTag("meta.{$property}");
    }

    /**
     * Add a head tag.
     */
    public function rawTag(string $key, ?string $tag = null): static
    {
        $tag ??= $key;

        $this->tags[$key] = $tag;

        return $this;
    }

    /**
     * Add a meta tag.
     */
    public function tag(string $property, string $content): static
    {
        $content = e($content);

        $this->rawTag("meta.{$property}", "<meta property=\"{$property}\" content=\"{$content}\">");

        return $this;
    }

    /**
     * Get or set metadata.
     *
     * @param  string|array<string, string|array<mixed>|null>  $key  The key or key-value pair being set.
     * @param  string|array<mixed>|null  $value  The value (if a single key is provided).
     * @return $this|string|null
     */
    public function meta(string|array $key, string|array|null $value = null): self|string|null
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->meta($k, $v);
            }

            return $this;
        }

        if ($value === null) {
            $result = data_get($this->meta, $key);

            return is_string($result) || is_null($result) ? $result : null;
        }

        $this->setMetaValue($key, $value);

        return $this;
    }

    /**
     * @param  string|array<mixed>  $value
     */
    private function setMetaValue(string $key, string|array $value): void
    {
        $this->meta = $this->withMetaValue($this->meta, explode('.', $key), $value);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<int, string>  $segments
     * @param  string|array<mixed>  $value
     * @return array<string, mixed>
     */
    private function withMetaValue(array $meta, array $segments, string|array $value): array
    {
        $segment = array_shift($segments);

        if (! is_string($segment) || $segment === '') {
            return $meta;
        }

        if ($segments === []) {
            $meta[$segment] = $value;

            return $meta;
        }

        $child = $meta[$segment] ?? [];

        if (! is_array($child)) {
            $child = [];
        }

        $meta[$segment] = $this->withMetaValue($child, $segments, $value);

        return $meta;
    }

    /**
     * Render blade directive.
     *
     * This is the only method whose output (returned values) is wrapped in e()
     * as these values are used in the meta.blade.php file via @seo calls.
     *
     * @param  mixed  ...$args  The arguments passed to the directive
     * @return array<string, string|null>|string|null
     */
    public function render(mixed ...$args): array|string|null
    {
        // Two arguments indicate that we're setting a value, e.g. `@seo('title', 'foo')
        if (count($args) === 2 && is_string($args[0])) {
            if (! is_string($args[1]) && ! $args[1] instanceof Closure && ! is_null($args[1])) {
                return null;
            }

            $result = $this->set($args[0], $args[1]);

            return is_string($result) || is_null($result) ? e($result) : $result;
        }

        // An array means we don't return anything, e.g. `@seo(['title' => 'foo'])
        if (count($args) === 1 && is_array($args[0])) {
            foreach ($args[0] as $type => $value) {
                if (is_string($type)) {
                    if (! is_string($value) && ! $value instanceof Closure && ! is_null($value)) {
                        continue;
                    }

                    $this->set($type, $value);
                }
            }

            return null;
        }

        // A single value means we fetch a value, e.g. `@seo('title')
        if (count($args) === 1 && is_string($args[0])) {
            return e($this->get($args[0]));
        }

        return null;
    }

    /**
     * Handle magic method calls.
     *
     * @param  string  $name  The method name
     * @param  array<int|string, mixed>  $arguments  The method arguments
     * @return string|array<string, string|null>|null|static
     */
    public function __call(string $name, array $arguments): string|array|null|static
    {
        $key = Str::snake($name, '.');

        if (isset($arguments['default']) && (is_string($arguments['default']) || $arguments['default'] instanceof Closure)) {
            $this->defaults[$key] = $arguments['default'];
        }

        if (isset($arguments['modifier']) && $arguments['modifier'] instanceof Closure) {
            $this->modifiers[$key] = $arguments['modifier'];
        }

        // modify: ... is an alias for modifier: ...
        if (isset($arguments['modify']) && $arguments['modify'] instanceof Closure) {
            $this->modifiers[$key] = $arguments['modify'];
        }

        if (array_key_exists(0, $arguments) && (is_string($arguments[0]) || $arguments[0] instanceof Closure || is_null($arguments[0]))) {
            $this->set($key, $arguments[0]);
        }

        if (array_key_exists(0, $arguments) || isset($arguments['default']) || isset($arguments['modifier']) || isset($arguments['modify'])) {
            return $this;
        }

        return $this->get($key);
    }

    /**
     * Handle magic get.
     */
    public function __get(string $key): ?string
    {
        return $this->get(Str::snake($key, '.'));
    }

    /**
     * Handle magic set.
     */
    public function __set(string $key, string $value): void
    {
        $this->set(Str::snake($key, '.'), $value);
    }
}
