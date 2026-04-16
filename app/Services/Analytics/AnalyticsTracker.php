<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Server-side side of the dataLayer bridge. Controllers flash an
 * event after a successful action (sign-up, purchase, lead); the
 * flash-events Blade partial reads and emits a dataLayer push on
 * the next page render.
 *
 * Pair with GTM → the container picks up the dataLayer push and
 * fans out to GA4, Google Ads, Meta Pixel based on trigger config.
 * (Reliable server-side reinforcement via Measurement Protocol +
 * Conversions API lives in Commit 3.)
 */
class AnalyticsTracker
{
    private const SESSION_KEY = '_analytics_events';

    /**
     * Queue an event for the next page render. Controllers call
     * this right before redirect; the destination page's
     * flash-events partial emits the push.
     */
    public function flash(string $name, array $params = []): void
    {
        $events = Session::get(self::SESSION_KEY, []);
        $events[] = $this->normalize($name, $params);
        Session::flash(self::SESSION_KEY, $events);
    }

    /**
     * Emit an event inline — used from view composers / middleware
     * that runs on the response being rendered. Same session key so
     * the partial picks it up in the same pass.
     */
    public function enqueue(string $name, array $params = []): void
    {
        $events = Session::get(self::SESSION_KEY, []);
        $events[] = $this->normalize($name, $params);
        Session::put(self::SESSION_KEY, $events);
    }

    /**
     * Drain the queue. Called once per request from the Blade
     * partial; the partial then renders dataLayer pushes for each.
     *
     * @return array<int, array{event: string, params: array}>
     */
    public function collect(): array
    {
        $events = Session::get(self::SESSION_KEY, []);
        Session::forget(self::SESSION_KEY);
        return $events;
    }

    private function normalize(string $name, array $params): array
    {
        // Strip anything the view shouldn't emit. Keep GA4 reserved
        // names + our custom ones. Currency defaults to USD — the
        // operator can override per-call; GA4 requires 3-letter code.
        $params = array_filter($params, fn ($v) => $v !== null && $v !== '');
        if (isset($params['value']) && !isset($params['currency'])) {
            $params['currency'] = 'USD';
        }
        return ['event' => $name, 'params' => $params];
    }
}
