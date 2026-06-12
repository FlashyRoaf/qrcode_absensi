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
    //
    public function index(): Response
    {
        $penalties = Penalty::where('user_id', Auth::id())->with([
            'weeklyReport:id,week_start,total_minutes'
        ])->get();

        return Inertia::render('Penalty', [
            'penalties' => $penalties,
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
                'max:1024', // 1 MB
            ],
        ], [
            'proof.required' => 'Bukti wajib diupload.',
            'proof.image'    => 'File harus berupa gambar.',
            'proof.mimes'    => 'Format file harus JPG, JPEG, atau PNG.',
            'proof.max'      => 'Ukuran file maksimal 1 MB.',
        ]);

        // Hapus bukti lama kalau ada
        if ($penalty->proof_path) {
            Storage::disk('public')->delete($penalty->proof_path);
        }

        // Simpan bukti baru
        $path = $request->file('proof')->store('penalties', 'public');

        $penalty->update([
            'proof_path'       => $path,
            'status'           => 'uploaded',
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Bukti berhasil diupload. Menunggu review admin.');
    }

    public function adminIndex(): Response
    {
        $penalties = Penalty::with([
            'user:id,name',
            'weeklyReport:id,week_start'
        ])->get();

        $exemptWeeks = PenaltyExemptWeek::all();

        return Inertia::render('admin/Penalty', [
            'penalties' => $penalties,
            'exemptWeeks' => $exemptWeeks,
        ]);
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

        // Hapus bukti yang ditolak
        if ($penalty->proof_path) {
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
