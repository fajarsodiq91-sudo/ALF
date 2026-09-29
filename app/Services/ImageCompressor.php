<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded files, shrinking photos (JPEG/PNG/WebP) first so they don't bloat the server.
 * Non-image files — and images when GD is unavailable — are stored untouched.
 */
class ImageCompressor
{
    public const MAX_DIMENSION = 1600;

    public const QUALITY = 80;

    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public static function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $compressed = self::compress($file);

        if ($compressed === null) {
            return $file->store($directory, $disk);
        }

        [$contents, $extension] = $compressed;
        $path = trim($directory, '/').'/'.Str::random(40).'.'.$extension;

        Storage::disk($disk)->put($path, $contents);

        return $path;
    }

    /** @return array{0: string, 1: string}|null the re-encoded bytes and extension, or null to keep the original */
    public static function compress(UploadedFile $file): ?array
    {
        $mime = $file->getMimeType();

        if (! extension_loaded('gd') || ! isset(self::TYPES[$mime])) {
            return null;
        }

        // Decoding a 12 MP phone photo takes ~50 MB of raw pixels; don't let the default 128M limit kill the request.
        self::raiseMemoryLimit('512M');

        $original = $file->get();
        $image = @imagecreatefromstring($original);

        if (! $image instanceof GdImage) {
            return null;
        }

        $image = self::resize(self::orient($image, $file, $mime), $mime);
        $contents = self::encode($image, $mime);
        unset($image);

        if ($contents === null || strlen($contents) >= strlen($original)) {
            return null;
        }

        return [$contents, self::TYPES[$mime]];
    }

    private static function raiseMemoryLimit(string $limit): void
    {
        $current = ini_get('memory_limit');

        if ($current !== '-1' && ini_parse_quantity($current) < ini_parse_quantity($limit)) {
            ini_set('memory_limit', $limit);
        }
    }

    /** Phone cameras store rotation in EXIF; re-encoding drops it, so bake it into the pixels. */
    private static function orient(GdImage $image, UploadedFile $file, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $angle = match (@exif_read_data($file->getRealPath())['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle ? (imagerotate($image, $angle, 0) ?: $image) : $image;
    }

    private static function resize(GdImage $image, string $mime): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = self::MAX_DIMENSION / max($width, $height);

        if ($scale >= 1) {
            return $image;
        }

        $resized = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

        if ($mime !== 'image/jpeg') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);

        return $resized;
    }

    private static function encode(GdImage $image, string $mime): ?string
    {
        if ($mime === 'image/jpeg') {
            imageinterlace($image, true); // progressive JPEG: smaller and loads gradually
        }

        ob_start();

        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($image, null, self::QUALITY),
            'image/png' => imagesavealpha($image, true) && imagepng($image, null, 9),
            'image/webp' => function_exists('imagewebp') && imagewebp($image, null, self::QUALITY),
        };

        $contents = ob_get_clean();

        return $ok && $contents !== false ? $contents : null;
    }
}
