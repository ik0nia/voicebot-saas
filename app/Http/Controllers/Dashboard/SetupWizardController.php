<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\ConversationPolicy;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;

class SetupWizardController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;

        // If tenant already has bots, skip wizard
        if (Bot::exists()) {
            return redirect()->route('dashboard');
        }

        return view('dashboard.setup.index', [
            'presets' => config('business-presets'),
        ]);
    }

    public function storeBusinessType(Request $request)
    {
        $validated = $request->validate([
            'business_type' => 'required|in:ecommerce,services,hybrid',
        ]);

        session(['setup_business_type' => $validated['business_type']]);

        return response()->json(['success' => true]);
    }

    public function generatePrompt(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:200',
            'business_description' => 'required|string|max:1000',
            'business_type' => 'required|in:ecommerce,services,hybrid',
        ]);

        $preset = config("business-presets.{$validated['business_type']}");
        if (!$preset) {
            return response()->json(['error' => 'Invalid business type'], 422);
        }

        // Generate system prompt from template
        $prompt = str_replace(
            ['{business_name}', '{business_description}'],
            [$validated['business_name'], $validated['business_description']],
            $preset['system_prompt_template']
        );

        $greeting = str_replace(
            '{business_name}',
            $validated['business_name'],
            $preset['greeting']
        );

        // Optionally enhance with AI
        $aiEnhanced = false;
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'temperature' => 0.3,
                'max_tokens' => 300,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Ești un expert în configurarea asistenților virtuali AI. Primești un prompt de sistem draft și îl îmbunătățești ușor — păstrezi structura, adaugi detalii specifice business-ului, faci tonul mai natural. Răspunde DOAR cu prompt-ul îmbunătățit, fără explicații.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Business: {$validated['business_name']}\nDescriere: {$validated['business_description']}\nTip: {$validated['business_type']}\n\nPrompt draft:\n{$prompt}",
                    ],
                ],
            ]);
            $enhanced = trim($response->choices[0]->message->content ?? '');
            if (!empty($enhanced) && mb_strlen($enhanced) > 50) {
                $prompt = $enhanced;
                $aiEnhanced = true;
            }
        } catch (\Throwable $e) {
            Log::debug('Setup wizard: AI prompt enhancement failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'prompt' => $prompt,
            'greeting' => $greeting,
            'bot_name' => $validated['business_name'] . ' Asistent',
            'ai_enhanced' => $aiEnhanced,
        ]);
    }

    public function complete(Request $request)
    {
        $validated = $request->validate([
            'business_type' => 'required|in:ecommerce,services,hybrid',
            'business_name' => 'required|string|max:200',
            'bot_name' => 'required|string|max:200',
            'system_prompt' => 'required|string|max:5000',
            'greeting' => 'required|string|max:500',
            'domain' => 'nullable|string|max:500',
        ]);

        $tenant = auth()->user()->tenant;
        $preset = config("business-presets.{$validated['business_type']}");

        // Create site if domain provided
        $site = null;
        if (!empty($validated['domain'])) {
            $domain = preg_replace('#^https?://#', '', trim($validated['domain']));
            $domain = preg_replace('#^www\.#', '', $domain);
            $domain = rtrim($domain, '/');

            $site = Site::firstOrCreate(
                ['tenant_id' => $tenant->id, 'domain' => $domain],
                [
                    'name' => $validated['business_name'],
                    'status' => 'pending',
                    'verification_token' => Str::random(32),
                    'verification_method' => 'dns',
                ]
            );
        }

        // Create bot
        $bot = Bot::create([
            'tenant_id' => $tenant->id,
            'site_id' => $site?->id,
            'name' => $validated['bot_name'],
            'system_prompt' => $validated['system_prompt'],
            'greeting_message' => $validated['greeting'],
            'language' => 'ro',
            'voice' => 'alloy',
            'is_active' => true,
            'settings' => [
                'business_type' => $validated['business_type'],
                'temperature' => 0.7,
                'vad_threshold' => 0.5,
            ],
        ]);

        // Create conversation policy from preset
        if ($preset && !empty($preset['conversation_policy'])) {
            ConversationPolicy::create(array_merge(
                [
                    'tenant_id' => $tenant->id,
                    'bot_id' => $bot->id,
                    'is_active' => true,
                ],
                $preset['conversation_policy']
            ));
        }

        // Create default web chatbot channel
        $bot->channels()->create([
            'type' => 'web_chatbot',
            'name' => 'Web Chatbot',
            'is_active' => true,
            'config' => [
                'greeting' => $validated['greeting'],
                'color' => '#991b1b',
            ],
        ]);

        // Save business type in tenant settings
        $settings = $tenant->settings ?? [];
        $settings['business_type'] = $validated['business_type'];
        $settings['setup_completed'] = true;
        $tenant->update(['settings' => $settings]);

        return response()->json([
            'success' => true,
            'bot_id' => $bot->id,
            'channel_id' => $bot->channels->first()->id,
            'redirect' => route('dashboard.bots.show', $bot),
        ]);
    }
}
