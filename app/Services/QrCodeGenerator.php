<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeGenerator
{
    /** Returns an inline-ready SVG (no XML prolog) for the given text. */
    public static function svg(string $data, int $size = 240): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, 2), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString($data);

        return trim(preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));
    }
}
