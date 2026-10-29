<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Exports\WeeklyReportExport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class WeeklyReportController extends Controller
{
    // Server-side search, filter, sort and pagination for the table
    // tab. The stat cards, per-user tab and trend tab used to be
    // computed in the browser from the full list, so those aggregates
    // are now computed here from all reports and sent separately —
    // they must not be based on just the current page.
    public function show(Request $request): Response
    {
        $search = (string) $request->input('search', '');
        $statusFilter = (string) $request->input('status', 'all');
        $weekFilter = (string) $request->input('week_start', '');
        $sortKey = (string) $request->input('sort', 'week_start');
        $sortDir = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        // All reports, mapped once, for stats / per-user / trend.
        // Leaves are loaded once and checked in memory, instead of
        // one database query per report row.
        $reportRows = WeeklyReport::with('user')
            ->select('user_id', 'week_start', 'total_minutes', 'status')
            ->get();

        $leavesByUser = Leave::whereIn('user_id', $reportRows->pluck('user_id')->unique())
            ->get()
            ->groupBy('user_id');

        $all = $reportRows->map(function ($r) use ($leavesByUser) {
            $weekStart = Carbon::parse($r->week_start)->startOfDay();
            $weekEnd = $weekStart->copy()->addDays(5)->endOfDay();

            return [
                'user_id' => $r->user_id,
                'user_name' => $r->user->name ?? null,
                'week_start' => $r->week_start,
                'total_minutes' => $r->total_minutes,
                'status' => $r->status,
                'is_on_leave' => Leave::coversFullWeekFromCollection(
                    $leavesByUser->get($r->user_id, collect()), $weekStart, $weekEnd
                ),
            ];
        });

        $memenuhi = $all->where('status', 'memenuhi')->count();
        $tidakMemenuhi = $all->where('status', 'tidak_memenuhi')->count();

        $reportStats = [
            'totalUsers' => $all->unique('user_id')->count(),
            'totalReports' => $all->count(),
            'totalWeeks' => $all->unique('week_start')->count(),
            'memenuhi' => $memenuhi,
            'tidakMemenuhi' => $tidakMemenuhi,
            'complianceRate' => $all->count()
                ? round($memenuhi / $all->count() * 100)
                : 0,
        ];

        $userStats = $all->groupBy('user_id')->map(function ($rows, $userId) {
            $first = $rows->first();

            return [
                'user_id' => (int) $userId,
                'user_name' => $first['user_name'] ?? "User #{$userId}",
                'total' => $rows->count(),
                'memenuhi' => $rows->where('status', 'memenuhi')->count(),
                'tidak_memenuhi' => $rows->filter(fn ($r) => $r['status'] !== 'memenuhi' && !$r['is_on_leave'])->count(),
                'is_on_leave' => $rows->where('is_on_leave', true)->count(),
                'total_minutes' => (int) $rows->sum('total_minutes'),
            ];
        })->values()->sortByDesc('memenuhi')->values();

        $weeklyTrend = $all->groupBy('week_start')->map(function ($rows, $week) {
            return [
                'week' => $week,
                'total' => $rows->count(),
                'memenuhi' => $rows->where('status', 'memenuhi')->count(),
                'tidak' => $rows->filter(fn ($r) => $r['status'] !== 'memenuhi' && !$r['is_on_leave'])->count(),
                'izin' => $rows->where('is_on_leave', true)->count(),
            ];
        })->values()->sortBy('week')->values()->slice(-8)->values();

        // Table data: apply the UI filters, then sort, then paginate.
        // Filtering a paginated collection in memory is acceptable
        // here because weekly reports grow by one row per user per
        // week; the table itself is paged at 10 rows, and the
        // aggregates above are already separate.
        $filtered = $all;

        if ($search !== '') {
            $q = strtolower($search);
            $filtered = $filtered->filter(fn ($r) =>
                str_contains((string) $r['user_id'], $q)
                || str_contains(strtolower($r['user_name'] ?? ''), $q)
                || str_contains((string) $r['week_start'], $q)
            );
        }

        if ($statusFilter !== 'all') {
            if ($statusFilter === 'izin') {
                $filtered = $filtered->where('is_on_leave', true);
            } else {
                $filtered = $filtered->filter(fn ($r) =>
                    $r['status'] === $statusFilter && !$r['is_on_leave']
                );
            }
        }

        if ($weekFilter !== '') {
            $filtered = $filtered->where('week_start', $weekFilter);
        }

        $filtered = $filtered->sort(function ($a, $b) use ($sortKey, $sortDir) {
            $va = $a[$sortKey] ?? null;
            $vb = $b[$sortKey] ?? null;
            if ($sortKey === 'total_minutes') {
                $va = (float) $va;
                $vb = (float) $vb;
            }
            $cmp = $va <=> $vb;

            return $sortDir === 'asc' ? $cmp : -$cmp;
        })->values();

        $perPage = 10;
        $page = max(1, (int) $request->input('page', 1));
        $total = $filtered->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $pageRows = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        // Shape the table page like a Laravel paginator, so the page
        // renders it the same way as the other admin lists.
        $reportsPage = [
            'data' => $pageRows,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'total' => $total,
                'per_page' => $perPage,
                'from' => $total ? ($page - 1) * $perPage + 1 : null,
                'to' => $total ? min($page * $perPage, $total) : null,
            ],
        ];

        // Scheduler warning: scheduled jobs record their last run in
        // cache. If nothing has run in the last 8 days (weekly reports
        // are weekly), the page warns that the cron job may be missing.
        $lastScheduleRun = Cache::get('schedule_last_run_at');
        $scheduleWarning = !$lastScheduleRun
            || Carbon::parse($lastScheduleRun)->lt(now()->subDays(8));

        return Inertia::render('admin/WeeklyReport', [
            'schedule_warning' => $scheduleWarning,
            'schedule_last_run_at' => $lastScheduleRun,
            'reports' => $reportsPage,
            'report_stats' => $reportStats,
            'user_stats' => $userStats,
            'weekly_trend' => $weeklyTrend,
            'target_minutes' => (int) config('staks.target_minutes', 870),
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'week_start' => $weekFilter,
                'sort' => $sortKey,
                'dir' => $sortDir,
            ],
        ]);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
{
    // Ambil data
    $reports = WeeklyReport::with('user.leaves')
        ->get()
        ->map(function ($r) {
            $weekStart = Carbon::parse($r->week_start)->startOfDay();
            $weekEnd = $weekStart->copy()->addDays(5)->endOfDay();

            return [
                'user_id'       => $r->user_id,
                'user_name'     => $r->user->name ?? null,
                'week_start'    => $r->week_start,
                'total_minutes' => $r->total_minutes,
                'status'        => $r->status,
                'is_on_leave'   => Leave::coversFullWeekFromCollection($r->user->leaves ?? collect(), $weekStart, $weekEnd),
            ];
        });
    
    // Terima filter dari request
    $filters = $request->only(['search', 'status', 'week_start']);

    $filename = 'weekly-report-' . now()->format('Y-m-d_His') . '.xlsx';

    // Perbaikan: Hapus collect() karena $reports sudah berupa Collection
    return Excel::download(
        new WeeklyReportExport($reports, $filters, (int) config('staks.target_minutes', 870)),
        $filename
    );
}
}