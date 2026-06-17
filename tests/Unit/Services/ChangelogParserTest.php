<?php

namespace Tests\Unit\Services;

use App\Services\ChangelogParser;
use ReflectionClass;
use Tests\TestCase;

class ChangelogParserTest extends TestCase
{
    private function parseBlock(string $block): array
    {
        $ref = new ReflectionClass(ChangelogParser::class);
        $method = $ref->getMethod('parseBlock');

        return $method->invoke(new ChangelogParser, $block);
    }

    public function test_parse_latest_returns_array_from_real_changelog(): void
    {
        $result = (new ChangelogParser)->parseLatest();

        $this->assertNotNull($result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('items', $result);
    }

    public function test_parse_latest_skips_unreleased_block(): void
    {
        $result = (new ChangelogParser)->parseLatest();

        $this->assertNotSame('Unreleased', $result['version'] ?? null);
    }

    public function test_parse_block_extracts_version(): void
    {
        $block = "[2.0.0] - 2026-01-01\n\n### Added\n- Something new";

        $result = $this->parseBlock($block);

        $this->assertSame('2.0.0', $result['version']);
        $this->assertSame('In-ventra 2.0.0', $result['title']);
    }

    public function test_parse_block_extracts_summary(): void
    {
        $block = "[1.5.0] - 2026-03-10\n\nFirst major redesign.\n\n### Added\n- Feature A";

        $result = $this->parseBlock($block);

        $this->assertSame('First major redesign.', $result['summary']);
    }

    public function test_parse_block_maps_added_to_feature(): void
    {
        $block = "[1.0.0] - 2026-01-01\n\n### Added\n- New feature";

        $result = $this->parseBlock($block);

        $this->assertSame('feature', $result['items'][0]['type']);
        $this->assertSame('New feature', $result['items'][0]['title']);
    }

    public function test_parse_block_maps_fixed_to_fix(): void
    {
        $block = "[1.0.0] - 2026-01-01\n\n### Fixed\n- Bug fix";

        $result = $this->parseBlock($block);

        $this->assertSame('fix', $result['items'][0]['type']);
    }

    public function test_parse_block_maps_changed_to_improvement(): void
    {
        $block = "[1.0.0] - 2026-01-01\n\n### Changed\n- Better UX";

        $result = $this->parseBlock($block);

        $this->assertSame('improvement', $result['items'][0]['type']);
    }

    public function test_parse_block_maps_security_section(): void
    {
        $block = "[1.0.0] - 2026-01-01\n\n### Security\n- Rate limiting";

        $result = $this->parseBlock($block);

        $this->assertSame('security', $result['items'][0]['type']);
    }

    public function test_parse_block_assigns_order_incrementally(): void
    {
        $block = "[1.0.0] - 2026-01-01\n\n### Added\n- Item A\n- Item B\n- Item C";

        $result = $this->parseBlock($block);

        $this->assertSame(0, $result['items'][0]['order']);
        $this->assertSame(1, $result['items'][1]['order']);
        $this->assertSame(2, $result['items'][2]['order']);
    }

    public function test_parse_block_handles_multiple_sections(): void
    {
        $block = "[1.0.0] - 2026-01-01\n\n### Added\n- Feature\n\n### Fixed\n- Fix\n\n### Security\n- Patch";

        $result = $this->parseBlock($block);

        $this->assertCount(3, $result['items']);
        $types = array_column($result['items'], 'type');
        $this->assertContains('feature', $types);
        $this->assertContains('fix', $types);
        $this->assertContains('security', $types);
    }

    public function test_parse_block_maps_removed_to_removal(): void
    {
        $block = "[1.0.0] - 2026-01-01\n\n### Removed\n- Legacy API";

        $result = $this->parseBlock($block);

        $this->assertSame('removal', $result['items'][0]['type']);
        $this->assertSame('Legacy API', $result['items'][0]['title']);
    }

    public function test_parse_block_maps_deprecated_to_deprecation(): void
    {
        $block = "[1.0.0] - 2026-01-01\n\n### Deprecated\n- Old endpoint";

        $result = $this->parseBlock($block);

        $this->assertSame('deprecation', $result['items'][0]['type']);
        $this->assertSame('Old endpoint', $result['items'][0]['title']);
    }
}
