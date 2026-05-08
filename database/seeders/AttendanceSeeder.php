<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Attendance::factory()->count(150)->create();
        // $users = User::all();

        // foreach ($users as $user) {
        //     Attendance::factory()
        //         ->count(rand(3, 6))
        //         ->create([
        //             'user_id' => $user->id,
        //         ]);
        // }
    }
}
