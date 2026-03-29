<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $tenant = auth()->user()->currentTenant();
        $query = Lead::where('tenant_id', $tenant->id)
            ->with('bot', 'conversation')
            ->orderByDesc('created_at');

        if ($status = $request->input('status')) $query->where('status', $status);
        if ($botId = $request->input('bot_id')) $query->where('bot_id', $botId);
        if ($from = $request->input('from')) $query->where('created_at', '>=', $from);
        if ($to = $request->input('to')) $query->where('created_at', '<=', $to . ' 23:59:59');
        if ($minScore = $request->input('min_score')) $query->where('qualification_score', '>=', (int) $minScore);

        $leads = $query->paginate(25);
        $bots = $tenant->bots()->select('id', 'name')->get();
        $stats = [
            'total' => Lead::where('tenant_id', $tenant->id)->count(),
            'qualified' => Lead::where('tenant_id', $tenant->id)->where('status', 'qualified')->count(),
            'converted' => Lead::where('tenant_id', $tenant->id)->where('status', 'converted')->count(),
            'avg_score' => (int) Lead::where('tenant_id', $tenant->id)->avg('qualification_score'),
        ];

        return view('dashboard.leads.index', compact('leads', 'bots', 'stats'));
    }

    public function show(Lead $lead)
    {
        $this->authorizeAccess($lead);
        $lead->load('bot', 'conversation.messages', 'contact');

        $events = \App\Models\ChatEvent::where('conversation_id', $lead->conversation_id)
            ->orderBy('occurred_at')
            ->get();

        return view('dashboard.leads.show', compact('lead', 'events'));
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorizeAccess($lead);
        $validated = $request->validate(['status' => 'required|in:new,partial,qualified,sent_to_crm,converted,dismissed']);
        $lead->update(['status' => $validated['status']]);
        return back()->with('success', 'Status actualizat.');
    }

    public function addNote(Request $request, Lead $lead)
    {
        $this->authorizeAccess($lead);
        $validated = $request->validate(['note' => 'required|string|max:2000']);
        $existing = $lead->internal_notes ?? '';
        $timestamp = now()->format('Y-m-d H:i');
        $author = auth()->user()->name;
        $lead->update(['internal_notes' => $existing . "\n[{$timestamp} - {$author}] {$validated['note']}"]);
        return back()->with('success', 'Notă adăugată.');
    }

    public function export(Request $request): StreamedResponse
    {
        $tenant = auth()->user()->currentTenant();
        $leads = Lead::where('tenant_id', $tenant->id)->with('bot')->orderByDesc('created_at')->get();

        return response()->streamDownload(function () use ($leads) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nume', 'Email', 'Telefon', 'Companie', 'Scor', 'Status', 'Bot', 'Data']);
            foreach ($leads as $lead) {
                fputcsv($handle, [
                    $lead->name, $lead->email, $lead->phone, $lead->company,
                    $lead->qualification_score, $lead->status, $lead->bot?->name, $lead->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        }, 'leads-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function authorizeAccess(Lead $lead): void
    {
        if (!auth()->user()->hasRole('super_admin') && $lead->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
