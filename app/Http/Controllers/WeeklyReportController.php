<?php

namespace App\Http\Controllers;

use App\Models\WeeklyReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Exports\WeeklyReportExport;
use Maatwebsite\Excel\Facades\Excel;

class WeeklyReportController extends Controller
{
    //
    public function show(): Response {        
        $reports = WeeklyReport::with('user')
        ->select('user_id', 'week_start', 'total_minutes', 'status')
        ->get()
        ->map(fn($r) => [
            'user_id'       => $r->user_id,
            'user_name'     => $r->user->name ?? null,
            'week_start'    => $r->week_start,
            'total_minutes' => $r->total_minutes,
            'status'        => $r->status,
        ]);

        return Inertia::render('admin/WeeklyReport', compact('reports'));
        
        // return Inertia::render('admin/WeeklyReport', [
        //     'reports' => WeeklyReport::all(),
        // ]);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
{
    // Ambil data
    $reports = WeeklyReport::with('user')
        ->get()
        ->map(fn($r) => [
            'user_id'       => $r->user_id,
            'user_name'     => $r->user->name ?? null,
            'week_start'    => $r->week_start,
            'total_minutes' => $r->total_minutes,
            'status'        => $r->status,
        ]);

    // Terima filter dari request
    $filters = $request->only(['search', 'status', 'week_start']);

    $filename = 'weekly-report-' . now()->format('Y-m-d_His') . '.xlsx';

    // Perbaikan: Hapus collect() karena $reports sudah berupa Collection
    return Excel::download(
        new WeeklyReportExport($reports, $filters), 
        $filename
    );
}
}