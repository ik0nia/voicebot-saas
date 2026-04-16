<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Notifications\TeamInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index()
    {
        $members = User::where('tenant_id', auth()->user()->tenant_id)
            ->with('roles')
            ->latest()
            ->get();

        return view('dashboard.team.index', compact('members'));
    }

    public function invite(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:tenant_admin,tenant_manager,tenant_viewer',
        ]);

        $tempPassword = Str::random(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($tempPassword),
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        $user->assignRole($validated['role']);

        AdminAuditLog::record('team.invite', $user, [
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        try {
            $user->notify(new TeamInvitation(auth()->user(), $tempPassword));
        } catch (\Exception $e) {
            // Notification send failed, but user was created
        }

        return back()->with('success', "Invitație trimisă către {$validated['email']}.");
    }

    public function updateRole(Request $request, User $user)
    {
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => 'required|in:tenant_admin,tenant_manager,tenant_viewer',
        ]);

        $previousRoles = $user->roles->pluck('name')->toArray();
        $user->syncRoles([$validated['role']]);

        AdminAuditLog::record('team.role_change', $user, [
            'role' => [implode(',', $previousRoles), $validated['role']],
        ]);

        return back()->with('success', 'Rolul a fost actualizat.');
    }

    public function remove(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Nu te poți elimina pe tine.');
        }
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        // Record before delete so the audit row carries the target's
        // email / role (post-delete the model is gone).
        AdminAuditLog::record('team.remove', $user, [
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
        ]);

        $user->delete();

        return back()->with('success', 'Membrul a fost eliminat.');
    }
}
