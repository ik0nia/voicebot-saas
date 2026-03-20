<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Bot;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function index(Request $request)
    {
        $query = Call::with('bot')->latest();

        if ($botId = $request->get('bot')) {
            $query->where('bot_id', $botId);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($direction = $request->get('direction')) {
            $query->where('direction', $direction);
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        if ($search = $request->get('search')) {
            $query->where('caller_number', 'like', "%{$search}%");
        }

        $calls = $query->paginate(20)->withQueryString();
        $bots = Bot::orderBy('name')->get();

        return view('dashboard.calls.index', compact('calls', 'bots'));
    }

    public function show(Call $call)
    {
        $call->load('bot', 'transcripts', 'callEvents', 'phoneNumber');
        $transcripts = $call->transcripts()->orderBy('timestamp_ms')->get();
        $events = $call->callEvents()->orderBy('occurred_at')->get();

        return view('dashboard.calls.show', compact('call', 'transcripts', 'events'));
    }

    public function destroy(Call $call)
    {
        $call->delete();
        return redirect()->route('dashboard.calls.index')
            ->with('success', 'Apelul a fost șters.');
    }

    public function exportTranscript(Call $call, string $format = 'txt')
    {
        $call->load('transcripts', 'bot');
        $transcripts = $call->transcripts()->orderBy('timestamp_ms')->get();

        if ($format === 'txt') {
            $content = "Transcript - Apel #{$call->id}\n";
            $content .= "Bot: {$call->bot?->name}\n";
            $content .= "Data: {$call->created_at}\n";
            $content .= "Durată: {$call->duration_seconds}s\n";
            $content .= str_repeat('-', 50) . "\n\n";

            foreach ($transcripts as $t) {
                $role = $t->role === 'user' ? 'Client' : 'Bot';
                $content .= "[{$role}]: {$t->content}\n\n";
            }

            return response($content)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', "attachment; filename=transcript-{$call->id}.txt");
        }

        return back();
    }
}
