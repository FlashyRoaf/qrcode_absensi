<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startOfWeek = now()->startOfWeek(); // Senin

    // Random hari: 0–5 (Senin–Sabtu)
    $randomDay = rand(0, 5);
    $date = $startOfWeek->copy()->addDays($randomDay);

    // Random jam check-in (08:00 - 17:00)
    $checkIn = $date->copy()->setTime(rand(8, 17), rand(0, 59));

    // Random durasi (30 - 240 menit)
    $duration = rand(30, 240);

    $checkOut = $checkIn->copy()->addMinutes($duration);

    return [
        'user_id' => User::inRandomOrder()->first()->id,
        'qrcode' => fake()->uuid(),
        'date' => $date->toDateString(),
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'duration_minutes' => $duration,
    ];
    }
}
