<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $botUrl;

    public function __construct()
    {
        $this->botUrl = rtrim(config('services.wa_bot.url', 'http://localhost:3000'), '/');
    }

    /**
     * Kirim notifikasi absensi ke pekerja via WhatsApp.
     *
     * @param string $phone        Nomor WA pekerja (format 08xx atau 628xx)
     * @param string $name         Nama pekerja
     * @param string $type         'check_in' atau 'check_out'
     * @param string $time         Waktu absensi (format tampilan)
     * @param string $weeklyDurasi Total durasi kerja minggu ini (format tampilan)
     * @return bool
     */
    public function sendAttendanceNotification(
        string $phone,
        string $name,
        string $type,
        string $time,
        string $weeklyDurasi = '0 menit'
    ): bool {
        try {
            $response = Http::timeout(5)->post("{$this->botUrl}/send-notification", [
                'phone'        => $phone,
                'name'         => $name,
                'type'         => $type,
                'time'         => $time,
                'weeklyDurasi' => $weeklyDurasi,
            ]);

            if ($response->successful()) {
                Log::info("WA notifikasi terkirim ke {$phone} ({$type})");
                return true;
            }

            Log::warning("WA notifikasi gagal ke {$phone}: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("WA service error: " . $e->getMessage());
            return false;
        }
    }
}