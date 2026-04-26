---
name: prezet-development
description: ACTIVATE when the user works with Prezet markdown content in Laravel, including installation, indexing, frontmatter, markdown parsing, content routes, templates, images, SEO, JSON-LD, sitemap generation, OG images, or Prezet action customization.
license: MIT
metadata:
  author: prezet
---

# Prezet Development

Prezet is a Laravel markdown publishing engine. It handles markdown parsing, typed frontmatter, a SQLite content index, image optimization, SEO metadata, JSON-LD, sitemaps, and OG image generation. Frontend templates own routes, controllers, Blade views, CSS, and sample content.

## Documentation

Use the Prezet documentation at `https://prezet.com` for detailed patterns. Key pages include:

- Installation: `https://prezet.com/installation`
- Configuration: `https://prezet.com/configuration`
- Content structure: `https://prezet.com/content`
- SQLite index: `https://prezet.com/index`
- Frontmatter: `https://prezet.com/features/frontmatter`
- Blade in markdown: `https://prezet.com/features/blade`
- Images: `https://prezet.com/features/images`
- SEO: `https://prezet.com/features/seo`
- JSON-LD: `https://prezet.com/features/jsonld`
- OG images: `https://prezet.com/features/ogimage`
- Self-healing links: `https://prezet.com/features/self-healing-links`
- Custom actions: `https://prezet.com/customize/actions`
- Custom frontmatter: `https://prezet.com/customize/frontmatter`
- Templates: `https://prezet.com/customize/templates`

Check the installed package version before using legacy docs or namespaces.

## Setup

- Framework package: `composer require prezet/prezet`
- Install Prezet: `php artisan prezet:install`
- Frontend templates: install `prezet/blog-template` or `prezet/docs-template` separately, then run that template's installer. Template installers copy routes, controllers, views, CSS, config, and sample content into the user's Laravel app.
- Refresh index after adding files, deleting files, changing slugs, changing frontmatter, or changing tags: `php artisan prezet:index`
- Rebuild from scratch for deploys, CI, missing SQLite files, or upgrades: `php artisan prezet:index --fresh`
- Generate OG images: `php artisan prezet:ogimage {slug}` or `php artisan prezet:ogimage --all`

## Content Structure

Prezet reads from the disk configured at `config('prezet.filesystem.disk')`, usually the `prezet` disk rooted at the application's `prezet/` directory.

- Markdown files live under `content/`.
- Referenced images live under `images/`.
- Generated social images usually live under `images/ogimages/`.
- `SUMMARY.md` defines navigation order for templates that use it.

Default slugs are generated from file paths unless `config('prezet.slug.source')` is `title`. A `slug` in frontmatter always wins.

## Frontmatter

Every markdown document needs valid YAML frontmatter. Default fields:

- Required: `title`, `excerpt` or `description`, `date`
- Optional: `category`, `image`, `contentType`, `draft`, `author`, `slug`, `key`, `tags`
- Defaults: `contentType: article`, `draft: false`, `tags: []`
- Valid `contentType` values: `article`, `video`, `category`

Example:

```yaml
---
title: My Post
date: 2026-01-15
excerpt: Short description for lists and SEO.
category: Guides
image: /prezet/img/ogimages/my-post.webp
author: jane_doe
tags: [laravel, markdown]
---
```

Customize frontmatter by extending `Prezet\Prezet\Data\FrontmatterData` and binding it in a service provider:

```php
use App\Data\CustomFrontmatterData;
use Prezet\Prezet\Data\FrontmatterData;

$this->app->bind(FrontmatterData::class, CustomFrontmatterData::class);
```

## Index and Queries

The index uses the `prezet` database connection and stores documents, tags, document-tag pivots, and headings. Query indexed content with `Prezet\Prezet\Models\Document`.

```php
use Prezet\Prezet\Models\Document;

$documents = Document::query()
    ->with('tags')
    ->where('draft', false)
    ->where('category', 'guides')
    ->orderByDesc('created_at')
    ->paginate();
```

Use `headings()` for table-of-contents data and heading search. Prezet adds the document title as a level 1 heading and indexes markdown headings when content changes.

## Routes and Templates

The `prezet/prezet` package is the backend engine. Template packages are starter frontends. When a template installer runs, it publishes or copies its files into the user's application directory; after installation, those files belong to the app and should be edited there.

Prezet templates publish application-owned files, commonly:

- `routes/prezet.php`
- `app/Http/Controllers/Prezet/*`
- `resources/views/prezet/*`
- `resources/views/components/prezet/*`
- `resources/css/prezet.css`
- `vite.config.js` and related frontend build files
- sample markdown content under `prezet/`

Keep display changes in published views and CSS. Put filtering, sorting, and response data in published controllers. The framework package should remain the backend engine.

The image route must be named `prezet.image` because markdown image URLs are generated from that route:

```php
Route::get('prezet/img/{path}', ImageController::class)
    ->name('prezet.image')
    ->where('path', '.*');
```

Document pages commonly use a `prezet.show` route name; self-healing redirects also target this route.

## Markdown Features

- `MarkdownBladeExtension` renders fenced code blocks with the `+parse` info word as Blade.
- `MarkdownImageExtension` turns local markdown images into responsive `srcset` images.
- `PhikiExtension` provides server-side syntax highlighting.
- Configure CommonMark extensions and extension options in `config/prezet.php`.

Blade-in-markdown example:

````markdown
```html +parse
<x-alert type="warning" message="Check your APP_URL before generating OG images." />
```
````

## Images, SEO, and Structured Data

- Configure image `widths`, `sizes`, and `zoomable` in `config/prezet.php`.
- SEO tags are typically rendered from template views with `@seo([...])`.
- JSON-LD uses frontmatter plus `config('prezet.authors')` and `config('prezet.publisher')`.
- The sitemap is regenerated by `php artisan prezet:index` and written as `public/prezet_sitemap.xml`.
- Set `APP_URL` or `PREZET_SITEMAP_ORIGIN` correctly before generating production sitemaps.

## Self-Healing Links

Enable stable redirects when files or titles change:

```php
'slug' => [
    'source' => 'filepath',
    'keyed' => true,
],
```

Then run `php artisan prezet:index`. Prezet adds a stable `key` to frontmatter and appends it to generated slugs. If the slug changes later, Prezet can redirect by key.

## Customization Points

Prezet resolves actions, data classes, and models from Laravel's container. Override behavior by binding a replacement class in a service provider.

Common actions:

- `ParseMarkdown` - Markdown to HTML
- `GetMarkdown` - Raw markdown from storage
- `UpdateIndex` - SQLite index updates
- `GetImage` - Optimized image responses
- `GenerateOgImage` - Social image generation
- `GetLinkedData` - JSON-LD output

Example:

```php
use App\Actions\CustomGetLinkedData;
use Prezet\Prezet\Actions\GetLinkedData;

$this->app->bind(GetLinkedData::class, CustomGetLinkedData::class);
```

Prefer overriding the narrowest action or template file that owns the behavior. Run `php artisan prezet:index` after changes that affect indexed metadata.
