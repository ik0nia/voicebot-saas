<?php

namespace Database\Seeders;

use App\Models\Niche;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class NicheSeeder extends Seeder
{
    public function run(): void
    {
        $files = glob(database_path('seeds/niches/*.json'));

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $fillable = (new Niche())->getFillable();

        foreach ($files as $file) {
            $raw = file_get_contents($file);
            $data = json_decode($raw, true);

            if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
                $this->command->warn("Skipping {$file}: " . json_last_error_msg());
                Log::warning("NicheSeeder: failed to parse {$file}", ['error' => json_last_error_msg()]);
                $skipped++;
                continue;
            }

            // Normalize known key variants
            if (isset($data['faqs']) && !isset($data['faq'])) {
                $data['faq'] = $data['faqs'];
            }

            // Apply sensible defaults
            $data['tone'] = $data['tone'] ?? 'professional';
            $data['sort_order'] = $data['sort_order'] ?? 0;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['problem_title'] = $data['problem_title'] ?? 'Problema';
            $data['solution_title'] = $data['solution_title'] ?? 'Soluția Sambla';

            // Filter to fillable columns only
            $payload = array_intersect_key($data, array_flip($fillable));

            // meta_description column is varchar(160) — truncate safely
            if (!empty($payload['meta_description']) && is_string($payload['meta_description'])) {
                $payload['meta_description'] = mb_substr($payload['meta_description'], 0, 160);
            }

            if (empty($payload['slug'])) {
                $this->command->warn("Skipping {$file}: missing slug");
                $skipped++;
                continue;
            }

            $existed = Niche::where('slug', $payload['slug'])->exists();
            Niche::updateOrCreate(['slug' => $payload['slug']], $payload);

            if ($existed) {
                $updated++;
            } else {
                $created++;
            }
        }

        $this->command->info("NicheSeeder: created={$created} updated={$updated} skipped={$skipped}");
        Log::info('NicheSeeder finished', compact('created', 'updated', 'skipped'));
    }
}
