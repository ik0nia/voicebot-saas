<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AbExperiment;
use App\Models\Bot;
use App\Services\AbTestingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbTestingController extends Controller
{
    public function __construct(
        private AbTestingService $abTestingService,
    ) {}

    /**
     * List experiments for a bot.
     */
    public function index(Bot $bot): JsonResponse
    {
        $experiments = AbExperiment::where('bot_id', $bot->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($exp) => $this->formatExperiment($exp));

        return response()->json(['experiments' => $experiments]);
    }

    /**
     * Create a new experiment.
     */
    public function store(Request $request, Bot $bot): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:prompt,model,rag_config,policy,custom',
            'variants' => 'required|array|min:2',
            'variants.*.id' => 'required|string|max:10',
            'variants.*.name' => 'required|string|max:100',
            'variants.*.config' => 'required|array',
            'variants.*.weight' => 'required|integer|min:1|max:100',
            'metric' => 'sometimes|string|in:satisfaction,conversion,engagement,lead_capture,response_quality,messages_count',
            'min_conversations' => 'sometimes|integer|min:10|max:10000',
            'confidence_level' => 'sometimes|numeric|min:0.8|max:0.99',
        ]);

        $experiment = AbExperiment::create([
            'tenant_id' => $bot->tenant_id,
            'bot_id' => $bot->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'status' => 'draft',
            'variants' => $validated['variants'],
            'metric' => $validated['metric'] ?? 'satisfaction',
            'min_conversations' => $validated['min_conversations'] ?? 100,
            'confidence_level' => $validated['confidence_level'] ?? 0.95,
        ]);

        return response()->json([
            'experiment' => $this->formatExperiment($experiment),
            'message' => 'Experiment creat cu succes.',
        ], 201);
    }

    /**
     * Show experiment details with computed results.
     */
    public function show(Bot $bot, AbExperiment $experiment): JsonResponse
    {
        if ($experiment->bot_id !== $bot->id) {
            return response()->json(['error' => 'Experiment not found for this bot.'], 404);
        }

        // Compute live results if running
        $results = null;
        if (in_array($experiment->status, ['running', 'completed'])) {
            $results = $experiment->status === 'completed' && $experiment->results
                ? $experiment->results
                : $this->abTestingService->computeResults($experiment);
        }

        $data = $this->formatExperiment($experiment);
        $data['results'] = $results;
        $data['assignments_count'] = $experiment->assignments()->count();
        $data['assignments_with_metrics'] = $experiment->assignments()->whereNotNull('metrics')->count();

        return response()->json(['experiment' => $data]);
    }

    /**
     * Update experiment status (start, pause, complete).
     */
    public function update(Request $request, Bot $bot, AbExperiment $experiment): JsonResponse
    {
        if ($experiment->bot_id !== $bot->id) {
            return response()->json(['error' => 'Experiment not found for this bot.'], 404);
        }

        $validated = $request->validate([
            'status' => 'sometimes|string|in:draft,running,paused,completed',
            'name' => 'sometimes|string|max:255',
            'variants' => 'sometimes|array|min:2',
            'metric' => 'sometimes|string',
            'min_conversations' => 'sometimes|integer|min:10',
            'confidence_level' => 'sometimes|numeric|min:0.8|max:0.99',
        ]);

        // Handle status transitions
        if (isset($validated['status'])) {
            $newStatus = $validated['status'];

            if ($newStatus === 'running' && $experiment->status !== 'running') {
                // Check no other experiment is running for this bot
                $existingRunning = AbExperiment::where('bot_id', $bot->id)
                    ->where('id', '!=', $experiment->id)
                    ->where('status', 'running')
                    ->exists();

                if ($existingRunning) {
                    return response()->json([
                        'error' => 'Alt experiment este deja activ pentru acest bot. Oprește-l mai întâi.',
                    ], 422);
                }

                $validated['started_at'] = now();
            }

            if ($newStatus === 'completed') {
                $validated['ended_at'] = now();
                $validated['results'] = $this->abTestingService->computeResults($experiment);
            }
        }

        $experiment->update($validated);

        return response()->json([
            'experiment' => $this->formatExperiment($experiment->fresh()),
            'message' => 'Experiment actualizat.',
        ]);
    }

    /**
     * Delete an experiment.
     */
    public function destroy(Bot $bot, AbExperiment $experiment): JsonResponse
    {
        if ($experiment->bot_id !== $bot->id) {
            return response()->json(['error' => 'Experiment not found for this bot.'], 404);
        }

        if ($experiment->status === 'running') {
            return response()->json([
                'error' => 'Nu poți șterge un experiment activ. Oprește-l mai întâi.',
            ], 422);
        }

        $experiment->delete();

        return response()->json(['message' => 'Experiment șters.']);
    }

    /**
     * Format experiment for JSON response.
     */
    private function formatExperiment(AbExperiment $experiment): array
    {
        return [
            'id' => $experiment->id,
            'name' => $experiment->name,
            'type' => $experiment->type,
            'status' => $experiment->status,
            'variants' => $experiment->variants,
            'metric' => $experiment->metric,
            'min_conversations' => $experiment->min_conversations,
            'confidence_level' => $experiment->confidence_level,
            'started_at' => $experiment->started_at?->toIso8601String(),
            'ended_at' => $experiment->ended_at?->toIso8601String(),
            'results' => $experiment->results,
            'created_at' => $experiment->created_at->toIso8601String(),
        ];
    }
}
