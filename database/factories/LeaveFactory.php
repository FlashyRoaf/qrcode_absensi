<?php

namespace Database\Factories;

use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Leave>
 */
class LeaveFactory extends Factory
{
    protected $model = Leave::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 1. Pilih awal mulai izin (Senin)
        // Kita samakan dengan attendance, yaitu antara 2 minggu lalu, minggu lalu, atau minggu ini.
        $weeksOffset = rand(1, 2); 
        $startOfWeek = Carbon::now()->startOfWeek(1)->subWeeks($weeksOffset);

        // 2. Tentukan durasi izin (antara 1 sampai 3 minggu secara random)
        $durationWeeks = rand(1, 3);

        // 3. Hitung tanggal selesai izin (Sabtu di akhir minggu durasi)
        $endOfWeek = $startOfWeek->copy()->addWeeks($durationWeeks - 1)->next(6);

        return [
            // Ambil user secara random
            'user_id' => User::inRandomOrder()->first()->id,
            
            // Format tanggal (Y-m-d) karena tipe datanya adalah date
            'start_date' => $startOfWeek->format('Y-m-d'),
            'end_date'   => $endOfWeek->format('Y-m-d'),
        ];
    }
}
