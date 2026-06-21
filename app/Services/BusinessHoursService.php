<?php

namespace App\Services;

use App\Models\Bot;
use Carbon\CarbonImmutable;
use Carbon\CarbonTimeZone;

/**
 * Interpretează `settings.business_info.hours_schedule` pentru a răspunde
 * la întrebări simple: este botul deschis ACUM? când e următoarea deschidere?
 *
 * Schema unei intrări (din UI tab Business):
 *   { key: 'mon', label: 'Luni', closed: bool, open: 'HH:MM', close: 'HH:MM',
 *     break_start: 'HH:MM'|null, break_end: 'HH:MM'|null }
 *
 * Timezone-ul vine din `settings.timezone` (per-bot), fallback `config('app.timezone')`.
 */
class BusinessHoursService
{
    private const DAY_KEY_FROM_DOW = [
        0 => 'sun', 1 => 'mon', 2 => 'tue', 3 => 'wed',
        4 => 'thu', 5 => 'fri', 6 => 'sat',
    ];

    /**
     * @return array{is_open: bool, status: 'open'|'on_break'|'closed', label: ?string, next_open: ?string}
     */
    public function status(Bot $bot, ?CarbonImmutable $now = null): array
    {
        $tz = $this->timezone($bot);
        $now = ($now ?? CarbonImmutable::now())->setTimezone($tz);

        $schedule = $this->schedule($bot);
        if (empty($schedule)) {
            return ['is_open' => true, 'status' => 'open', 'label' => null, 'next_open' => null];
        }

        $today = $this->dayEntry($schedule, $now);
        if ($today && empty($today['closed'])) {
            $open = $this->timeToday($now, $today['open'] ?? null);
            $close = $this->timeToday($now, $today['close'] ?? null);
            if ($open && $close && $now->between($open, $close)) {
                $bs = $this->timeToday($now, $today['break_start'] ?? null);
                $be = $this->timeToday($now, $today['break_end'] ?? null);
                if ($bs && $be && $now->between($bs, $be)) {
                    return [
                        'is_open' => false,
                        'status' => 'on_break',
                        'label' => 'Pauză până la ' . $be->format('H:i'),
                        'next_open' => $be->format('H:i'),
                    ];
                }
                return [
                    'is_open' => true,
                    'status' => 'open',
                    'label' => 'Deschis până la ' . $close->format('H:i'),
                    'next_open' => null,
                ];
            }
        }

        $next = $this->findNextOpening($schedule, $now);
        return [
            'is_open' => false,
            'status' => 'closed',
            'label' => $next ? 'Închis. Deschidem ' . $next['human'] : 'Închis',
            'next_open' => $next['human'] ?? null,
        ];
    }

    public function isOpenNow(Bot $bot, ?CarbonImmutable $now = null): bool
    {
        return $this->status($bot, $now)['is_open'];
    }

    public function timezone(Bot $bot): CarbonTimeZone
    {
        $tz = $bot->settings['timezone'] ?? null;
        if (!is_string($tz) || trim($tz) === '') {
            $tz = (string) (config('app.timezone') ?: 'Europe/Bucharest');
        }
        try {
            return new CarbonTimeZone($tz);
        } catch (\Throwable) {
            return new CarbonTimeZone('Europe/Bucharest');
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function schedule(Bot $bot): array
    {
        $schedule = $bot->settings['business_info']['hours_schedule'] ?? null;
        if (is_string($schedule)) {
            $schedule = json_decode($schedule, true);
        }
        return is_array($schedule) ? $schedule : [];
    }

    /**
     * @param array<int, array<string, mixed>> $schedule
     * @return array<string, mixed>|null
     */
    private function dayEntry(array $schedule, CarbonImmutable $when): ?array
    {
        $key = self::DAY_KEY_FROM_DOW[(int) $when->format('w')] ?? null;
        if ($key === null) {
            return null;
        }
        foreach ($schedule as $entry) {
            if (is_array($entry) && ($entry['key'] ?? null) === $key) {
                return $entry;
            }
        }
        return null;
    }

    private function timeToday(CarbonImmutable $today, ?string $hhmm): ?CarbonImmutable
    {
        if (!is_string($hhmm) || !preg_match('/^\d{1,2}:\d{2}$/', trim($hhmm))) {
            return null;
        }
        [$h, $m] = explode(':', trim($hhmm));
        return $today->setTime((int) $h, (int) $m, 0);
    }

    /**
     * @param array<int, array<string, mixed>> $schedule
     * @return array{human: string}|null
     */
    private function findNextOpening(array $schedule, CarbonImmutable $now): ?array
    {
        for ($offset = 0; $offset <= 7; $offset++) {
            $candidate = $now->addDays($offset);
            $entry = $this->dayEntry($schedule, $candidate);
            if (!$entry || !empty($entry['closed'])) {
                continue;
            }
            $open = $this->timeToday($candidate, $entry['open'] ?? null);
            if (!$open) {
                continue;
            }
            if ($offset === 0 && $open->lessThanOrEqualTo($now)) {
                continue;
            }
            $label = $entry['label'] ?? '';
            if ($offset === 0) {
                $human = 'azi la ' . $open->format('H:i');
            } elseif ($offset === 1) {
                $human = 'mâine la ' . $open->format('H:i');
            } else {
                $human = ($label !== '' ? mb_strtolower((string) $label) : 'în ' . $offset . ' zile') . ' la ' . $open->format('H:i');
            }
            return ['human' => $human];
        }
        return null;
    }
}
