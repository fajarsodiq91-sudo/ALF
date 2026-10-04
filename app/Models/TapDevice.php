<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A network RFID/QR reader (ESP32 or standalone) that reports taps to the app with its own secret token. */
#[Fillable(['name', 'location', 'is_active'])]
class TapDevice extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /** Creates the device and returns it with the plain token, which is shown once and never stored. */
    public static function register(string $name, ?string $location): array
    {
        $token = Str::random(48);
        $device = (new self(['name' => $name, 'location' => $location, 'is_active' => true]))->forceFill(['token_hash' => self::hash($token)]);
        $device->save();

        return [$device, $token];
    }

    /** Replaces the secret; the old one stops working immediately. */
    public function regenerateToken(): string
    {
        $token = Str::random(48);
        $this->forceFill(['token_hash' => self::hash($token)])->save();

        return $token;
    }

    public static function findByToken(string $token): ?self
    {
        return self::where('token_hash', self::hash($token))->first();
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
