<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Transcript;
use Illuminate\Http\Request;

class AdminCallController extends Controller
{
    public function index(Request $request)
    {
        $query = Call::withoutGlobalScopes()->with(['bot', 'tenant'])->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $calls = $query->paginate(20);
        return view('admin.calls.index', compact('calls'));
    }

    public function show($callId)
    {
        $call = Call::withoutGlobalScopes()->with(['bot', 'tenant', 'phoneNumber'])->findOrFail($callId);
        $transcripts = Transcript::where('call_id', $call->id)->orderBy('timestamp_ms')->get();
        return view('admin.calls.show', compact('call', 'transcripts'));
    }
}
