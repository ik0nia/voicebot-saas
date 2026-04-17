<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BotResource;
use App\Models\Bot;
use App\Services\StructuredPromptBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BotApiController extends Controller
{
    public function index(Request $request)
    {
        $bots = Bot::where('tenant_id', $request->user()->tenant_id)
            ->when($request->get('active'), fn($q) => $q->where('is_active', true))
            ->paginate($request->get('per_page', 15));

        return BotResource::collection($bots);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'language' => 'string|in:ro,en,de,fr,es',
            'voice' => 'string|in:alloy,echo,fable,onyx,nova,shimmer',
            'system_prompt' => 'nullable|string|max:10000',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(6);

        $bot = Bot::create($validated);

        return new BotResource($bot);
    }

    public function show(Request $request, Bot $bot)
    {
        if ($bot->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        return new BotResource($bot->loadCount('calls'));
    }

    public function update(Request $request, Bot $bot)
    {
        if ($bot->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'language' => 'string|in:ro,en,de,fr,es',
            'voice' => 'string|in:alloy,echo,fable,onyx,nova,shimmer',
            'system_prompt' => 'nullable|string|max:10000',
            'is_active' => 'boolean',
        ]);

        $bot->update($validated);

        return new BotResource($bot);
    }

    public function destroy(Request $request, Bot $bot)
    {
        if ($bot->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $bot->delete();

        return response()->json(['message' => 'Bot deleted.'], 200);
    }

    /**
     * Preview the composed structured prompt for a bot.
     *
     * Shows each section individually plus the final stitched prompt —
     * lets operators sanity-check what the PromptBuilder will send before
     * flipping the use_structured_prompt flag on a live bot.
     *
     * Returns the composition even when the flag is off (flag_on reports
     * runtime behaviour). Callers can therefore dry-run the new format
     * side-by-side with the current freeform prompt.
     */
    public function promptPreview(Request $request, Bot $bot, StructuredPromptBuilder $builder)
    {
        if ($bot->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        return response()->json([
            'prompt' => $builder->build($bot),
            'sections' => $builder->sections($bot),
            'flag_on' => $bot->usesStructuredPrompt(),
        ]);
    }
}
