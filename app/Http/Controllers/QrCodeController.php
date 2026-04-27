<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Qrcode as ModelsQrcode;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    //
    public function index() {

        // $url = route('qrcode.scan', ['token' => 'abc123']);

        // $qrCode = QrCode::size(300)->generate($url)->toHtml();

        // $qrCode = QrCode::size(300)->generate(route('qrcode.scan', ['token' => 'd7acc6c9-a0eb-42ef-94ff-621825c738f3']))->toHtml();

        return Inertia::render("qrcode/scan");
        // return Inertia::render("qrcode/scan", [
        //     'shifts' => Shift::all(),
        //     'divisions' => Division::all(),
        //     // 'qrCode' => $qrCode,
        // ]);
    }

    public function create(Request $request) {
        $request->validate([
            // 'shift' => 'required|exists:shifts,name',
            // 'division' => 'required|exists:divisions,name',
            'type' => 'required|in:check_in,check_out',
            'expires_in_minutes' => 'required|integer|min:1',
        ]);

        $token = Str::uuid();
        $expiresAt = now()->addMinutes($request->expires_in_minutes);

        $qrCode = ModelsQrcode::create([
            'token' => $token,
            // 'shift' => $request->shift,
            // 'division' => $request->division,
            'type' => $request->type,
            'expires_at' => $expiresAt,
        ]);

        $qrCodeUrl = route('qrcode.scanned', ['token' => $qrCode->token]);

        return Inertia::render("qrcode/scan", [
            // 'shifts' => Shift::all(),
            // 'divisions' => Division::all(),
            'qrCode' => QrCode::size(300)->generate($qrCodeUrl)->toHtml(),
            'expires_at' => $expiresAt,
        ]);
    }
}
