<?php

namespace App\Console\Commands;

use App\Services\Social\SocialImageOrchestrator;
use Illuminate\Console\Command;

class TestSocialImagePattern extends Command
{
    protected $signature = 'sambla:test-pattern
        {pattern : Pattern slug (bento_editorial_portrait, person_annotated_call, split_before_after, flat_illustration_icons, testimonial_pullquote)}
        {niche=default : Niche slug (veterinar, stomatolog, contabil, avocat, salon, restaurant, cofetar, auto, imobiliare, consultant)}
        {--message= : Key message for the RO composer (e.g. "programări automate 24/7")}
        {--no-composer : Skip GPT-5.4 RO composer and use pattern defaults}
        {--logo : Pass public/images/logo-icon.png as reference via /v1/images/edits}';

    protected $description = 'Generate a single social-media image using the new gpt-image-2 pipeline and print the public URL.';

    public function handle(SocialImageOrchestrator $orchestrator): int
    {
        $pattern = $this->argument('pattern');
        $niche = $this->argument('niche');
        $keyMessage = $this->option('message');
        $useComposer = !$this->option('no-composer');
        $withLogo = $this->option('logo');

        $options = ['use_composer' => $useComposer];
        if ($keyMessage) {
            $options['key_message'] = $keyMessage;
        }
        if ($withLogo) {
            $options['reference_image_path'] = public_path('images/logo-icon.png');
        }

        $this->info("Pattern='{$pattern}' niche='{$niche}' composer=" . ($useComposer ? 'on' : 'off') . ($withLogo ? ' +logo' : ''));
        if ($keyMessage) {
            $this->line("Key message: {$keyMessage}");
        }

        $start = microtime(true);
        $result = $orchestrator->generate($pattern, $niche, $options);
        $elapsed = round(microtime(true) - $start, 1);

        if (!$result) {
            $this->error("Generation failed (see storage/logs/laravel.log for details).");
            return self::FAILURE;
        }

        $this->info("✓ Generated in {$elapsed}s");
        $this->line("  URL:      {$result['url']}");
        $this->line("  File:     {$result['path']}");
        $this->line("  Provider: {$result['provider']}");
        $this->line("  Prompt:   {$result['prompt_length']} chars");

        return self::SUCCESS;
    }
}
