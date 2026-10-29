<?php

namespace App\Console\Commands;

use App\Models\Penalty;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;

class DeletePenaltyProof extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-penalty-proof';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus gambar bukti hukuman yang sudah lama';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Cache::put('schedule_last_run_at', now()->toIso8601String());

        $this->info('🗑️  Memulai hapus bukti hukuman lama...');

        $oneMonthAgo = Carbon::now()->subMonths((int) config('staks.proof_retention_months', 1));

        // Ambil penalty yang approved/rejected lebih lama dari batas di config staks
        $penalties = Penalty::whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('proof_path')
            ->where('updated_at', '<', $oneMonthAgo)
            ->get();

        $this->info("📋 Ditemukan {$penalties->count()} bukti yang perlu dihapus.");

        $deleted = 0;
        $failed  = 0;

        foreach ($penalties as $penalty) {
            try {
                // New proofs are on the private disk; older ones may
                // still be on the public disk.
                if (Storage::disk('local')->exists($penalty->proof_path)) {
                    Storage::disk('local')->delete($penalty->proof_path);
                }
                if (Storage::disk('public')->exists($penalty->proof_path)) {
                    Storage::disk('public')->delete($penalty->proof_path);
                }

                $penalty->update(['proof_path' => null]);
                $deleted++;
            } catch (\Exception $e) {
                $this->error("❌ Gagal hapus penalty ID {$penalty->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("✅ Selesai! Berhasil: {$deleted}, Gagal: {$failed}.");
    }
}
