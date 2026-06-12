<?php

namespace App\Console\Commands;

use App\Models\Penalty;
use Carbon\Carbon;
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
        $this->info('🗑️  Memulai hapus bukti hukuman lama...');

        $oneMonthAgo = Carbon::now()->subMonth();

        // Ambil penalty yang approved/rejected lebih dari 1 bulan
        $penalties = Penalty::whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('proof_path')
            ->where('updated_at', '<', $oneMonthAgo)
            ->get();

        $this->info("📋 Ditemukan {$penalties->count()} bukti yang perlu dihapus.");

        $deleted = 0;
        $failed  = 0;

        foreach ($penalties as $penalty) {
            try {
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
