/*
 * Single source of truth for the OpenAI Realtime event names we depend on.
 *
 * Why this file exists: on 2026-05-12 OpenAI moved Realtime out of beta and
 * renamed the output events (`response.audio.delta` →
 * `response.output_audio.delta`). The bridge kept listening for the old
 * names, so audio arrived, fell through the switch's `default:` branch at
 * LOG_LEVEL=info, and was never forwarded to Twilio. Calls were silent for
 * 96 days with no error logged anywhere — the failure mode was silence, not
 * an exception.
 *
 * The lists are consumed by BOTH openaiBridge.js (which forwards the audio)
 * and probe.js (which asserts audio actually arrives). That coupling is the
 * point: if OpenAI renames these again, the probe stops seeing frames and
 * alerts, instead of the bug hiding until someone happens to place a call.
 *
 * Keep old names in the lists. They cost nothing and keep pre-GA models
 * working if we ever pin one.
 */

/** Events carrying a base64 audio chunk for the caller, in `delta`. */
export const AUDIO_DELTA_EVENTS = Object.freeze([
    'response.output_audio.delta', // GA, post 2026-05-12
    'response.audio.delta',        // beta, pre 2026-05-12
]);

/** Events carrying assistant speech transcript text, in `delta`. */
export const TRANSCRIPT_DELTA_EVENTS = Object.freeze([
    'response.output_audio_transcript.delta', // GA
    'response.audio_transcript.delta',        // beta
]);

/** Events carrying the caller's finalised ASR text, in `transcript`. */
export const INPUT_TRANSCRIPT_DONE_EVENTS = Object.freeze([
    'conversation.item.input_audio_transcription.completed',
]);

/*
 * Events we knowingly ignore. Anything arriving that is neither handled nor
 * listed here gets logged at `warn` (once per type per call) — that is the
 * tripwire for the next rename. Keeping the list explicit means a new event
 * from OpenAI shows up as a warning we can triage, not as silence.
 */
export const BENIGN_EVENTS = Object.freeze(new Set([
    'rate_limits.updated',
    'conversation.item.created',
    'conversation.item.added',
    'conversation.item.done',
    'input_audio_buffer.committed',
    'input_audio_buffer.cleared',
    'response.content_part.done',
    'response.output_item.done',
    'response.output_audio.done',
    'response.output_audio_transcript.done',
    'response.audio.done',
    'response.audio_transcript.done',
    'response.output_text.delta',
    'response.output_text.done',
    'response.text.delta',
    'response.text.done',
    'response.function_call_arguments.delta',
    'conversation.item.input_audio_transcription.delta',
]));

export const isBenign = (type) => BENIGN_EVENTS.has(type);

export const isAudioDelta = (type) => AUDIO_DELTA_EVENTS.includes(type);
export const isTranscriptDelta = (type) => TRANSCRIPT_DELTA_EVENTS.includes(type);
export const isInputTranscriptDone = (type) => INPUT_TRANSCRIPT_DONE_EVENTS.includes(type);
