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

        if ($stage = $request->input('stage')) $query->where('pipeline_stage', $stage);
        if ($status = $request->input('status')) $query->where('status', $status);
        if ($botId = $request->input('bot_id')) $query->where('bot_id', $botId);
        if ($from = $request->input('from')) $query->where('created_at', '>=', $from);
        if ($to = $request->input('to')) $query->where('created_at', '<=', $to . ' 23:59:59');

        $leads = $query->paginate(25);
        $bots = $tenant->bots()->select('id', 'name')->get();

        // Pipeline stats
        $pipelineBase = Lead::where('tenant_id', $tenant->id);
        $pipeline = [];
        foreach (Lead::STAGES as $stageKey => $stageLabel) {
            $pipeline[$stageKey] = (clone $pipelineBase)->where('pipeline_stage', $stageKey)->count();
        }

        $stats = [
            'total' => Lead::where('tenant_id', $tenant->id)->count(),
            'active' => Lead::where('tenant_id', $tenant->id)->active()->count(),
            'won' => $pipeline['won'],
            'scheduled' => $pipeline['scheduled'],
            'pipeline' => $pipeline,
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
        $validated = $request->validate([
            'pipeline_stage' => 'required|in:' . implode(',', array_keys(Lead::STAGES)),
            'assigned_to' => 'nullable|string|max:255',
            'outcome' => 'nullable|string|max:100',
            'estimated_value' => 'nullable|numeric|min:0',
            'lost_reason' => 'nullable|string|max:255',
            'service_type' => 'nullable|string|max:100',
            'preferred_date' => 'nullable|date',
            'preferred_time_slot' => 'nullable|string|in:dimineata,dupa-amiaza,seara',
        ]);

        $extra = array_filter([
            'assigned_to' => $validated['assigned_to'] ?? null,
            'outcome' => $validated['outcome'] ?? null,
            'estimated_value' => $validated['estimated_value'] ?? null,
            'lost_reason' => $validated['lost_reason'] ?? null,
            'service_type' => $validated['service_type'] ?? null,
            'preferred_date' => $validated['preferred_date'] ?? null,
            'preferred_time_slot' => $validated['preferred_time_slot'] ?? null,
        ], fn($v) => $v !== null);

        $lead->advanceTo($validated['pipeline_stage'], $extra);
        return back()->with('success', 'Lead avansat la: ' . Lead::STAGES[$validated['pipeline_stage']]);
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
