<?php

namespace Prezet\Prezet\Extensions;

use Illuminate\Support\Str;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use Prezet\Prezet\Exceptions\InvalidConfigurationException;

class MarkdownImageExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, [$this, 'onDocumentParsed']);
    }

    public function onDocumentParsed(DocumentParsedEvent $event): void
    {
        $walker = $event->getDocument()->walker();

        while ($event = $walker->next()) {
            $node = $event->getNode();

            if (! $node instanceof Image || ! $event->isEntering()) {
                continue;
            }

            $this->processImage($node);
        }
    }

    private function processImage(Image $node): void
    {
        // Skip external images
        if (Str::startsWith($node->getUrl(), ['http://', 'https://'])) {
            return;
        }

        $originalUrl = $node->getUrl();
        $this->configureImageAttributes($node, $originalUrl);
        $this->wrapImageInFigure($node);
    }

    private function configureImageAttributes(Image $node, string $originalUrl): void
    {
        $node->setUrl(route('prezet.image', $originalUrl, false));

        $srcset = $this->generateSrcset($originalUrl);
        $node->data->set('attributes', [
            'x-zoomable' => config('prezet.image.zoomable', true),
            'srcset' => $srcset,
            'sizes' => config('prezet.image.sizes'),
            'loading' => 'lazy',
            'decoding' => 'async',
            'fetchpriority' => 'auto',
            'class' => 'prezet-image',
        ]);

        // If the viewport is less than 1024px, the image will take up 92% of the viewport width. Otherwise the image will be 768px wide.
        // https://coderpad.io/blog/development/the-definitive-guide-to-responsive-images-on-the-web/#:~:text=Adding%20the%20sizes%20attribute

    }

    private function wrapImageInFigure(Image $node): void
    {
        $paragraph = $node->parent();

        // Create figure elements
        $openFigure = $this->createOpenFigureElement();
        $captionBlock = $this->createCaptionElement($node);
        $closeFigure = $this->createCloseFigureElement();

        // Remove the image from its paragraph
        $node->detach();

        // Insert the new structure
        $paragraph->insertBefore($openFigure);
        $paragraph->insertBefore($node);

        if ($captionBlock) {
            $paragraph->insertBefore($captionBlock);
        }

        $paragraph->insertBefore($closeFigure);

        // Remove the now-empty paragraph
        $paragraph->detach();
    }

    private function createOpenFigureElement(): HtmlBlock
    {
        $openFigure = new HtmlBlock(HtmlBlock::TYPE_6_BLOCK_ELEMENT);
        $openFigure->setLiteral('<figure class="prezet-figure">');

        return $openFigure;
    }

    private function createCaptionElement(Image $node): ?HtmlBlock
    {
        $captionText = $this->extractAltText($node);

        if ($captionText === '') {
            return null;
        }

        // Escape to avoid XSS via markdown alt text
        $escaped = e($captionText, false);
        $captionBlock = new HtmlBlock(HtmlBlock::TYPE_6_BLOCK_ELEMENT);
        $captionBlock->setLiteral('<figcaption class="prezet-figcaption">'.$escaped.'</figcaption>');

        return $captionBlock;
    }

    private function createCloseFigureElement(): HtmlBlock
    {
        $closeFigure = new HtmlBlock(HtmlBlock::TYPE_6_BLOCK_ELEMENT);
        $closeFigure->setLiteral('</figure>');

        return $closeFigure;
    }

    private function generateSrcset(string $url): string
    {
        $srcset = [];
        $allowedSizes = config('prezet.image.widths');

        if (! is_array($allowedSizes)) {
            throw new InvalidConfigurationException('prezet.image.widths', $allowedSizes, 'is not a valid array');
        }

        foreach ($allowedSizes as $size) {
            $srcset[] = $this->generateImageUrl($url, $size).' '.$size.'w';
        }

        return implode(', ', $srcset);
    }

    private function generateImageUrl(string $url, int $width): string
    {
        $filename = pathinfo($url, PATHINFO_FILENAME).'-'.$width.'w.'.pathinfo($url, PATHINFO_EXTENSION);

        return route('prezet.image', $filename, false);
    }

    private function extractAltText(Image $node): string
    {
        $buffer = '';

        /** @var Node $child */
        foreach ($node->children() as $child) {
            if ($child instanceof Text) {
                $buffer .= $child->getLiteral();
            } else {
                // Recursively extract text from nested nodes
                $buffer .= $this->extractTextFromNode($child);
            }
        }

        return trim($buffer);
    }

    private function extractTextFromNode(Node $node): string
    {
        $buffer = '';

        foreach ($node->children() as $child) {
            if ($child instanceof Text) {
                $buffer .= $child->getLiteral();
            } else {
                $buffer .= $this->extractTextFromNode($child);
            }
        }

        return $buffer;
    }
}
