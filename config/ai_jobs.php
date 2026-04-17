<?php

/*
|--------------------------------------------------------------------------
| Sampling knobs for per-call AI jobs (Iteration B)
|--------------------------------------------------------------------------
|
| AnalyzeCallSentiment and GenerateCallSummary run on every call. On a
| busy tenant that's one gpt-4o-mini call per call, two times. Most
| tenants don't actually look at per-call sentiment/summary — they use
| it for rollups. Exposing a sampling rate lets ops down-sample cheaply
| in production without a code change.
|
| 1.0 = run every time (current behavior, safe default).
| 0.25 = run on a random ~25% of calls.
| 0.0 = disable entirely.
|
| Short-duration calls (< min_duration_seconds) are always skipped
| because there's no meaningful signal in a 5s hangup.
|
*/

return [

    'sentiment_sampling_rate' => (float) env('AI_JOBS_SENTIMENT_SAMPLING_RATE', 1.0),
    'summary_sampling_rate'   => (float) env('AI_JOBS_SUMMARY_SAMPLING_RATE', 1.0),

    /*
    | Calls shorter than this are skipped regardless of sampling rate.
    | 30s is the cheapest "greeting + hangup" floor — below that the
    | transcript rarely contains anything to analyze.
    */
    'min_duration_seconds' => (int) env('AI_JOBS_MIN_DURATION_SECONDS', 30),

];
