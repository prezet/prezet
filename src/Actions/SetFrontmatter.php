<?php

namespace Prezet\Prezet\Actions;

use Carbon\Carbon;

class SetFrontmatter
{
    /**
     * @param  array<string, mixed>  $fm
     */
    public function handle(string $md, array $fm): string
    {
        // Remove existing frontmatter if present
        $pattern = '/^---\s*\n.*?\n---\s*\n/s';
        $cleanMarkdown = preg_replace($pattern, '', $md);

        // Add new frontmatter
        return $this->addFrontmatter($fm).$cleanMarkdown;
    }

    /**
     * @param  array<string, mixed>  $fm
     */
    private function addFrontmatter(array $fm): string
    {
        $yaml = "---\n";
        foreach ($fm as $key => $value) {
            $yaml .= $key.': '.$this->formatValue($value)."\n";
        }
        $yaml .= "---\n\n";

        return $yaml;
    }

    private function formatValue(mixed $value): string
    {
        if (is_int($value) && $value > 946713600) {
            return (new Carbon($value))->toDateString();
        } elseif (is_array($value)) {
            return '['.implode(', ', array_map([$this, 'formatValue'], $value)).']';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return '';
        } elseif (is_string($value) && (strpos($value, ':') !== false || strpos($value, '#') !== false)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        } elseif (is_string($value) || is_int($value) || is_float($value)) {
            return (string) $value;
        } elseif ($value instanceof \Stringable) {
            return (string) $value;
        }

        return '';
    }
}
