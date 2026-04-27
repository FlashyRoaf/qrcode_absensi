<?php

namespace Database\Seeders;

use App\Models\Attendance;
// use App\Models\Division;
// use App\Models\Shift;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();
        // try {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_admin' => true,
            'role' => 'admin',
            // 'shift' => 'Siang',
            // 'division' => 'Programmer'

        ]);

        $this->call([
            AttendanceSeeder::class,
        ]);

        // Shift::insert([
        //     [
        //         'name' => 'Pagi',
        //         'start_time' => '09:00:00',
        //         'end_time' => '18:00:00',
        //     ],
        //     [
        //         'name' => 'Siang',
        //         'start_time' => '11:00:00',
        //         'end_time' => '20:00:00',
        //     ],
        // ]);

        // Division::insert([
        //     [
        //         'name' => 'Gudang',
        //     ],
        //     [
        //         'name' => 'Keuangan',
        //     ],
        //     [
        //         'name' => 'Programmer',
        //     ],
        //     [
        //         'name' => 'Satpam',
        //     ],
        // ]);
        // } catch (\Throwable $e) {
        //     dd($e->getMessage(), $e->getTraceAsString());
        // }

    }
}
