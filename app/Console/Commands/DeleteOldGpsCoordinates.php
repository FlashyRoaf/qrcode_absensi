<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

// Privacy retention: exact GPS coordinates are kept only for the
// period in config staks (default 30 days). After that, latitude
// and longitude are deleted and only the distance from the office
// remains on the attendance row.
class DeleteOldGpsCoordinates extends Command
{
    protected $signature = 'app:delete-old-gps-coordinates';

    protected $description = 'Hapus koordinat GPS lama dari absensi (jarak tetap disimpan)';

    public function handle()
    {
        Cache::put('schedule_last_run_at', now()->toIso8601String());

        $cutoff = Carbon::now()->subDays((int) config('staks.gps_retention_days', 30));

        $deleted = Attendance::where('date', '<', $cutoff->toDateString())
            ->whereNotNull('latitude')
            ->update(['latitude' => null, 'longitude' => null]);

        $this->info("Berhasil hapus koordinat GPS dari {$deleted} absensi lama (jarak tetap disimpan).");
    }
}
