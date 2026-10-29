<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeleteExpireQrcodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-expire-qrcodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus QR code yang sudah lebih dari batas hari di config staks (default 3 hari)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Cache::put('schedule_last_run_at', now()->toIso8601String());

        $deleted = DB::table('qrcodes')
            ->where('created_at', '<', Carbon::now()->subDays((int) config('staks.qr_cleanup_days', 3)))
            ->delete();

        $this->info("Berhasil hapus {$deleted} QR code kadaluarsa.");
    }
}
