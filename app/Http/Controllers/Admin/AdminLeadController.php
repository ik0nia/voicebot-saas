<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Notifications\ContactMessageReplyMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Unified SaaS lead inbox. Everything that's "someone who wants
 * to talk to Sambla" lives here — contact form + niche landing
 * submissions, both mirrored into contact_messages. Structure
 * mimics a minimal ticketing view: list + detail + inline reply
 * form that stores the reply thread on the row.
 */
class AdminLeadController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $source = $request->get('source');
        $q = $request->get('q');

        $query = ContactMessage::query()->latest();

        if ($status && in_array($status, ['new', 'contacted', 'qualified', 'disqualified', 'converted'], true)) {
            $query->where('status', $status);
        }
        if ($source && in_array($source, ['contact_form', 'niche_landing'], true)) {
            $query->where('source', $source);
        }
        if ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'ilike', "%{$q}%")
                    ->orWhere('email', 'ilike', "%{$q}%")
                    ->orWhere('company', 'ilike', "%{$q}%")
                    ->orWhere('subject', 'ilike', "%{$q}%")
                    ->orWhere('message', 'ilike', "%{$q}%");
            });
        }

        $leads = $query->paginate(25)->withQueryString();

        $counts = [
            'total'     => ContactMessage::count(),
            'new'       => ContactMessage::where('status', 'new')->count(),
            'contacted' => ContactMessage::where('status', 'contacted')->count(),
            'qualified' => ContactMessage::where('status', 'qualified')->count(),
            'converted' => ContactMessage::where('status', 'converted')->count(),
        ];

        return view('admin.leads.index', compact('leads', 'counts', 'status', 'source', 'q'));
    }

    public function show(ContactMessage $lead)
    {
        $lead->load('replies.sender');
        return view('admin.leads.show', compact('lead'));
    }

    public function updateStatus(Request $request, ContactMessage $lead)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,qualified,disqualified,converted',
        ]);

        $lead->update([
            'status' => $validated['status'],
            'handled_at' => $validated['status'] !== 'new' ? now() : null,
        ]);

        return back()->with('success', 'Status actualizat la: ' . $validated['status']);
    }

    public function reply(Request $request, ContactMessage $lead)
    {
        $validated = $request->validate([
            'subject' => 'nullable|string|max:240',
            'body'    => 'required|string|max:10000',
        ]);

        $reply = ContactMessageReply::create([
            'contact_message_id' => $lead->id,
            'direction'          => 'out',
            'sender_user_id'     => $request->user()?->id,
            'subject'            => $validated['subject'] ?: ('Re: ' . ($lead->subject ?: 'Sambla')),
            'body'               => $validated['body'],
            'sent_at'            => now(),
        ]);

        try {
            Notification::route('mail', $lead->email)->notify(new ContactMessageReplyMail($reply));
        } catch (\Throwable $e) {
            Log::warning('AdminLeadController: reply email failed', ['err' => $e->getMessage()]);
            return back()->withErrors(['reply' => 'Răspunsul s-a salvat dar trimiterea pe email a eșuat: ' . $e->getMessage()]);
        }

        // Auto-bump status new → contacted on first outbound reply.
        if ($lead->status === 'new') {
            $lead->update(['status' => 'contacted', 'handled_at' => now()]);
        }

        return back()->with('success', 'Răspuns trimis.');
    }
}
