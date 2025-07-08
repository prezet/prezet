<?php

use Prezet\Prezet\Prezet;

it('parses markdown into html', function () {
    $markdown = <<<'MD'
---
title: 'Example Post'
excerpt: 'This is an example post.'
date: '2021-12-24'
---

# Heading 1

This is a paragraph with **bold** and *italic* text.

- List item 1
- List item 2
MD;

    $expectedHtml = <<<'HTML'
<h1>Heading 1</h1>
<p>This is a paragraph with <strong>bold</strong> and <em>italic</em> text.</p>
<ul>
<li>List item 1</li>
<li>List item 2</li>
</ul>

HTML;

    $result = Prezet::parseMarkdown($markdown);

    expect($result->getContent())->toEqual($expectedHtml);
});

it('parses markdown with images html', function () {
    $markdown = <<<'MD'
---
title: 'Example Post'
excerpt: 'This is an example post.'
date: '2021-12-24'
---

# Heading 1

This is a paragraph with **bold** and *italic* text.

![Image Title](/path/to/image.jpg)
MD;

    $expectedHtml = <<<'HTML'
<h1>Heading 1</h1>
<p>This is a paragraph with <strong>bold</strong> and <em>italic</em> text.</p>
<figure class="prezet-figure"><img x-zoomable srcset="/prezet/img/image-480w.jpg 480w, /prezet/img/image-640w.jpg 640w, /prezet/img/image-768w.jpg 768w, /prezet/img/image-960w.jpg 960w, /prezet/img/image-1536w.jpg 1536w" sizes="92vw, (max-width: 1024px) 92vw, 768px" loading="lazy" decoding="async" fetchpriority="auto" class="prezet-image" src="/prezet/img//path/to/image.jpg" alt="Image Title" />
<figcaption class="prezet-figcaption">Image Title</figcaption>
</figure>

HTML;

    $result = Prezet::parseMarkdown($markdown);

    expect($result->getContent())->toEqual($expectedHtml);
});

it('parses markdown with blade components', function () {
    $markdown = <<<'MD'
---
title: 'Example Post'
excerpt: 'This is an example post.'
date: '2021-12-24'
---

# Heading 1

This is a paragraph with **bold** and *italic* text.

```html +parse
<x-prezet::logo />
```
MD;

    $expectedHtml = <<<'HTML'
<h1>Heading 1</h1>
<p>This is a paragraph with <strong>bold</strong> and <em>italic</em> text.</p>
<svg
    class="h-9 w-auto"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 36 36"
>
    <path
        d="m5 31 7-26h5l-7 26H5ZM18 14V9l13 6.5v5L18 27v-5l8-4-8-4Z"
        class="fill-primary-600"
    />
</svg>


HTML;

    $result = Prezet::parseMarkdown($markdown);

    expect($result->getContent())->toEqual($expectedHtml);
});

it('parses markdown with youtube', function () {
    $markdown = <<<'MD'
---
title: 'Example Post'
excerpt: 'This is an example post.'
date: '2021-12-24'
---

# Heading 1

This is a paragraph with **bold** and *italic* text.

```html +parse
<x-prezet::youtube videoid="dt1ado9wJi8" title="Supercharge Markdown with Laravel" date="2023-12-15T12:00:00+08:00">
    Login
</x-prezet::youtube>
```
MD;

    Prezet::parseMarkdown($markdown);
})->throwsNoExceptions();
