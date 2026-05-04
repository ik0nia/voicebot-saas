<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $this->authorize('delete', $call);
        $call->delete();
        return redirect()->route('dashboard.calls.index')
            ->with('success', 'Apelul a fost șters.');
    }

    /**
     * Stream the locally-mirrored call recording, gated by tenant auth.
     *
     * Why a custom route instead of returning the carrier URL: Twilio's
     * recording URLs require account credentials to fetch — exposing
     * them to the browser leaks our auth, and even pre-signed Twilio
     * URLs eventually expire. Mirroring + serving locally keeps the
     * audio working past carrier retention and gives operators a clean
     * `<audio>` source on our domain.
     *
     * Returns:
     *   200 + audio/mpeg stream when local file exists
     *   410 Gone when the recording was purged (past 14-day retention)
     *   404 when this call was never mirrored or doesn't exist for tenant
     */
    public function audio(Call $call): StreamedResponse|\Illuminate\Http\Response
    {
        // Tenant scope. The Call model uses BelongsToTenant + global
        // scope, so route binding already filters cross-tenant access;
        // super-admin can see everything intentionally.
        if (!auth()->user()->isSuperAdmin()
            && (int) $call->tenant_id !== (int) auth()->user()->tenant_id) {
            abort(404);
        }

        if ($call->recording_purged_at) {
            return response('Recording deleted after retention window expired.', 410)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        if (!$call->local_recording_path || !Storage::disk('local')->exists($call->local_recording_path)) {
            abort(404);
        }

        return Storage::disk('local')->response(
            $call->local_recording_path,
            sprintf('call-%d.mp3', $call->id),
            [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
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
