<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user        = Auth::user();
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek   = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $weekAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get()
            ->groupBy('date');

        $records = collect();

        for ($day = $startOfWeek->copy(); $day->lte($endOfWeek); $day->addDay()) {
            $dateKey    = $day->toDateString();
            $attendance = $weekAttendances->get($dateKey, collect());

            $firstCheckIn = $attendance->sortBy('check_in')->first();

            $lastCheckOut = $attendance->sortByDesc('check_out')->first();

            $totalMinutes = $attendance->sum('duration_minutes');
            
            $records->push([
                'id'           => $dateKey,
                'day'          => $day->locale('id')->isoFormat('dddd'),
                'date'         => $day->locale('id')->isoFormat('D MMM YYYY'),
                'check_in'     => $firstCheckIn?->check_in
                                    ? Carbon::parse($firstCheckIn->check_in)->format('H:i')
                                    : null,
                'check_out'    => $lastCheckOut?->check_out
                                    ? Carbon::parse($lastCheckOut->check_out)->format('H:i')
                                    : null,
                'hours_worked' => $totalMinutes
                                    ? round($totalMinutes / 60, 2)
                                    : 0,
            ]);
        }

        $todayKey     = Carbon::today()->toDateString();
        $todayRecord  = $weekAttendances->get($todayKey);
        $presentToday = $todayRecord && $todayRecord->check_in !== null;

        $totalHours = $records->sum('hours_worked');
        $workedDays = $records->where('hours_worked', '>', 0)->count();
        $avgHours   = $workedDays > 0 ? round($totalHours / $workedDays, 2) : 0;

        return Inertia::render('Dashboard', [
            'records'       => $records->values(),
            'present_today' => $presentToday,
            'avg_hours'     => $avgHours,
            'hours_worked'  => round($totalHours, 2),
            'target_hours'  => 14.5,
        ]);
    }
}