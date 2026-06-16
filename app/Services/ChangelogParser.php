<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ChangelogParser
{
    private const TYPE_MAP = [
        'Added' => 'feature',
        'Fixed' => 'fix',
        'Changed' => 'improvement',
        'Improved' => 'improvement',
        'Security' => 'security',
        'Removed' => 'removal',
        'Deprecated' => 'deprecation',
    ];

    public function parseLatest(): ?array
    {
        $path = base_path('CHANGELOG.md');

        if (! File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        $blocks = preg_split('/^## /m', $content);

        foreach ($blocks as $block) {
            $block = trim($block);

            if (empty($block) || ! str_starts_with($block, '[') || str_starts_with($block, '[Unreleased]')) {
                continue;
            }

            return $this->parseBlock($block);
        }

        return null;
    }

    private function parseBlock(string $block): array
    {
        $lines = explode("\n", $block);
        $header = array_shift($lines);

        preg_match('/\[([^\]]+)\](?:\s*-\s*(\d{4}-\d{2}-\d{2}))?/', $header, $m);
        $version = $m[1] ?? 'unknown';

        $summary = '';
        $items = [];
        $currentType = null;
        $order = 0;

        foreach ($lines as $line) {
            $line = rtrim($line);

            if (str_starts_with($line, '### ')) {
                $sectionName = trim(substr($line, 4));
                $currentType = self::TYPE_MAP[$sectionName] ?? 'improvement';

                continue;
            }

            if (str_starts_with($line, '- ') && $currentType) {
                $items[] = [
                    'type' => $currentType,
                    'title' => trim(substr($line, 2)),
                    'order' => $order++,
                ];

                continue;
            }

            if ($currentType === null && ! empty($line) && ! str_starts_with($line, '#')) {
                $summary .= ($summary ? ' ' : '').$line;
            }
        }

        return [
            'version' => $version,
            'title' => "In-ventra {$version}",
            'summary' => trim($summary),
            'items' => $items,
        ];
    }
}
