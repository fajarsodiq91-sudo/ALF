<?php

namespace App\Http\Controllers;

use App\Services\TapRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A staff member's phone or tablet camera scans ID-card QR codes at the door or training room, recording attendance like a reader would. */
class AttendanceKioskController extends Controller
{
    public function index(): View
    {
        return view('kiosk.scan');
    }

    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate(['qr' => ['required', 'string', 'max:1000']]);

        return response()->json(TapRecorder::record(null, $data['qr']));
    }
}
