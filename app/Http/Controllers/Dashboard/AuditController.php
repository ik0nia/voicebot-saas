<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * Tenant-facing audit log viewer.
 *
 * Read-only. Listează toate intrările audit pentru tenantul curent,
 * cu filtre pe acțiune și utilizator. Doar tenant_admin / super_admin
 * pot accesa (vezi route middleware).
 */
class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with(['user'])
            ->latest('id');

        if ($action = $request->get('action')) {
            $query->where('action', 'like', $action . '%');
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', (int) $userId);
        }

        $logs = $query->paginate(50)->withQueryString();

        // Pentru filtru: cei care au generat audit pentru acest tenant
        $users = AuditLog::query()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        $users = \App\Models\User::whereIn('id', $users)->orderBy('name')->get(['id', 'name']);

        // Lista de acțiuni distincte pentru filtru
        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('dashboard.audit.index', compact('logs', 'users', 'actions'));
    }
}
