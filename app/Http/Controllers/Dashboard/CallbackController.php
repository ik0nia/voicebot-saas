<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CallbackRequest;
use Illuminate\Http\Request;

class CallbackController extends Controller
{
    public function index(Request $request)
    {
        $tenant = auth()->user()->currentTenant();
        $query = CallbackRequest::where('tenant_id', $tenant->id)
            ->with('bot', 'lead')
            ->orderByDesc('created_at');

        if ($status = $request->input('status')) $query->where('status', $status);
        if ($botId = $request->input('bot_id')) $query->where('bot_id', $botId);

        $callbacks = $query->paginate(25);
        $bots = $tenant->bots()->select('id', 'name')->get();

        $stats = [
            'pending' => CallbackRequest::where('tenant_id', $tenant->id)->where('status', 'pending')->count(),
            'today' => CallbackRequest::where('tenant_id', $tenant->id)->whereDate('preferred_date', today())->count(),
            'total' => CallbackRequest::where('tenant_id', $tenant->id)->count(),
            'completed' => CallbackRequest::where('tenant_id', $tenant->id)->where('status', 'completed')->count(),
        ];

        return view('dashboard.callbacks.index', compact('callbacks', 'bots', 'stats'));
    }

    public function show(CallbackRequest $callback)
    {
        if (!auth()->user()->hasRole('super_admin') && $callback->tenant_id !== auth()->user()->tenant_id) abort(403);
        $callback->load('bot', 'lead', 'conversation');
        return view('dashboard.callbacks.show', compact('callback'));
    }

    public function updateStatus(Request $request, CallbackRequest $callback)
    {
        if (!auth()->user()->hasRole('super_admin') && $callback->tenant_id !== auth()->user()->tenant_id) abort(403);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled,no_answer',
            'internal_notes' => 'nullable|string|max:2000',
            'outcome' => 'nullable|string|max:500',
            'assigned_to' => 'nullable|string|max:255',
        ]);

        $data = ['status' => $validated['status']];
        if ($validated['status'] === 'confirmed') $data['confirmed_at'] = now();
        if ($validated['status'] === 'completed') $data['completed_at'] = now();
        if (!empty($validated['internal_notes'])) {
            $existing = $callback->internal_notes ?? '';
            $data['internal_notes'] = $existing . "\n[" . now()->format('d.m H:i') . ' - ' . auth()->user()->name . '] ' . $validated['internal_notes'];
        }
        if (!empty($validated['outcome'])) $data['outcome'] = $validated['outcome'];
        if (!empty($validated['assigned_to'])) $data['assigned_to'] = $validated['assigned_to'];

        $callback->update($data);

        return back()->with('success', 'Status actualizat.');
    }
}
