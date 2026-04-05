<?php

namespace App\Console\Commands;

use App\Models\SocialAccount;
use App\Services\Social\GeminiContentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SetupFacebookPage extends Command
{
    protected $signature = 'social:setup-facebook-page
                            {--skip-cover : Skip cover photo generation}
                            {--skip-profile : Skip profile picture update}
                            {--skip-info : Skip page info update}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Setup Facebook page with profile picture, cover photo, and all business details';

    private string $graphUrl = 'https://graph.facebook.com/v19.0';

    public function handle(): int
    {
        $account = SocialAccount::where('platform', 'facebook')->where('is_active', true)->first();

        if (!$account || empty($account->access_token) || empty($account->platform_id)) {
            $this->error('No active Facebook account configured. Go to /admin/social/accounts first.');
            return self::FAILURE;
        }

        $this->info("Facebook Page: {$account->name} (ID: {$account->platform_id})");
        $this->newLine();

        $dryRun = $this->option('dry-run');

        // Step 1: Update page info (about, description, website, phone, hours)
        if (!$this->option('skip-info')) {
            $this->updatePageInfo($account, $dryRun);
        }

        // Step 2: Set profile picture (Sambla logo)
        if (!$this->option('skip-profile')) {
            $this->updateProfilePicture($account, $dryRun);
        }

        // Step 3: Generate and set cover photo
        if (!$this->option('skip-cover')) {
            $this->updateCoverPhoto($account, $dryRun);
        }

        $this->newLine();
        $this->info('Facebook page setup complete!');

        return self::SUCCESS;
    }

    private function updatePageInfo(SocialAccount $account, bool $dryRun): void
    {
        $this->components->task('Updating page info', function () use ($account, $dryRun) {
            $fields = [
                'about' => 'Sambla - Angajatul tău AI. Chatbot & voicebot inteligent pentru afacerea ta. Setup 10 min, funcționează 24/7.',
                'description' => "Sambla este o platformă românească de AI conversațional care transformă modul în care business-urile comunică cu clienții.\n\n"
                    . "Ce oferim:\n"
                    . "- Chatbot inteligent pentru website — răspunde instant la întrebări, califică lead-uri, oferă suport 24/7\n"
                    . "- Voicebot cu voce naturală — agent telefonic AI care sună și răspunde la apeluri ca un om\n"
                    . "- Integrare WooCommerce — comenzi, stocuri, recomandări de produse direct din chat\n"
                    . "- Bază de cunoștințe AI — învață din documentele tale și nu inventează răspunsuri\n"
                    . "- Analytics avansate — vezi ce întreabă clienții, identifică oportunități\n\n"
                    . "De ce Sambla?\n"
                    . "- 100% românesc — hosting în România, optimizat pentru limba română\n"
                    . "- Setup în 10 minute — fără cunoștințe tehnice\n"
                    . "- Anti-halucinare — AI-ul răspunde doar din datele tale\n"
                    . "- GDPR compliant — date izolate per client, hosting local\n\n"
                    . "Planuri de la 99€/lună. Încercare gratuită disponibilă.\n"
                    . "https://sambla.ro",
                'website' => 'https://sambla.ro',
                'phone' => '+40775222333',
                'emails' => json_encode(['servus@sambla.ro']),
                'hours' => json_encode([
                    'mon_1_open' => '10:00',
                    'mon_1_close' => '16:00',
                    'tue_1_open' => '10:00',
                    'tue_1_close' => '16:00',
                    'wed_1_open' => '10:00',
                    'wed_1_close' => '16:00',
                    'thu_1_open' => '10:00',
                    'thu_1_close' => '16:00',
                ]),
            ];

            if ($dryRun) {
                return true;
            }

            $response = Http::timeout(15)->post("{$this->graphUrl}/{$account->platform_id}", array_merge($fields, [
                'access_token' => $account->access_token,
            ]));

            if (!$response->ok()) {
                $error = $response->json('error.message') ?? $response->body();
                Log::error('Facebook page info update failed', ['error' => $error]);

                // Try fields one by one if batch fails
                $this->warn("  Batch update failed ({$error}), trying fields individually...");
                $succeeded = 0;
                foreach ($fields as $field => $value) {
                    $single = Http::timeout(10)->post("{$this->graphUrl}/{$account->platform_id}", [
                        $field => $value,
                        'access_token' => $account->access_token,
                    ]);
                    if ($single->ok()) {
                        $succeeded++;
                        $this->line("    ✓ {$field}");
                    } else {
                        $err = $single->json('error.message') ?? 'unknown';
                        $this->line("    ✗ {$field}: {$err}");
                    }
                }
                return $succeeded > 0;
            }

            return true;
        });
    }

    private function updateProfilePicture(SocialAccount $account, bool $dryRun): void
    {
        $this->components->task('Setting profile picture (Sambla logo)', function () use ($account, $dryRun) {
            // Use icon-only logo (no text) — cleaner for profile picture
            $logoUrl = rtrim(config('app.url'), '/') . '/images/logo-icon.png';

            if ($dryRun) {
                return true;
            }

            $response = Http::timeout(30)->post("{$this->graphUrl}/{$account->platform_id}/picture", [
                'picture' => $logoUrl,
                'access_token' => $account->access_token,
            ]);

            if (!$response->ok()) {
                $error = $response->json('error.message') ?? $response->body();
                Log::error('Facebook profile picture update failed', ['error' => $error]);

                // Fallback: try with file upload instead of URL
                $logoFile = public_path('images/logo-icon.png');
                if (file_exists($logoFile)) {
                    $this->warn("  URL method failed, trying file upload...");
                    $response = Http::timeout(30)
                        ->attach('source', file_get_contents($logoFile), 'logo.png')
                        ->post("{$this->graphUrl}/{$account->platform_id}/picture", [
                            'access_token' => $account->access_token,
                        ]);
                    if ($response->ok()) {
                        return true;
                    }
                    $error = $response->json('error.message') ?? $response->body();
                    Log::error('Facebook profile picture file upload also failed', ['error' => $error]);
                }

                $this->error("  Failed: {$error}");
                return false;
            }

            return true;
        });
    }

    private function updateCoverPhoto(SocialAccount $account, bool $dryRun): void
    {
        $this->components->task('Generating cover photo (light background)', function () use ($account, $dryRun) {
            $gemini = app(GeminiContentService::class);

            $coverImage = $gemini->generateImage(
                prompt: "Facebook cover banner for tech company called Sambla. "
                    . "CRITICAL: The background MUST be WHITE or very light gray (#f5f5f5). DO NOT use dark backgrounds, DO NOT use red backgrounds. "
                    . "Use WHITE/LIGHT background only. "
                    . "Small red (#dc2626) accent elements only: thin lines, small dots, subtle icons. "
                    . "Include floating minimal icons: a chat bubble, a phone handset, a sound wave — all in light gray or subtle red outlines. "
                    . "Center text: 'Sambla' in bold dark (#1a1a1a) letters, below it 'Angajatul tău AI' in smaller gray text. "
                    . "Style: Apple-like minimalism, premium, airy, lots of white space. NO busy graphics. NO dark areas.",
                aspectRatio: '16:9', // Facebook cover
                style: null,
            );

            if (!$coverImage) {
                $this->error('  Failed to generate cover image');
                return false;
            }

            $this->line("  Generated: {$coverImage['url']}");

            if ($dryRun) {
                return true;
            }

            // Upload and set as cover in one step
            $coverFile = public_path($coverImage['path']);
            if (!file_exists($coverFile)) {
                $this->error("  Cover file not found: {$coverFile}");
                return false;
            }

            $setCover = Http::timeout(30)
                ->attach('source', file_get_contents($coverFile), 'cover.png')
                ->post("{$this->graphUrl}/{$account->platform_id}/photos", [
                    'access_token' => $account->access_token,
                    'no_story' => 'true',
                    'cover_photo' => 'true',
                ]);

            if (!$setCover->ok()) {
                $error = $setCover->json('error.message') ?? $setCover->body();
                $this->error("  Set cover failed: {$error}");
                return false;
            }

            return true;
        });
    }
}
