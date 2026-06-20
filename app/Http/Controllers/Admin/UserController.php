<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    //
    public function index(): Response
    {
        // $users = User::all();
        $users = User::with(['leaves' => function ($query) {
            $query->where('end_date', '>=', now()->format('Y-m-d'));
        }])->get();

        return Inertia::render('admin/Crud', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:'.User::class,
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => 'nullable|string|max:255',
            'is_admin' => 'required|boolean',
            'role' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->boolean('is_admin'),
            'role' => $request->role,
            'phone' => $request->phone,
            'admin_assigned_at' => $request->boolean('is_admin') ? now() : null
        ]);

        return redirect()->route('users')
            ->with('success', 'Akun berhasil ditambahkan');
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:users,name,'.$user->id,
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:255',
            'is_admin' => 'required|boolean',
            'role' => 'nullable|string|max:255',
        ];

        // Only validate password if it's provided
        if ($request->filled('password')) {
            $rules['password'] = ['confirmed', Rules\Password::defaults()];
        }

        $request->validate($rules);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'is_admin' => $request->boolean('is_admin'),
            'role' => $request->role,
            'phone' => $request->phone,
            'admin_assigned_at' => $request->boolean('is_admin') ? now() : null
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return redirect()->route('users')
            ->with('success', 'Akun berhasil diperbarui');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent deleting the currently authenticated user
        if ($user->id === Auth::user()->id) {
            return redirect()->route('dashboard')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri');
        }

        $user->delete();

        return redirect()->route('users')
            ->with('success', 'Akun berhasil dihapus');
    }

    public function resetDevice(User $user) {
        $user->update([
            'device_id' => null,
            'identifier' => null,
        ]);

        return redirect()->route('users')
            ->with('success', 'Device berhasil direset. Pengguna dapat login kembali dari perangkat baru.');
    }

    public function setLeave(Request $request, User $user)
    {
        $request->validate([
            'weeks' => 'required|integer|min:1|max:12', // batasi misal maks 12 minggu
        ]);

        // Hitung Senin terdekat (bisa Senin minggu ini jika hari ini Senin, atau Senin depan)
        // Jika ingin selalu dihitung dari Senin minggu berjalan, gunakan startOfWeek()
        $start_date = Carbon::now()->startOfWeek(1);
        
        // Hitung hari Sabtu setelah X minggu
        $end_date = $start_date->copy()->addWeeks($request->weeks - 1)->next(6);

        // Hapus izin aktif sebelumnya (jika ada) untuk menghindari duplikasi tumpah tindih
        $user->leaves()->where('end_date', '>=', now()->format('Y-m-d'))->delete();

        // Simpan izin baru
        $user->leaves()->create([
            'start_date' => $start_date->format('Y-m-d'),
            'end_date' => $end_date->format('Y-m-d'),
        ]);

        return redirect()->back()->with('success', "Izin berhasil diset hingga {$end_date->format('d M Y')}.");
    }

    /**
     * Membatalkan/Menghapus izin yang sedang berjalan
     */
    public function removeLeave(User $user)
    {
        // Hapus izin aktif
        $user->leaves()->where('end_date', '>=', now()->format('Y-m-d'))->delete();

        return redirect()->back()->with('success', 'Izin berhasil dibatalkan.');
    }
    
}
