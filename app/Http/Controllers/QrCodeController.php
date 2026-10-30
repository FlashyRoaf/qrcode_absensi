<?php

namespace App\Http\Controllers;

use App\Models\Qrcode as ModelsQrcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function index()
    {
        return Inertia::render("qrcode/scan");
    }

    public function create(Request $request)
    {
        $request->validate([
            'type' => 'required|in:check_in,check_out',
            'expires_in_minutes' => 'required|numeric|min:0.1',
        ]);

        $token = Str::uuid();
        // In seconds, not minutes: the scan page can now send a
        // fractional value (30 seconds = 0.5 minutes), and converting
        // here guarantees the exact expiry instead of relying on
        // fractional-minute handling.
        $expiresAt = now()->addSeconds((int) round($request->expires_in_minutes * 60));

        // One QR code is for one employee only. It is marked used after
        // that employee's first successful scan, so the operator must
        // generate a fresh code for the next employee (see scan.vue,
        // which watches this code's status and prompts for a new one).
        $qrCode = ModelsQrcode::create([
            'token' => $token,
            'type' => $request->type,
            'expires_at' => $expiresAt,
        ]);

        $qrCodeUrl = route('qrcode.scanned', ['token' => $qrCode->token]);

        // Render the QR code as an <img>, never as raw HTML: the SVG
        // string is sent as a data URI, so even if it ever contained
        // markup it cannot run as page content. As an <img>, an SVG
        // is also run in an isolated image context with scripts off.
        $qrSvg = QrCode::size(300)->generate($qrCodeUrl);

        return Inertia::render("qrcode/scan", [
            'qrCodeImage' => 'data:image/svg+xml;base64,' . base64_encode($qrSvg),
            'qr_token' => $qrCode->token,
            'expires_at' => $expiresAt,
        ]);
    }

    // Lets the scan page notice when its code has been used, so it can
    // prompt the operator to make a fresh code for the next employee.
    // Scanner-only (see routes/auth.php); the token alone is not an
    // attendance record — attendance still requires a logged-in employee
    // to scan it and pass the location check.
    public function status(string $token): JsonResponse
    {
        $qrCode = ModelsQrcode::where('token', $token)->first();

        if (!$qrCode) {
            return response()->json(['error' => 'QR Code tidak ditemukan.'], 404);
        }

        return response()->json([
            'is_used' => (bool) $qrCode->is_used,
            'type' => $qrCode->type,
            'expires_at' => $qrCode->expires_at,
            'expired' => $qrCode->expires_at ? now()->gt($qrCode->expires_at) : false,
        ]);
    }
}
