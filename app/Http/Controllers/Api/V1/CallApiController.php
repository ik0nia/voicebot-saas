<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CallResource;
use App\Http\Resources\TranscriptResource;
use App\Models\Bot;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Services\TelnyxService;
use Illuminate\Http\Request;

class CallApiController extends Controller
{
    public function index(Request $request)
    {
        $calls = Call::where('tenant_id', $request->user()->tenant_id)
            ->with('bot')
            ->when($request->get('bot_id'), fn($q, $v) => $q->where('bot_id', $v))
            ->when($request->get('status'), fn($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($request->get('per_page', 20));

        return CallResource::collection($calls);
    }

    public function show(Request $request, Call $call)
    {
        if ($call->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        return new CallResource($call->load('bot', 'transcripts'));
    }

    public function transcript(Request $request, Call $call)
    {
        if ($call->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $transcripts = $call->transcripts()->orderBy('timestamp_ms')->get();

        return TranscriptResource::collection($transcripts);
    }

    public function outbound(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|string',
            'bot_id' => 'required|exists:bots,id',
            'from' => 'nullable|string',
        ]);

        $bot = Bot::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($validated['bot_id']);

        $from = $validated['from'] ?? PhoneNumber::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->first()?->number;

        if (!$from) {
            return response()->json(['error' => 'No phone number available.'], 422);
        }

        try {
            $telnyx = app(TelnyxService::class);
            $telnyxCall = $telnyx->makeCall(
                $validated['to'],
                $from,
                route('webhook.telnyx.voice')
            );

            $call = Call::create([
                'tenant_id' => $request->user()->tenant_id,
                'bot_id' => $bot->id,
                'caller_number' => $validated['to'],
                'direction' => 'outbound',
                'status' => 'initiated',
                'metadata' => ['telnyx_call_control_id' => $telnyxCall->call_control_id],
                'started_at' => now(),
            ]);

            return new CallResource($call);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to initiate call: ' . $e->getMessage()], 500);
        }
    }
}
