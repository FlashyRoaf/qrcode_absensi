<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $botUrl;
    protected string $botSecret;

    public function __construct()
    {
        $this->botUrl = rtrim(config('services.wa_bot.url', 'http://localhost:3000'), '/');
        $this->botSecret = (string) config('services.wa_bot.secret', '');
    }

    // Never log a full phone number: keep only the last 4 digits, so
    // the logs cannot be used to harvest employee numbers.
    private static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) <= 4) {
            return '****';
        }

        return '****' . substr($digits, -4);
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
        string $weeklyDurasi = '0 menit',
        string $weeklyTarget = '14 jam 30 menit'
    ): bool {
        try {
            // The bot rejects requests without this shared secret, so
            // anyone else who finds the bot's port cannot send messages
            // as the company. Set the same WA_BOT_SECRET in Laravel's
            // .env and in wa-bot/.env.
            $response = Http::timeout(5)
                ->withHeaders(['X-WA-Secret' => $this->botSecret])
                ->post("{$this->botUrl}/send-notification", [
                'phone'        => $phone,
                'name'         => $name,
                'type'         => $type,
                'time'         => $time,
                'weeklyDurasi' => $weeklyDurasi,
                'weeklyTarget' => $weeklyTarget,
            ]);

            if ($response->successful()) {
                Log::info("WA notifikasi terkirim ke " . self::maskPhone($phone) . " ({$type})");
                return true;
            }

            Log::warning("WA notifikasi gagal ke " . self::maskPhone($phone) . ": " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("WA service error: " . $e->getMessage());
            return false;
        }
    }
}