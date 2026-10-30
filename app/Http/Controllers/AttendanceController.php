<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Qrcode;
use App\Services\AttendanceService;
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

    // Server-side search, sort and pagination. The page used to load
    // every attendance row and filter it in the browser; with months of
    // history that meant the whole table on every visit. Now the
    // database filters and pages, and the page only renders one page.
    // Stats are computed from the filtered query (all matching rows),
    // not just the current page.
    public function show(Request $request): Response
    {
        $search = (string) $request->input('search', '');
        $date = (string) $request->input('date', '');
        $sortKey = (string) $request->input('sort', 'date');
        $sortDir = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $sortColumns = [
            'userName' => 'users.name',
            'date' => 'attendances.date',
            'checkIn' => 'attendances.check_in',
            'checkOut' => 'attendances.check_out',
            'hoursWorked' => 'attendances.duration_minutes',
        ];
        $sortColumn = $sortColumns[$sortKey] ?? 'attendances.date';

        $query = Attendance::query()
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->select('attendances.*', 'users.name as user_name');

        if ($search !== '') {
            $query->where('users.name', 'like', '%' . $search . '%');
        }

        if ($date !== '') {
            $query->whereDate('attendances.date', $date);
        }

        $statsQuery = clone $query;
        $stats = [
            'totalHours' => round((float) $statsQuery->sum('attendances.duration_minutes') / 60, 1),
            'count' => (int) $statsQuery->count(),
        ];

        $page = $query
            ->orderBy($sortColumn, $sortDir)
            ->orderBy('attendances.id', 'desc')
            ->paginate(50)
            ->withQueryString();

        $page->getCollection()->transform(fn ($r) => [
            'id' => $r->id,
            'user_id' => $r->user_id,
            'user_name' => $r->user_name ?? 'Unknown',
            'qrcode' => $r->qrcode,
            'date' => $r->date,
            'check_in' => $r->check_in,
            'check_out' => $r->check_out,
            'duration_minutes' => $r->duration_minutes,
            // Shown to admins so a GPS result can be reviewed. It is
            // client-supplied data, not proof of presence.
            'distance' => $r->distance,
            'latitude' => $r->latitude,
            'longitude' => $r->longitude,
            'missing_checkout' => $r->check_in && !$r->check_out
                && $r->date < now()->toDateString(),
        ]);

        // Explicit paginator shape (see note in PenaltyController):
        // the browser gets this from Inertia anyway, but tests see
        // the raw paginator, which has no 'meta' key to assert on.
        return Inertia::render('admin/Attendance', [
            'attendances' => $page->toArray(),
            'filters' => [
                'search' => $search,
                'date' => $date,
                'sort' => $sortKey,
                'dir' => $sortDir,
            ],
            'stats' => $stats,
        ]);
    }

    // Lightweight poll target for the admin Attendance page: returns
    // only the newest attendance id and timestamp, so the page can
    // skip the full stats query when nothing has changed.
    public function latest(): \Illuminate\Http\JsonResponse
    {
        $latest = Attendance::query()->orderByDesc('id')->first(['id', 'updated_at']);

        return response()->json([
            'latest_id' => $latest?->id,
            'latest_updated_at' => $latest?->updated_at?->toIso8601String(),
            'count' => Attendance::count(),
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
        // These coordinates come from the employee's phone, so they are a
        // hint, not proof of presence: they can be faked. The check below
        // only rejects obviously invalid values; admins can review the
        // recorded distance on the admin Attendance page.

        if (is_null($latitude) || is_null($longitude)
            || !is_numeric($latitude) || !is_numeric($longitude)
            || $latitude < -90 || $latitude > 90
            || $longitude < -180 || $longitude > 180
            || ((float) $latitude === 0.0 && (float) $longitude === 0.0)
        ) {
            return Inertia::render('qrcode/scanned', [
                'message' => 'Lokasi tidak valid. Aktifkan GPS dan coba lagi.',
                'type'    => $qrCode->type ?? null,
                'success' => false,
            ]);
        }

        $radiusMeters = (int) config('staks.radius_meters', 50);

        $distance = $this->getDistanceInMeters(
            (float) config('staks.latitude'),
            (float) config('staks.longitude'),
            (float) $latitude,
            (float) $longitude,
        );

        if ($distance > $radiusMeters) {
            return Inertia::render('qrcode/scanned', [
                'message' => 'Anda berada ' . round($distance) . ' meter dari kantor. Absensi hanya bisa dilakukan dalam radius ' . $radiusMeters . ' meter.',
                'type'    => $qrCode->type ?? null,
                'success' => false,
            ]);
        }

        // ── Proses Absensi ────────────────────────────────────────────────

        $openSession = Attendance::where('user_id', $user->id)
            ->whereNull('check_out')
            ->latest()
            ->first();

        if ($openSession && $openSession->date !== now()->toDateString()) {
            // A session from a previous day that was never checked out.
            // Do NOT invent a check-out time for it, and do NOT silently
            // clear it: leave the row open so it shows up as a missing
            // check-out in the dashboard and the admin Attendance page,
            // and start today's session separately.
            $openSession = null;
        }

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
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance' => $distance,
            ]);

            // ── Kirim Notifikasi WA Check In ──────────────────────────────
            if ($user->phone) {
                // Kumulatif minggu ini SEBELUM saat ini (sesi saat ini belum selesai)
                $weeklyMinutes   = $this->getWeeklyMinutes($user->id, $waktuSekarang);
                $weeklyFormatted = $this->formatMinutes($weeklyMinutes);
                $weeklyTarget = $this->formatMinutes((int) config("staks.target_minutes", 870) - $weeklyMinutes);

                $this->whatsapp->sendAttendanceNotification(
                    phone:        $user->phone,
                    name:         $user->name,
                    type:         'check_in',
                    time:         $waktuSekarang->copy()->setTimezone('Asia/Makassar')->format('d/m/Y H:i:s') . ' WITA',
                    weeklyDurasi: $weeklyFormatted,
                    weeklyTarget: $weeklyTarget,
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
                $weeklyTarget = $this->formatMinutes((int) config("staks.target_minutes", 870) - $weeklyMinutes);

                $this->whatsapp->sendAttendanceNotification(
                    phone:        $user->phone,
                    name:         $user->name,
                    type:         'check_out',
                    time:         $waktuSekarang->copy()->setTimezone('Asia/Makassar')->format('d/m/Y H:i:s') . " WITA (durasi saat ini: {$durasi})",
                    weeklyDurasi: $weeklyFormatted,
                    weeklyTarget: $weeklyTarget,
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

        return AttendanceService::weeklyMinutes($userId, $senin, $akhir, true);
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
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
