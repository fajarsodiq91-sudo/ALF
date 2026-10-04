<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TapRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TapController extends Controller
{
    /** Body: `uid` (RFID card number) or `qr` (text a scanner read from the ID card QR code). */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uid' => ['nullable', 'string', 'max:255', 'required_without:qr'],
            'qr' => ['nullable', 'string', 'max:1000', 'required_without:uid'],
        ]);

        $result = TapRecorder::record($data['uid'] ?? null, $data['qr'] ?? null, $request->attributes->get('tap_device'));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
