<?php

namespace App\Services;

use FontLib\Font;

/**
 * Splits a recipient name into the lines it takes on a certificate, measured with the certificate's own script font.
 * Deciding this here, instead of letting the renderer wrap, keeps the PDF and the portal preview identical and lets
 * the template keep the last line in place while extra lines grow upward.
 */
class CertificateNameLines
{
    public const FONT_SIZE_PX = 64;

    /** How far each extra line raises the lines above it, in mm; tight enough to keep two lines clear of the number. */
    public const LINE_PITCH_MM = 15;

    /** @var array{unitsPerEm: int, glyphs: array<int, int>, widths: array<int, array<int, int>>}|null */
    private static ?array $metrics = null;

    /**
     * Fills each line with as many words as fit in the box; a single word wider than the box keeps its own line.
     *
     * @return list<string>
     */
    public static function split(string $name, float $boxWidthMm): array
    {
        $boxWidthPx = $boxWidthMm * 96 / 25.4;
        $lines = [];
        $line = '';

        foreach (preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;

            if ($line !== '' && self::widthPx($candidate) > $boxWidthPx) {
                $lines[] = $line;
                $candidate = $word;
            }

            $line = $candidate;
        }

        return [...$lines, $line];
    }

    private static function widthPx(string $text): float
    {
        if (self::$metrics === null) {
            $font = Font::load(public_path('fonts/certificate/GreatVibes-Regular.ttf'));
            $font->parse();
            self::$metrics = [
                'unitsPerEm' => (int) $font->getData('head', 'unitsPerEm'),
                'glyphs' => $font->getUnicodeCharMap(),
                'widths' => $font->getData('hmtx'),
            ];
            $font->close();
        }

        $units = 0;

        foreach (mb_str_split($text) as $character) {
            $glyph = self::$metrics['glyphs'][mb_ord($character)] ?? 0;
            $units += self::$metrics['widths'][$glyph][0] ?? 0;
        }

        return $units / self::$metrics['unitsPerEm'] * self::FONT_SIZE_PX;
    }
}
