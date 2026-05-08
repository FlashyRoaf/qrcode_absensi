<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Qrcode;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    protected WhatsAppService $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function show(): Response
    {
        $attendances = Attendance::with('user')
            ->get()
            ->map(fn($r) => [
                'id'               => $r->id,
                'user_id'          => $r->user_id,
                'user_name'        => $r->user->name ?? 'Unknown',
                'qrcode'           => $r->qrcode,
                'date'             => $r->date,
                'check_in'         => $r->check_in,
                'check_out'        => $r->check_out,
                'duration_minutes' => $r->duration_minutes,
            ]);

        return Inertia::render('admin/Attendance', [
            'attendances' => $attendances,
        ]);
    }

    public function index(Request $request, $token)
    {
        if ($request->isMethod('get')) {
            return Inertia::render('qrcode/scanned', [
                'token'   => $token,
                'message' => null,
                'type'    => null,
                'success' => null,
            ]);
        }

        $user      = Auth::user();
        $qrCode    = Qrcode::where('token', $token)->first();
        $latitude  = $request->latitude;
        $longitude = $request->longitude;
	\Log::info('Koordinat diterima: lat=' . $latitude . ' lng=' . $longitude);
	\Log::info('Koordinat kantor: lat=' . config('staks.latitude') . ' lng=' . config('staks.longitude'));

        // ── Validasi QR Code ──────────────────────────────────────────────

        if (!$qrCode) {
            abort(404, 'QR Code tidak ditemukan');
        }

        if (now()->gt($qrCode->expires_at)) {
            return Inertia::render('qrcode/scanned', [
                'message' => 'QR Code telah kadaluwarsa.',
                'type'    => $qrCode->type,
                'success' => false,
            ]);
        }

        if (Carbon::now()->isSunday()) {
            return Inertia::render('qrcode/scanned', [
                'message' => 'Hari Minggu tidak diperbolehkan untuk melakukan absensi.',
                'type'    => $qrCode->type,
                'success' => false,
            ]);
        }

        if ($qrCode->is_used) {
            return Inertia::render('qrcode/scanned', [
                'message' => 'QR Code sudah digunakan.',
                'type'    => $qrCode->type,
                'success' => false,
            ]);
        }

        // ── Validasi Lokasi GPS ───────────────────────────────────────────

        if (is_null($latitude) || is_null($longitude)) {
            return Inertia::render('qrcode/scanned', [
                'message' => 'Lokasi tidak dapat dideteksi. Aktifkan GPS dan coba lagi.',
                'type'    => $qrCode->type ?? null,
                'success' => false,
            ]);
        }

        $distance = $this->getDistanceInMeters(
            (float) config('staks.latitude'),
            (float) config('staks.longitude'),
            (float) $latitude,
            (float) $longitude,
        );

        if ($distance > 50) {
            return Inertia::render('qrcode/scanned', [
                'message' => 'Anda berada ' . round($distance) . ' meter dari kantor. Absensi hanya bisa dilakukan dalam radius 50 meter.',
                'type'    => $qrCode->type ?? null,
                'success' => false,
            ]);
        }

        // ── Proses Absensi ────────────────────────────────────────────────

        $openSession = Attendance::where('user_id', $user->id)
            ->whereNull('check_out')
            ->latest()
            ->first();

        $tipe = [
            'check_in'  => 'Check In',
            'check_out' => 'Check Out',
        ];

        $waktuSekarang = Carbon::now();

        if ($qrCode->type === 'check_in') {

            if ($openSession) {
                return Inertia::render('qrcode/scanned', [
                    'message' => 'Masih ada sesi yang belum di-checkout.',
                    'type'    => $qrCode->type,
                    'success' => false,
                ]);
            }

            Attendance::create([
                'user_id'  => $user->id,
                'qrcode'   => $qrCode->token,
                'date'     => now()->toDateString(),
                'check_in' => $waktuSekarang,
            ]);

            // ── Kirim Notifikasi WA Check In ──────────────────────────────
            if ($user->phone) {
                // Kumulatif minggu ini SEBELUM saat ini (sesi saat ini belum selesai)
                $weeklyMinutes   = $this->getWeeklyMinutes($user->id, $waktuSekarang);
                $weeklyFormatted = $this->formatMinutes($weeklyMinutes);

                $this->whatsapp->sendAttendanceNotification(
                    phone:        $user->phone,
                    name:         $user->name,
                    type:         'check_in',
                    time:         $waktuSekarang->copy()->setTimezone('Asia/Makassar')->format('d/m/Y H:i:s') . ' WITA',
                    weeklyDurasi: $weeklyFormatted,
                );
            }

        } else {

            if (!$openSession || !$openSession->check_in) {
                return Inertia::render('qrcode/scanned', [
                    'message' => 'Tidak ada sesi aktif untuk di-checkout.',
                    'type'    => $qrCode->type,
                    'success' => false,
                ]);
            }

            $duration = Carbon::parse($openSession->check_in)->diffInMinutes($waktuSekarang);

            if ($duration < 1) {
                return Inertia::render('qrcode/scanned', [
                    'message' => 'Tunggu beberapa saat sebelum melakukan check-out.',
                    'type'    => $qrCode->type,
                    'success' => false,
                ]);
            }

            $openSession->update([
                'check_out'        => $waktuSekarang,
                'duration_minutes' => $duration,
            ]);

            // ── Kirim Notifikasi WA Check Out ─────────────────────────────
            if ($user->phone) {
                $durasi = $this->formatMinutes($duration);

                // Kumulatif minggu ini TERMASUK sesi yang baru selesai
                $weeklyMinutes   = $this->getWeeklyMinutes($user->id, $waktuSekarang);
                $weeklyFormatted = $this->formatMinutes($weeklyMinutes);

                $this->whatsapp->sendAttendanceNotification(
                    phone:        $user->phone,
                    name:         $user->name,
                    type:         'check_out',
                    time:         $waktuSekarang->copy()->setTimezone('Asia/Makassar')->format('d/m/Y H:i:s') . " WITA (durasi saat ini: {$durasi})",
                    weeklyDurasi: $weeklyFormatted,
                );
            }
        }

        $qrCode->update(['is_used' => true]);

        return Inertia::render('qrcode/scanned', [
            'message' => $user->name . ' Berhasil melakukan ' . $tipe[$qrCode->type],
            'type'    => $qrCode->type,
            'success' => true,
        ]);
    }

    // ── Hitung total menit kerja minggu ini (Senin s/d saat ini) ─────────
    // Hanya menghitung hari yang ada absensinya (check_out tidak null)
    private function getWeeklyMinutes(int $userId, Carbon $now): int
    {
        $senin = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $akhir = $now->copy()->endOfDay();

        return (int) Attendance::where('user_id', $userId)
            ->whereBetween('date', [$senin->toDateString(), $akhir->toDateString()])
            ->whereNotNull('check_out')
            ->sum('duration_minutes');
    }

    // ── Format menit → "X jam Y menit" ───────────────────────────────────
    private function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) return '0 menit';
        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;
        if ($hours > 0 && $mins > 0) return "{$hours} jam {$mins} menit";
        if ($hours > 0)              return "{$hours} jam";
        return "{$mins} menit";
    }

    // ── Haversine formula ─────────────────────────────────────────────────
    private function getDistanceInMeters(
        float $lat1, float $lon1,
        float $lat2, float $lon2
    ): float {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
