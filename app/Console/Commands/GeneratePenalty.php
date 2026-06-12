<?php

namespace App\Console\Commands;

use App\Models\Penalty;
use App\Models\WeeklyReport;
use Illuminate\Console\Command;

class GeneratePenalty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-penalty';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate hukuman untuk semua weeklreport user yang tidak memenuhi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $this->info('Memulai generate hukuman...');
        $this->newLine();

        $reports = WeeklyReport::all();
        $this->info("Total report: {$reports->count()}");
        $this->newLine();

        $created = 0;
        $skipped = 0;
        
        foreach ($reports as $report) {
            try {
                if ($report->status !== "tidak_memenuhi") {
                    $skipped++;
                    continue;
                }

                $exists = Penalty::where('user_id', $report->user_id)
                    ->where('weekly_report_id', $report->id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                Penalty::firstOrcreate(
                    [
                        'user_id'          => $report->user_id,
                        'weekly_report_id' => $report->id,
                    ],
                    ['status' => 'pending']
                );

                $created++;
                $this->line("✓ Penalty dibuat untuk User #{$report->user_id} (Report #{$report->id})");
            } catch (\Exception $e) {
                $this->error(
                    "✗ Gagal memproses Report #{$report->id}: {$e->getMessage()}"
                );
            }
        }


        $this->newLine();
        $this->info("Selesai.");
        $this->info("Penalty dibuat : {$created}");
        $this->info("Dilewati       : {$skipped}");
    }
}
