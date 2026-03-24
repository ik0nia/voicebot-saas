<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessVoiceCloning;
use App\Models\Bot;
use App\Models\ClonedVoice;
use App\Services\ElevenLabsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClonedVoiceController extends Controller
{
    public function create(Bot $bot)
    {
        $this->authorizeBotAccess($bot);

        $clonedVoice = ClonedVoice::withoutGlobalScopes()
            ->where('tenant_id', $bot->tenant_id)
            ->latest()
            ->first();

        return view('dashboard.bots.voice-clone', [
            'bot' => $bot,
            'clonedVoice' => $clonedVoice,
        ]);
    }

    public function store(Request $request, Bot $bot)
    {
        $this->authorizeBotAccess($bot);

        $request->validate([
            'name' => 'required|string|max:255',
            'audio' => 'required|file|mimes:wav,mp3,webm,ogg|max:20480', // max 20MB
        ]);

        $file = $request->file('audio');
        $path = $file->store('voice-samples/' . $bot->tenant_id, 'local');

        $clonedVoice = ClonedVoice::create([
            'tenant_id' => $bot->tenant_id,
            'name' => $request->input('name'),
            'audio_path' => $path,
            'status' => ClonedVoice::STATUS_PENDING,
        ]);

        ProcessVoiceCloning::dispatch($clonedVoice->id);

        return redirect()
            ->route('dashboard.bots.voiceClone.create', $bot)
            ->with('success', 'Înregistrarea a fost trimisă pentru procesare. Veți fi notificat când vocea clonată este gata.');
    }

    public function activate(Request $request, Bot $bot, ClonedVoice $clonedVoice)
    {
        $this->authorizeBotAccess($bot);

        if (!$clonedVoice->isReady()) {
            return back()->with('error', 'Vocea clonată nu este încă gata.');
        }

        $bot->update(['cloned_voice_id' => $clonedVoice->id]);

        return back()->with('success', 'Vocea clonată a fost activată pentru acest bot.');
    }

    public function deactivate(Request $request, Bot $bot)
    {
        $this->authorizeBotAccess($bot);

        $bot->update(['cloned_voice_id' => null]);

        return back()->with('success', 'Botul folosește din nou vocea presetată.');
    }

    public function destroy(Bot $bot, ClonedVoice $clonedVoice)
    {
        $this->authorizeBotAccess($bot);

        // Remove from any bot using it
        Bot::withoutGlobalScopes()
            ->where('cloned_voice_id', $clonedVoice->id)
            ->update(['cloned_voice_id' => null]);

        // Delete from ElevenLabs
        if ($clonedVoice->elevenlabs_voice_id) {
            app(ElevenLabsService::class)->deleteVoice($clonedVoice->elevenlabs_voice_id);
        }

        // Delete audio file
        if ($clonedVoice->audio_path && Storage::exists($clonedVoice->audio_path)) {
            Storage::delete($clonedVoice->audio_path);
        }

        $clonedVoice->delete();

        return back()->with('success', 'Vocea clonată a fost ștearsă.');
    }

    public function status(Bot $bot, ClonedVoice $clonedVoice)
    {
        return response()->json([
            'id' => $clonedVoice->id,
            'status' => $clonedVoice->status,
            'error_message' => $clonedVoice->error_message,
            'is_ready' => $clonedVoice->isReady(),
        ]);
    }

    private function authorizeBotAccess(Bot $bot): void
    {
        if (!auth()->user()->hasRole('super_admin') && $bot->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
