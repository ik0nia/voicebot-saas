<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\ServiceType;
use App\Models\StaffMember;
use App\Models\WorkingHour;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * php artisan booking:seed-defaults {bot_id}
 *
 * Idempotent — reads the bot's niche_slug from config/niches.php
 * and seeds:
 *   - service_types (one per `default_service_types` entry)
 *   - a "Cabinetul" staff member (solo operator)
 *   - working_hours Mon-Fri 09:00-19:00 (overridden by the niche's
 *     default_working_hours if provided)
 *
 * Running twice is a no-op: existing rows are matched by slug and
 * left untouched (not updated) — tenants may have edited them.
 */
class BookingSeedDefaults extends Command
{
    protected $signature = 'booking:seed-defaults {bot_id}';
    protected $description = 'Seed service types, staff, and working hours from the bot\'s niche template.';

    public function handle(): int
    {
        $bot = Bot::withoutGlobalScopes()->find($this->argument('bot_id'));
        if (!$bot) {
            $this->error("Bot #{$this->argument('bot_id')} not found.");
            return self::FAILURE;
        }
        if (!$bot->niche_slug) {
            $this->error("Bot has no niche_slug set — nothing to seed.");
            return self::FAILURE;
        }
        $niche = config('niches.' . $bot->niche_slug);
        if (!$niche) {
            $this->error("Niche '{$bot->niche_slug}' not in config/niches.php.");
            return self::FAILURE;
        }

        // 1. Service types
        $serviceCount = 0;
        foreach ($niche['default_service_types'] ?? [] as $spec) {
            $slug = Str::slug($spec['name']);
            $existing = ServiceType::withoutGlobalScopes()
                ->where('bot_id', $bot->id)->where('slug', $slug)->first();
            if ($existing) continue;
            ServiceType::create([
                'tenant_id'        => $bot->tenant_id,
                'bot_id'           => $bot->id,
                'name'             => $spec['name'],
                'slug'             => $slug,
                'duration_minutes' => $spec['duration_minutes'] ?? 30,
                'buffer_minutes'   => $spec['buffer_minutes'] ?? 0,
                'price'            => $spec['price'] ?? null,
                'is_urgent'        => $spec['is_urgent'] ?? false,
            ]);
            $serviceCount++;
        }
        $this->line("  - service_types created: {$serviceCount}");

        // 2. Staff — one default if none exists.
        $staff = StaffMember::withoutGlobalScopes()
            ->where('bot_id', $bot->id)->first();
        if (!$staff) {
            $staff = StaffMember::create([
                'tenant_id' => $bot->tenant_id,
                'bot_id'    => $bot->id,
                'name'      => 'Cabinetul',
                'role'      => $niche['display_name'] ?? 'Serviciu principal',
                'is_active' => true,
            ]);
            $this->line('  - default staff member created');
        } else {
            $this->line('  - staff already present, skipping');
        }

        // 3. Working hours. Niche may specify a schedule; default
        //    to L-V 09:00-19:00 if not.
        $hoursCount = WorkingHour::where('staff_member_id', $staff->id)->count();
        if ($hoursCount === 0) {
            $rules = $niche['default_working_hours'] ?? ['mon-fri' => '09:00-19:00'];
            foreach ($this->expandRules($rules) as $rule) {
                WorkingHour::create([
                    'staff_member_id' => $staff->id,
                    'weekday'         => $rule['weekday'],
                    'start_time'      => $rule['start'],
                    'end_time'        => $rule['end'],
                ]);
            }
            $this->line('  - working hours seeded');
        } else {
            $this->line('  - working hours already present, skipping');
        }

        $this->info("Done seeding booking defaults for bot #{$bot->id} ({$bot->niche_slug}).");
        return self::SUCCESS;
    }

    /**
     * Expand a compact rule map like ['mon-fri' => '09:00-19:00',
     * 'sat' => '09:00-14:00'] into one entry per weekday.
     *
     * @return array<int, array{weekday:int,start:string,end:string}>
     */
    private function expandRules(array $rules): array
    {
        $dayMap = ['mon'=>1,'tue'=>2,'wed'=>3,'thu'=>4,'fri'=>5,'sat'=>6,'sun'=>7];
        $out = [];
        foreach ($rules as $days => $range) {
            [$start, $end] = array_map('trim', explode('-', $range));
            if (str_contains($days, '-')) {
                [$from, $to] = explode('-', $days);
                $fromNum = $dayMap[$from] ?? 1;
                $toNum = $dayMap[$to] ?? 5;
                for ($d = $fromNum; $d <= $toNum; $d++) {
                    $out[] = ['weekday' => $d, 'start' => $start, 'end' => $end];
                }
            } else {
                $d = $dayMap[$days] ?? null;
                if ($d) $out[] = ['weekday' => $d, 'start' => $start, 'end' => $end];
            }
        }
        return $out;
    }
}
