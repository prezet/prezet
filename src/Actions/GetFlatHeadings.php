<?php

namespace Prezet\Prezet\Actions;

use DOMDocument;
use DOMElement;
use DOMXPath;

class GetFlatHeadings
{
    /**
     * @return array<int, array<string, int|string>>
     */
    public function handle(string $html): array
    {
        $dom = new DOMDocument;
        $html = '<?xml encoding="UTF-8"><div>'.$html.'</div>'; // Wrapper to handle h2 as first element
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        return $this->extractHeadings($dom);
    }

    /**
     * @return array<int, array<string, string|int>>
     */
    private function extractHeadings(DOMDocument $dom): array
    {
        $xpath = new DOMXPath($dom);
        $headingElements = $xpath->query('//h2 | //h3');
        $headings = [];

        if (! $headingElements) {
            return $headings;
        }

        $currentSection = '';
        foreach ($headingElements as $headingElement) {
            if (! $headingElement instanceof DOMElement) {
                continue;
            }

            $headingText = $this->cleanHeadingText($headingElement->textContent);

            $headingLevel = (int) substr(strtolower($headingElement->tagName), 1);
            if ($headingLevel === 2) {
                $currentSection = $headingText;
            }

            $headings[] = [
                'text' => $headingText,
                'level' => $headingLevel,
                'section' => $currentSection,
            ];
        }

        return $headings;
    }

    private function cleanHeadingText(string $text): string
    {
        return trim((string) preg_replace('/^\s*#*\s*|\s*#*\s*$/', '', $text));
    }
}
