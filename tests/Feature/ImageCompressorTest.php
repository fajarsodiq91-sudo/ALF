<?php

namespace Tests\Feature;

use App\Services\ImageCompressor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageCompressorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_large_photo_is_resized_and_shrunk(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        $image = imagecreatetruecolor(4000, 3000);
        for ($i = 0; $i < 20000; $i++) {
            imagesetpixel($image, random_int(0, 3999), random_int(0, 2999), random_int(0, 0xFFFFFF));
        }
        ob_start();
        imagejpeg($image, null, 100);
        $original = ob_get_clean();
        unset($image);

        $path = ImageCompressor::store(UploadedFile::fake()->createWithContent('photo.jpg', $original), 'customer-photos');

        $stored = Storage::disk('public')->get($path);
        [$width, $height] = getimagesizefromstring($stored);

        $this->assertStringEndsWith('.jpg', $path);
        $this->assertSame([ImageCompressor::MAX_DIMENSION, 1200], [$width, $height]);
        $this->assertLessThan(strlen($original), strlen($stored));
    }

    public function test_non_image_files_are_stored_untouched(): void
    {
        $pdf = UploadedFile::fake()->createWithContent('bukti.pdf', '%PDF-1.4 dummy content');

        $path = ImageCompressor::store($pdf, 'proofs');

        $this->assertStringStartsWith('proofs/', $path);
        $this->assertSame('%PDF-1.4 dummy content', Storage::disk('public')->get($path));
    }
}
