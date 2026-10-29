<?php

namespace App\Console\Commands;

use App\Models\WeeklyReport;
use App\Services\PenaltyService;
use Illuminate\Console\Command;

// Manual backfill only. Weekly reports already create penalties
// through PenaltyService when they are generated, so this command
// is deliberately NOT in the scheduler. Run it by hand only when a
// penalty was missed (for example, a report created before the
// penalty rule existed).
class GeneratePenalty extends Command
{
    protected $signature = 'app:generate-penalty';

    protected $description = 'Manual only: backfill hukuman untuk weekly report yang tidak memenuhi (tidak dijadwalkan; generator laporan mingguan sudah membuat hukuman sendiri)';

    public function handle()
    {
        $this->warn('Perintah manual: generator laporan mingguan sudah membuat hukuman sendiri. Perintah ini hanya untuk backfill yang terlewat.');
        $this->newLine();
        $this->info('Memulai generate hukuman...');
        $this->newLine();

        $reports = WeeklyReport::all();
        $this->info("Total report: {$reports->count()}");
        $this->newLine();

        $created = 0;
        $skipped = 0;

        foreach ($reports as $report) {
            try {
                if (PenaltyService::createIfNeeded($report)) {
                    $created++;
                    $this->line("✓ Penalty dibuat untuk User #{$report->user_id} (Report #{$report->id})");
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->error(
                    "✗ Gagal memproses Report #{$report->id}: {$e->getMessage()}"
                );
            }
        }

        $this->newLine();
        $this->info('Selesai.');
        $this->info("Penalty dibuat : {$created}");
        $this->info("Dilewati       : {$skipped}");
    }
}
