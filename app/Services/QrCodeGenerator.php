<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeGenerator
{
    /**
     * Returns an inline-ready SVG (no XML prolog) for the given text.
     * Pass $highErrorCorrection when a logo will be overlaid on top of the code, so it still scans.
     */
    public static function svg(string $data, int $size = 240, bool $highErrorCorrection = false): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, 2), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString($data, ecLevel: $highErrorCorrection ? ErrorCorrectionLevel::H() : null);

        return trim(preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));
    }
}
