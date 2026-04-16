/*
 * Audio format conversion between Twilio and OpenAI Realtime.
 *
 * Twilio Media Streams:  mulaw (G.711), 8kHz, mono, base64 per frame.
 * OpenAI Realtime:       pcm16 (linear 16-bit), 24kHz, mono, base64.
 *
 * Neither party resamples for you, so this module does both directions:
 *
 *   decodeTwilioFrame(base64)  → Buffer of PCM16 @ 24kHz
 *   encodeTwilioFrame(pcm24k)  → base64 mulaw @ 8kHz
 *
 * The mulaw tables and resampling are inlined to keep the service
 * zero-native-deps (so the container image stays small and boots
 * fast under Coolify).
 */

// μ-law decode: 8-bit companded → 16-bit linear PCM. Table derived
// from ITU-T G.711 spec; matches libavcodec / sox reference output.
const MULAW_DECODE_TABLE = new Int16Array(256);
(function buildMulawDecodeTable() {
    for (let i = 0; i < 256; i++) {
        const m = ~i & 0xff;
        const sign = (m & 0x80) ? -1 : 1;
        const exponent = (m >> 4) & 0x07;
        const mantissa = m & 0x0f;
        const magnitude = ((mantissa << 4) + 0x08) << exponent;
        MULAW_DECODE_TABLE[i] = sign * (magnitude - 0x84);
    }
})();

function mulawToPcm16(mulawBuf) {
    const out = new Int16Array(mulawBuf.length);
    for (let i = 0; i < mulawBuf.length; i++) {
        out[i] = MULAW_DECODE_TABLE[mulawBuf[i]];
    }
    return out;
}

// PCM16 → μ-law: inverse of the table above, also G.711 reference.
function pcm16ToMulaw(pcm) {
    const out = Buffer.alloc(pcm.length);
    for (let i = 0; i < pcm.length; i++) {
        let sample = pcm[i];
        let sign = 0;
        if (sample < 0) { sample = -sample; sign = 0x80; }
        sample = Math.min(sample + 0x84, 0x7fff);

        let exponent = 7;
        for (let mask = 0x4000; (sample & mask) === 0 && exponent > 0; mask >>= 1) exponent--;
        const mantissa = (sample >> (exponent + 3)) & 0x0f;
        out[i] = ~(sign | (exponent << 4) | mantissa) & 0xff;
    }
    return out;
}

// Linear upsampling 8kHz → 24kHz (3x). Cheap and good enough for
// speech — the alternative (sinc / polyphase) adds CPU for no
// perceptual improvement at voice bandwidth.
function upsample3x(pcm8k) {
    const out = new Int16Array(pcm8k.length * 3);
    for (let i = 0; i < pcm8k.length; i++) {
        const a = pcm8k[i];
        const b = i + 1 < pcm8k.length ? pcm8k[i + 1] : a;
        out[i * 3] = a;
        out[i * 3 + 1] = Math.round(a + (b - a) * (1 / 3));
        out[i * 3 + 2] = Math.round(a + (b - a) * (2 / 3));
    }
    return out;
}

// Downsampling 24kHz → 8kHz (1/3). Simple averaging; OpenAI delivers
// bandwidth-limited speech, no aliasing concern in practice.
function downsample3x(pcm24k) {
    const outLen = Math.floor(pcm24k.length / 3);
    const out = new Int16Array(outLen);
    for (let i = 0; i < outLen; i++) {
        const a = pcm24k[i * 3];
        const b = pcm24k[i * 3 + 1];
        const c = pcm24k[i * 3 + 2];
        out[i] = Math.round((a + b + c) / 3);
    }
    return out;
}

/**
 * Twilio → OpenAI direction: base64 mulaw 8k → base64 PCM16 24k.
 * Returns a base64 string ready to go into
 * { type: 'input_audio_buffer.append', audio: '...' }.
 */
export function decodeTwilioFrameToOpenai(base64Mulaw) {
    const mulaw = Buffer.from(base64Mulaw, 'base64');
    const pcm8k = mulawToPcm16(mulaw);
    const pcm24k = upsample3x(pcm8k);
    return Buffer.from(pcm24k.buffer, pcm24k.byteOffset, pcm24k.byteLength).toString('base64');
}

/**
 * OpenAI → Twilio direction: base64 PCM16 24k → base64 mulaw 8k.
 * The result goes into the `payload` field of a Twilio media frame.
 */
export function encodeOpenaiFrameToTwilio(base64Pcm24k) {
    const pcm24Buf = Buffer.from(base64Pcm24k, 'base64');
    const pcm24 = new Int16Array(pcm24Buf.buffer, pcm24Buf.byteOffset, pcm24Buf.byteLength / 2);
    const pcm8 = downsample3x(pcm24);
    const mulaw = pcm16ToMulaw(pcm8);
    return mulaw.toString('base64');
}
