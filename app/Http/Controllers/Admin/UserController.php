<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    //
    public function index(): Response
    {
        // $users = User::select('id', 'username', 'email', 'birthdate', 'newsletter', 'newsletter_consent_at', 'is_admin', 'admin_role', 'admin_assigned_at')
        //     ->orderBy('created_at', 'desc')
        //     ->get();
        $users = User::all();

        return Inertia::render('admin/Crud', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        // $request->validate([
        //     'username' => 'required|string|max:255',
        //     'email' => 'required|string|email|max:255|unique:users',
        //     'password' => ['required', 'confirmed', Rules\Password::defaults()],
        //     'role' => 'required|string|in:admin,user',
        // ]);

        $request->validate([
            'name' => 'required|string|max:255|unique:'.User::class,
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // 'birthdate' => 'required|date|max:255',
            // 'newsletter' => 'nullable|boolean',
            'is_admin' => 'required|boolean',
            'role' => 'nullable|string|max:255',
        ]);

        // User::create([
        //     'username' => $request->username,
        //     'email' => $request->email,
        //     'password' => Hash::make($request->password),
        //     'role' => $request->role,
        // ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // 'birthdate' => $request->birthdate,
            // 'newsletter' => $request->boolean('newsletter'),
            // 'newsletter_consent_at' => $request->boolean('newsletter') ? now() : null,
            'is_admin' => $request->boolean('is_admin'),
            'role' => $request->role,
            'admin_assigned_at' => $request->boolean('is_admin') ? now() : null
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Akun berhasil ditambahkan');
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        // $rules = [
        //     'username' => 'required|string|max:255',
        //     'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        //     'role' => 'required|string|in:admin,user',
        // ];


        $rules = [
            // 'username' => 'required|string|max:255|unique:'.User::class,
            'name' => 'required|string|max:255|unique:users,name,'.$user->id,
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,'.$user->id,
            // 'birthdate' => 'required|date|max:255',
            // 'newsletter' => 'nullable|boolean',
            'is_admin' => 'required|boolean',
            'role' => 'nullable|string|max:255',
        ];

        // Only validate password if it's provided
        if ($request->filled('password')) {
            $rules['password'] = ['confirmed', Rules\Password::defaults()];
        }

        $request->validate($rules);

        // $updateData = [
        //     'username' => $request->username,
        //     'email' => $request->email,
        //     'role' => $request->role,
        // ];

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            // 'birthdate' => $request->birthdate,
            // 'newsletter' => $request->boolean('newsletter'),
            // 'newsletter_consent_at' => $request->boolean('newsletter') ? now() : null,
            'is_admin' => $request->boolean('is_admin'),
            'role' => $request->role,
            'admin_assigned_at' => $request->boolean('is_admin') ? now() : null
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return redirect()->route('dashboard')
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

        return redirect()->route('dashboard')
            ->with('success', 'Akun berhasil dihapus');
    }

    public function resetDevice(User $user) {
        $user->update([
            'device_id' => null,
            'identifier' => null,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Device berhasil direset. Pengguna dapat login kembali dari perangkat baru.');
    }

}
