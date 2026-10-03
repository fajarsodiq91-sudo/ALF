<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An uploaded A4 landscape (297 x 210 mm) PNG background. Dynamic text, signature and QR code are laid over it
 * at the positions in `layout`: x = horizontal centre and y = top edge, both in mm from the page's top-left corner.
 */
class CertificateTemplate extends Model
{
    protected $fillable = ['name', 'background_path', 'layout', 'is_default'];

    /** Field label => [x, y, width (mm)]; the QR code and signature use width as their image size/width. */
    public const FIELDS = [
        'number' => ['Certificate number (under CERTIFICATE)', 148.5, 62, 120],
        'name' => ['Recipient name', 148.5, 80, 250],
        'statement' => ['Description sentence', 148.5, 112, 200],
        'date' => ['Issue date', 60, 160, 70],
        'qr' => ['QR code', 148.5, 140, 28],
        'id_number' => ['ID number (under QR code)', 148.5, 170, 60],
        'signature' => ['Signature image', 240, 135, 45],
        'signer_name' => ['Signer name', 240, 160, 60],
        'signer_title' => ['Signer position', 240, 167, 60],
    ];

    protected function casts(): array
    {
        return ['layout' => 'array', 'is_default' => 'boolean'];
    }

    public function programs(): HasMany
    {
        return $this->hasMany(TrainingProgram::class);
    }

    /** Defaults filled in for any field the saved layout lacks. @return array<string, array{x: float, y: float, w: float}> */
    public function positions(): array
    {
        $positions = [];

        foreach (self::FIELDS as $key => [, $x, $y, $w]) {
            $saved = $this->layout[$key] ?? [];
            $positions[$key] = [
                'x' => (float) ($saved['x'] ?? $x),
                'y' => (float) ($saved['y'] ?? $y),
                'w' => (float) ($saved['w'] ?? $w),
            ];
        }

        return $positions;
    }

    /** The template for a program: its own choice, else the default one, else null (legacy design). */
    public static function forProgram(?TrainingProgram $program): ?self
    {
        return ($program?->certificate_template_id ? $program->certificateTemplate : null)
            ?? self::where('is_default', true)->first();
    }
}
