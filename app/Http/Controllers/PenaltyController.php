<?php

namespace App\Http\Controllers;

use App\Models\Penalty;
use App\Models\PenaltyExemptWeek;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PenaltyController extends Controller
{
    // Server-side tab filter and pagination. Counts cover all of
    // the user's non-exempt penalties, not just the current page.
    public function index(Request $request): Response
    {
        $tab = (string) $request->input('tab', 'pending');

        $base = Penalty::where('user_id', Auth::id())
            ->where('status', '!=', 'exempted');

        $counts = [
            'pending' => (int) (clone $base)->where('status', 'pending')->count(),
            'uploaded' => (int) (clone $base)->where('status', 'uploaded')->count(),
            'approved' => (int) (clone $base)->where('status', 'approved')->count(),
            'rejected' => (int) (clone $base)->where('status', 'rejected')->count(),
            'total' => (int) (clone $base)->count(),
        ];

        $query = Penalty::where('user_id', Auth::id())
            ->where('status', '!=', 'exempted')
            ->with(['weeklyReport:id,week_start,total_minutes']);

        if ($tab !== 'all') {
            $query->where('status', $tab);
        }

        $penalties = $query->orderByDesc('id')->paginate(20)->withQueryString();

        // Explicit data+meta shape (see AttendanceController):
        // paginator toArray() flattens per_page/total at the top
        // level instead of nesting them under 'meta'.
        return Inertia::render('Penalty', [
            'penalties' => [
                'data' => $penalties->items(),
                'meta' => [
                    'current_page' => $penalties->currentPage(),
                    'last_page' => $penalties->lastPage(),
                    'total' => $penalties->total(),
                    'per_page' => $penalties->perPage(),
                    'from' => $penalties->firstItem(),
                    'to' => $penalties->lastItem(),
                ],
            ],
            'counts' => $counts,
            'filters' => ['tab' => $tab],
        ]);
    }

    // ── User: upload bukti ────────────────────────────────────────────────────
    public function upload(Request $request, Penalty $penalty)
    {
        // Pastikan hanya milik user sendiri
        if ($penalty->user_id !== Auth::id()) {
            abort(403);
        }

        // Pastikan status masih bisa upload
        if (!in_array($penalty->status, ['pending', 'rejected'])) {
            return back()->withErrors(['proof' => 'Hukuman ini tidak dapat diupload buktinya.']);
        }

        $request->validate([
            'proof' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:' . (int) config('staks.proof_upload_max_kb', 1024),
            ],
        ], [
            'proof.required' => 'Bukti wajib diupload.',
            'proof.image'    => 'File harus berupa gambar.',
            'proof.mimes'    => 'Format file harus JPG, JPEG, atau PNG.',
            'proof.max'      => 'Ukuran file maksimal 1 MB.',
        ]);

        // Hapus bukti lama kalau ada (baru di disk private, lama mungkin
        // masih di disk public dari sebelum perubahan ini).
        if ($penalty->proof_path) {
            Storage::disk('local')->delete($penalty->proof_path);
            Storage::disk('public')->delete($penalty->proof_path);
        }

        // Simpan bukti baru di disk private, BUKAN public: bukti tidak
        // boleh bisa dibuka lewat URL /storage/... tanpa login.
        // Bukti dilihat lewat route penalties.proof di bawah, yang
        // memeriksa pemilik/admin terlebih dahulu.
        $path = $request->file('proof')->store('penalties', 'local');

        $penalty->update([
            'proof_path'       => $path,
            'status'           => 'uploaded',
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Bukti berhasil diupload. Menunggu review admin.');
    }

    // Server-side tab, search, date and sort filters + pagination.
    // Counts cover all non-exempt penalties, not just the page.
    public function adminIndex(Request $request): Response
    {
        $tab = (string) $request->input('tab', 'all');
        $search = (string) $request->input('search', '');
        $filterDate = (string) $request->input('date', '');
        $sortColumn = (string) $request->input('sort', 'id');
        $sortOrder = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $base = Penalty::where('status', '!=', 'exempted');

        $counts = [
            'pending' => (int) (clone $base)->where('status', 'pending')->count(),
            'uploaded' => (int) (clone $base)->where('status', 'uploaded')->count(),
            'approved' => (int) (clone $base)->where('status', 'approved')->count(),
            'rejected' => (int) (clone $base)->where('status', 'rejected')->count(),
            'total' => (int) (clone $base)->count(),
        ];

        $query = Penalty::where('status', '!=', 'exempted')
            ->with(['user:id,name', 'weeklyReport:id,week_start']);

        if ($tab !== 'all') {
            $query->where('status', $tab);
        }

        if ($search !== '') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
        }

        if ($filterDate !== '') {
            // A penalty's week runs Monday-Saturday; include it if the
            // chosen date falls in that week.
            $date = Carbon::parse($filterDate);
            $query->whereHas('weeklyReport', function ($q) use ($date) {
                $q->whereDate('week_start', '<=', $date->toDateString())
                    ->whereDate('week_start', '>=', $date->copy()->subDays(5)->toDateString());
            });
        }

        $sortMap = [
            'id' => 'penalties.id',
            'week' => 'weekly_reports.week_start',
        ];

        if ($sortColumn === 'name') {
            $query->leftJoin('users', 'users.id', '=', 'penalties.user_id')
                ->select('penalties.*')
                ->orderBy('users.name', $sortOrder);
        } else {
            $sortCol = $sortMap[$sortColumn] ?? 'penalties.id';
            if ($sortColumn === 'week') {
                $query->leftJoin('weekly_reports', 'weekly_reports.id', '=', 'penalties.weekly_report_id')
                    ->select('penalties.*')
                    ->orderBy($sortCol, $sortOrder);
            } else {
                $query->orderBy($sortCol, $sortOrder);
            }
        }

        $penalties = $query->paginate(20)->withQueryString();
        $exemptWeeks = PenaltyExemptWeek::all();

        return Inertia::render('admin/Penalty', [
            'penalties' => [
                'data' => $penalties->items(),
                'meta' => [
                    'current_page' => $penalties->currentPage(),
                    'last_page' => $penalties->lastPage(),
                    'total' => $penalties->total(),
                    'per_page' => $penalties->perPage(),
                    'from' => $penalties->firstItem(),
                    'to' => $penalties->lastItem(),
                ],
            ],
            'counts' => $counts,
            'exemptWeeks' => $exemptWeeks,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
                'date' => $filterDate,
                'sort' => $sortColumn,
                'dir' => $sortOrder,
            ],
        ]);
    }

    // ── Lihat bukti (hanya pemilik atau admin) ─────────────────────────────
    // Proof files are NOT on the public disk, so this is the only way to
    // view one. A direct /storage/... link must not work for new uploads.
    public function proof(Penalty $penalty)
    {
        $user = Auth::user();

        if ($penalty->user_id !== $user->id && !$user->is_admin) {
            abort(403);
        }

        if (!$penalty->proof_path) {
            abort(404);
        }

        // New uploads live on the private disk; older uploads may still
        // be on the public disk, but they are still served only here,
        // after the check above.
        if (Storage::disk('local')->exists($penalty->proof_path)) {
            return Storage::disk('local')->response($penalty->proof_path);
        }

        if (Storage::disk('public')->exists($penalty->proof_path)) {
            return Storage::disk('public')->response($penalty->proof_path);
        }

        abort(404);
    }

    // ── Admin: setujui bukti ──────────────────────────────────────────────────
    public function approve(Penalty $penalty)
    {
        if ($penalty->status !== 'uploaded') {
            return back()->withErrors(['error' => 'Hukuman ini tidak dalam status uploaded.']);
        }

        $penalty->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Bukti berhasil disetujui.');
    }

    // ── Admin: tolak bukti ────────────────────────────────────────────────────
    public function reject(Request $request, Penalty $penalty)
    {
        if ($penalty->status !== 'uploaded') {
            return back()->withErrors(['error' => 'Hukuman ini tidak dalam status uploaded.']);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'Alasan penolakan wajib diisi.',
            'reason.max'      => 'Alasan maksimal 500 karakter.',
        ]);

        // Hapus bukti yang ditolak (cek kedua disk: baru di private,
        // lama mungkin masih di public).
        if ($penalty->proof_path) {
            Storage::disk('local')->delete($penalty->proof_path);
            Storage::disk('public')->delete($penalty->proof_path);
        }

        $penalty->update([
            'status'           => 'rejected',
            'proof_path'       => null,
            'rejection_reason' => $request->reason,
            'approved_at'      => null,
        ]);

        return back()->with('success', 'Bukti ditolak. User perlu upload ulang.');
    }

    // ── Admin: tambah minggu libur ────────────────────────────────────────────
    public function addExemptWeek(Request $request)
    {
        $request->validate([
            'week_start' => 'required|date|date_format:Y-m-d',
            'reason'     => 'nullable|string|max:255',
        ], [
            'week_start.required'    => 'Tanggal wajib diisi.',
            'week_start.date'        => 'Format tanggal tidak valid.',
            'week_start.date_format' => 'Format tanggal harus Y-m-d.',
        ]);

        // Cek apakah sudah ada
        $weekStart = Carbon::parse($request->week_start)->toDateString();

        $exists = PenaltyExemptWeek::whereDate('week_start', $weekStart)->exists();
        if ($exists) {
            return back()->withErrors(['week_start' => 'Minggu ini sudah ditambahkan sebelumnya.']);
        }

        PenaltyExemptWeek::create([
            'week_start' => $request->week_start,
            'reason'     => $request->reason,
        ]);

        // Update semua penalty yang minggu tersebut jadi exempt
        Penalty::whereHas('weeklyReport', function ($q) use ($weekStart) {
            $q->whereDate('week_start', $weekStart);
        })->whereIn('status', ['pending', 'uploaded'])
            ->update(['status' => 'exempted']);

        return back()->with('success', 'Minggu libur berhasil ditambahkan.');
    }

    // ── Admin: hapus minggu libur ─────────────────────────────────────────────
    public function deleteExemptWeek(PenaltyExemptWeek $week)
    {
        // Kembalikan penalty exempt ke pending
        Penalty::whereHas('weeklyReport', function ($q) use ($week) {
            $q->whereDate('week_start', $week->week_start);
        })->where('status', 'exempted')
            ->update(['status' => 'pending']);

        $week->delete();
        return back()->with('success', 'Minggu libur berhasil dihapus.');
    }
}
