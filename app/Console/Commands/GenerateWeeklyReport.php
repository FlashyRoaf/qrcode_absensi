<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use App\Models\WeeklyReport;
use Carbon\Carbon;

class GenerateWeeklyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-weekly-report {--week-start= : Tanggal Senin akhir minggu (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate weekly reports untuk semua user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $this->info('🚀 Memulai generate weekly reports...');
        $this->newLine();

        $today = Carbon::now();
        // $lastSaturday = $today->copy()->subDay();

        // Tentukan minggu yang akan di-generate
        if ($this->option('week-start')) {
            // Manual: dari parameter
            $weekStart = Carbon::parse($this->option('week-start'))->startOfDay();
            $weekEnd = $weekStart->copy()->endOfWeek(6);
        } else {
            // Auto: minggu lalu (Senin - Minggu)
            $weekStart = $today->copy()->subWeek()->startOfWeek(1);
            $weekEnd = $today->copy()->subWeek()->endOfWeek(6);
        }


        $this->info("📅 Periode: {$weekStart->format('d M Y')} - {$weekEnd->format('d M Y')}");
        $this->newLine();

        // Ambil semua user
        $users = User::all();
        $this->info("👥 Total user: {$users->count()}");
        $this->newLine();

        $successCount = 0;
        $skipCount = 0;
        $errorCount = 0;

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            try {
                // Cek apakah report untuk minggu ini sudah ada
                $existingReport = WeeklyReport::where('user_id', $user->id)
                    ->where('week_start', $weekStart->toDateString())
                    ->first();

                if ($existingReport) {
                    $skipCount++;
                    $progressBar->advance();
                    continue;
                }

                // Hitung total menit kerja user untuk minggu ini
                $totalMinutes = Attendance::where('user_id', $user->id)
                    ->whereBetween('date', [
                        $weekStart->toDateString(),
                        $weekEnd->toDateString()
                    ])
                    ->sum('duration_minutes');

                $this->info("Total menit {$user->name} : {$totalMinutes}");
                // $totalHours = $totalMinutes / 60;
                $targetMinutes = 16 * 60; // 960 menit
                // $targetHours = 16;
                $isTargetMet = $totalMinutes >= $targetMinutes;
                // $percentage = min(100, ($totalMinutes / $targetMinutes) * 100);
                $status = $isTargetMet ? 'memenuhi' : 'tidak_memenuhi';

                // Buat weekly report
                WeeklyReport::create([
                    'user_id' => $user->id,
                    'week_start' => $weekStart->toDateString(),
                    'total_minutes' => $totalMinutes,
                    'status' => $status,
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $this->error("Error untuk user {$user->id}: {$e->getMessage()}");
                $errorCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Generate selesai!');
        $this->newLine();
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Berhasil dibuat', $successCount],
                ['Dilewati (sudah ada)', $skipCount],
                ['Error', $errorCount],
            ]
        );
    }
}
