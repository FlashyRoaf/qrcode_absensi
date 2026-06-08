<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use App\Models\WeeklyReport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\WeeklyReportExport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GenerateWeeklyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-weekly-report {--week-start= : Tanggal Senin akhir minggu (Y-m-d)}
    {--send-wa : Kirim hasil report ke grup WA}';

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
        $weekOption = $this->option('week-start');

        $weekStart = null;
        $weekEnd = null;
        // $lastSaturday = $today->copy()->subDay();

        // Tentukan minggu yang akan di-generate
        if ($weekOption) {
            try {
                $dateFormat = Carbon::createFromFormat('j-n-Y', $weekOption);

                if ($dateFormat->format('j-n-Y') !== $weekOption) {
                    throw new Exception();
                }

                if (!$dateFormat->isMonday()) {
                    $this->error('--week-start harus hari Senin.');
                    return self::FAILURE;
                }

                // Manual: dari parameter
                $weekStart = Carbon::parse($weekOption)->startOfDay();
                $weekEnd = $weekStart->copy()->endOfWeek(6);
            } catch (Exception $e) {
                $this->error('Format tanggal tidak valid. Gunakan format d-m-Y');
                return self::FAILURE;
            }
        } else {
            if ($today->isSunday()) {
                $weekStart = $today->copy()->startOfWeek(1);
                $weekEnd = $today->copy()->addDays(5);
            } else {
                // Auto: minggu lalu (Senin - Sabtu)
                $weekStart = $today->copy()->subWeek()->startOfWeek(1);
                $weekEnd = $today->copy()->subWeek()->endOfWeek(6);
            }
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
                if ($user->role === 'scan') continue;

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

                $this->info("\nTotal menit {$user->name} : {$totalMinutes}");
                // $totalHours = $totalMinutes / 60;
                $targetMinutes = 14.5 * 60; // 870 menit
                // $targetHours = 14.5;
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

        if ($this->option('send-wa')) {
            // Generate dan kirim Excel ke WA
            $reports = WeeklyReport::with('user')
                ->where('week_start', $weekStart->toDateString())
                ->get()
                ->map(fn($r) => [
                    'user_id'       => $r->user_id,
                    'user_name'     => $r->user->name ?? null,
                    'week_start'    => $r->week_start,
                    'total_minutes' => $r->total_minutes,
                    'status'        => $r->status,
                ]);
    
            $filters = ['week_start' => $weekStart->toDateString()];
            $filename = 'weekly-report-' . $weekStart->format('Y-m-d') . '.xlsx';
    
            // Pastikan folder ada
            Storage::makeDirectory('reports');
            chmod(storage_path('app/private/reports'), 0755);
    
            // Simpan file
            Excel::store(new WeeklyReportExport($reports, $filters), 'reports/' . $filename);
    
            // Kirim ke WA bot
            $filePath = config('services.wa_bot.storage_path') . '/' . $filename;
            $groupId = config('services.wa_bot.group_id');
    
            Http::post(
                config('services.wa_bot.url') . '/send-file',
                [
                    'groupId'  => $groupId,
                    'filePath' => $filePath,
                    'filename' => $filename,
                    'caption'  => '📊 Weekly Report ' . $weekStart->format('d/m/Y') . ' - ' . $weekEnd->format('d/m/Y'),
                ]
            );
    
            $this->info('📤 Weekly report terkirim ke grup WA!');
        }

    }
}
