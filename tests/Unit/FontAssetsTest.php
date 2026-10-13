<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FontAssetsTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function fontFiles(): array
    {
        return ['normal' => ['Lora-Regular.ttf'], 'italic' => ['Lora-Italic.ttf']];
    }

    #[DataProvider('fontFiles')]
    public function test_local_font_has_all_precomposed_vietnamese_glyphs(string $filename): void
    {
        $path = dirname(__DIR__, 2).'/public/fonts/lora/'.$filename;
        $this->assertFileExists($path);
        $font = file_get_contents($path);
        $this->assertSame("\x00\x01\x00\x00", substr($font, 0, 4));
        $cmap = $this->unicodeCmap($font);
        $codepoints = array_merge(range(0x1EA0, 0x1EF9), [0x00C0, 0x00C1, 0x00C2, 0x00C8, 0x00C9, 0x00CA, 0x00CC, 0x00CD, 0x00D2, 0x00D3, 0x00D4, 0x00D9, 0x00DA, 0x00DD, 0x00E0, 0x00E1, 0x00E2, 0x00E8, 0x00E9, 0x00EA, 0x00EC, 0x00ED, 0x00F2, 0x00F3, 0x00F4, 0x00F9, 0x00FA, 0x00FD, 0x0102, 0x0103, 0x0110, 0x0111, 0x0128, 0x0129, 0x0168, 0x0169, 0x01A0, 0x01A1, 0x01AF, 0x01B0]);
        foreach ($codepoints as $codepoint) {
            $this->assertGreaterThan(0, $this->glyphId($font, $cmap, $codepoint), $filename.' missing U+'.strtoupper(dechex($codepoint)));
        }
    }

    public function test_font_stylesheet_keeps_local_normal_italic_faces_and_license(): void
    {
        $root = dirname(__DIR__, 2);
        $css = file_get_contents($root.'/public/fonts/lora/fonts.css');
        $this->assertStringContainsString('font-style: normal', $css);
        $this->assertStringContainsString('font-style: italic', $css);
        $this->assertStringContainsString('./Lora-Regular.ttf', $css);
        $this->assertStringContainsString('./Lora-Italic.ttf', $css);
        $this->assertStringNotContainsString('https://', $css);
        $this->assertStringContainsString('SIL OPEN FONT LICENSE', file_get_contents($root.'/public/fonts/lora/OFL.txt'));
        $this->assertStringContainsString('@import "../../public/fonts/lora/fonts.css"', file_get_contents($root.'/resources/css/app.css'));
        $this->assertStringContainsString('content_css: \'/fonts/lora/fonts.css\'', file_get_contents($root.'/resources/js/app.js'));
    }

    private function unicodeCmap(string $font): int
    {
        $count = $this->uint16($font, 4);
        for ($table = 0; $table < $count; $table++) {
            $record = 12 + $table * 16;
            if (substr($font, $record, 4) !== 'cmap') {
                continue;
            }
            $cmap = unpack('N', substr($font, $record + 8, 4))[1];
            $encodings = $this->uint16($font, $cmap + 2);
            for ($encoding = 0; $encoding < $encodings; $encoding++) {
                $entry = $cmap + 4 + $encoding * 8;
                $platform = $this->uint16($font, $entry);
                $offset = $cmap + unpack('N', substr($font, $entry + 4, 4))[1];
                if (in_array($platform, [0, 3], true) && $this->uint16($font, $offset) === 4) {
                    return $offset;
                }
            }
        }
        $this->fail('Font must contain a Unicode BMP cmap.');

        return 0;
    }

    private function glyphId(string $font, int $cmap, int $codepoint): int
    {
        $segments = (int) ($this->uint16($font, $cmap + 6) / 2);
        $endCodes = $cmap + 14;
        $startCodes = $endCodes + $segments * 2 + 2;
        $deltas = $startCodes + $segments * 2;
        $rangeOffsets = $deltas + $segments * 2;
        for ($segment = 0; $segment < $segments; $segment++) {
            $start = $this->uint16($font, $startCodes + $segment * 2);
            $end = $this->uint16($font, $endCodes + $segment * 2);
            if ($codepoint < $start || $codepoint > $end) {
                continue;
            }
            $delta = $this->uint16($font, $deltas + $segment * 2);
            $entry = $rangeOffsets + $segment * 2;
            $offset = $this->uint16($font, $entry);
            if ($offset === 0) {
                return ($codepoint + $delta) & 0xFFFF;
            }
            $glyph = $this->uint16($font, $entry + $offset + ($codepoint - $start) * 2);

            return $glyph === 0 ? 0 : ($glyph + $delta) & 0xFFFF;
        }

        return 0;
    }

    private function uint16(string $font, int $offset): int
    {
        return unpack('n', substr($font, $offset, 2))[1];
    }
}
