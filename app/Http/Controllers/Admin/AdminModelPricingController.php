<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModelPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminModelPricingController extends Controller
{
    public function index()
    {
        $pricing = ModelPricing::orderBy('provider')->orderBy('model_id')->get();

        return view('admin.model-pricing.index', compact('pricing'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'model_id' => 'required|string|max:100|unique:model_pricing,model_id',
            'provider' => 'required|string|in:openai,anthropic,elevenlabs',
            'pricing_unit' => 'required|string|in:1M_tokens,minute,1K_chars',
            'input_cost' => 'required|numeric|min:0',
            'output_cost' => 'required|numeric|min:0',
            'max_context_tokens' => 'required|integer|min:0',
        ]);

        ModelPricing::create($validated);
        Cache::flush();

        return back()->with('success', 'Model adăugat cu succes.');
    }

    public function update(Request $request, ModelPricing $pricing)
    {
        $validated = $request->validate([
            'pricing_unit' => 'sometimes|string|in:1M_tokens,minute,1K_chars',
            'input_cost' => 'required|numeric|min:0',
            'output_cost' => 'required|numeric|min:0',
            'max_context_tokens' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $pricing->update($validated);
        Cache::forget("model_pricing_{$pricing->model_id}");

        return back()->with('success', "Prețuri actualizate pentru {$pricing->model_id}.");
    }

    public function destroy(ModelPricing $pricing)
    {
        Cache::forget("model_pricing_{$pricing->model_id}");
        $pricing->delete();

        return back()->with('success', 'Model șters.');
    }
}
