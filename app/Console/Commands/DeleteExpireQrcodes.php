<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
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
    protected $description = 'Hapus QR code yang sudah lebih dari 3 hari';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $deleted = DB::table('nama_tabel_qrcode')
            ->where('created_at', '<', Carbon::now()->subDays(3))
            ->delete();

        $this->info("Berhasil hapus {$deleted} QR code kadaluarsa.");
    }
}
