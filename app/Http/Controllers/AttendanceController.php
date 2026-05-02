<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Qrcode;
use App\Models\Shift;
use App\Models\Notice;
use App\Notifications\TelegramNotif;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;
use PhpParser\Builder\Function_;

class AttendanceController extends Controller
{
    use Notifiable;
   public function show(): Response {
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
    //
    public function index(Request $request, $token)
    {
        if ($request->isMethod('get')) {
            return Inertia::render('qrcode/scanned', [
                'token' => $token,
                'message' => null,
                'type' => null,
                'success' => null,
            ]);
        }
        
        $user = Auth::user();
        $qrCode = Qrcode::where('token', $token)->first();
        // $attendance = Attendance::where('user_id', $user->id)
        // ->where('date', now()->toDateString())->first();
        
        $latitude  = $request->latitude;
        $longitude = $request->longitude;

        if (!$qrCode) {
            abort(404, 'QR Code tidak ditemukan');
        }
        
        if (now()->gt($qrCode->expires_at)) {
            return Inertia::render('qrcode/scanned', [
                'message' => 'QR Code telah kadaluwarsa.',
                'type' => $qrCode->type,
                'success' => false,
            ]);
        }

        if (Carbon::now()->isSunday()) {
            return Inertia::render('qrcode/scanned', [
                'message' => 'Hari Minggu tidak diperbolehkan untuk melakukan absensi.',
                'type' => $qrCode->type,
                'success' => false,
            ]);
        }
        
        if ($qrCode->is_used) {
            return Inertia::render('qrcode/scanned', [
                'message' => 'QR Code sudah digunakan.',
                'type' => $qrCode->type,
                'success' => false,
            ]);
        }
        
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
                'message' => 'Anda berada ' . round($distance) . ' meter dari kantor. Absensi hanya bisa dilakukan dalam radius ' . 50 . ' meter.',
                'type'    => $qrCode->type ?? null,
                'success' => false,
            ]);
        }
        
        $openSession = Attendance::where('user_id', $user->id)->whereNull('check_out')->latest()->first();

        $tipe = [
            'check_in' => 'Check In',
            'check_out' => 'Check Out',
        ];

        if ($qrCode->type === 'check_in') {
            // if ($attendance && $attendance->check_in) {
            //     return Inertia::render('qrcode/scanned', [
            //         'message' => 'Kamu sudah melakukan check-in hari ini.',
            //     ]);
            // } else {
            //     Attendance::create([
            //         'user_id' => $user->id,
            //         'qrcode' => $qrCode->token,
            //         'date' => now()->toDateString(),
            //         'check_in' => Carbon::now(),
            //     ]);
            // }

            if ($openSession) {
                return Inertia::render('qrcode/scanned', [
                    'message' => 'Masih ada sesi yang belum di-checkout.',
                    'type' => $qrCode->type,
                    'success' => false,
                ]);
            }

            Attendance::create([
                'user_id' => $user->id,
                'qrcode' => $qrCode->token,
                'date' => now()->toDateString(),
                'check_in' => now(),
            ]);

        } else {
            // if (!$attendance || !$attendance->check_in) {
            //     return Inertia::render('qrcode/scanned', [
            //         'message' => 'Kamu belum melakukan check-in hari ini.',
            //     ]);
            // }

            // if ($attendance->check_out) {
            //     return Inertia::render('qrcode/scanned', [
            //         'message' => 'Kamu sudah melakukan check-out hari ini.',
            //     ]);
            // }

            if (!$openSession || !$openSession->check_in) {
                return Inertia::render('qrcode/scanned', [
                    'message' => 'Tidak ada sesi aktif untuk di-checkout.',
                    'type' => $qrCode->type,
                    'success' => false,
                ]);
            }

            $duration = Carbon::parse($openSession->check_in)->diffInMinutes(Carbon::now());
            
            if ($duration < 1) {
                return Inertia::render('qrcode/scanned', [
                    'message' => 'Tunggu beberapa saat sebelum melakukan check-out.',
                    'type' => $qrCode->type,
                    'success' => false,
                ]);
            }
            
            $openSession->update([
                'check_out' => Carbon::now(),
                'duration_minutes' => $duration,
            ]);
        }
        
        // if ($user->shift !== $qrCode->shift || $user->division !== $qrCode->division) {
        //     abort(403, 'Anda tidak diizinkan untuk mengikuti shift atau divisi ini');
        // }


        // if ($attendance && now()->gt(Shift::where('name', $qrCode->shift)->first()->end_time)) {

        //     if ($attendance->check_out) {
        //         return Inertia::render('qrcode/scanned', [
        //             'division' => $qrCode->division,
        //             'shift' => $qrCode->shift,
        //             'message' => 'Kamu sudah melakukan check-out hari ini.',
        //         ]);
        //     }

        //     $attendance->update([
        //         'check_out' => Carbon::now(),
        //     ]);
            
        // } else {

        //     if ($attendance) {
        //         return Inertia::render('qrcode/scanned', [
        //             'division' => $qrCode->division,
        //             'shift' => $qrCode->shift,
        //             'message' => 'Kamu sudah melakukan check-in hari ini.',
        //         ]);
        //     }

        //     // $status = $this->attendStatus($qrCode);
            
        //     Attendance::create([
        //         'name' => $user->name,
        //         'qrcode' => $qrCode->token,
        //         'shift' => $qrCode->shift,
        //         'division' => $qrCode->division,
        //         'date' => now()->toDateString(),
        //         'check_in' => Carbon::now(),
        //         'status' => $status,
        //     ]);

        //     // if (Carbon::now()->diffInMinutes(Shift::where('name', $qrCode->shift)->first()->start_time) < -30) {
        //     //     $notice = new Notice([
        //     //         'title' => $user->name . ' Terlambat (' . intval(Carbon::now()->diffInMinutes(Shift::where('name', $qrCode->shift)->first()->start_time, true)) . ' menit)',
        //     //         'content' => Carbon::now(),
        //     //         'link' => 'https://youtube.com',
        //     //         'telegramid' => Config::get('services.telegram_id'),
        //     //     ]);
        //     //     $notice->save();
        //     //     $notice->notify(new TelegramNotif());
        //     // }

        // }

        $qrCode->update(['is_used' => true]);
        
        return Inertia::render('qrcode/scanned', [
            // 'division' => $qrCode->division,
            // 'shift' => $qrCode->shift,
            'message' =>  $user->name . ' Berhasil melakukan ' . $tipe[$qrCode->type],
            'type' => $qrCode->type,
            'success' => true,
        ]);
    }

    // public function attendStatus($qrCode) {
    //     $shift = Shift::where('name', $qrCode->shift)->first();

    //     if (!$shift) {
    //         abort(404, 'Shift tidak ditemukan');
    //     }
    //     if (now()->gt($shift->start_time)) {
    //         return 'late';
    //     }

    //     return 'present';
    // }

    private function getDistanceInMeters( float $lat1, float $lon1, 
    float $lat2, float $lon2): float {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}
