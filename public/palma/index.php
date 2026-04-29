<?php
// Palma — citit în palmă cu AI. Custom PHP, fără Laravel.
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Palma — citit în palmă cu AI</title>
<meta name="robots" content="noindex,nofollow">
<meta name="theme-color" content="#0a0420">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<style>
  :root {
    --bg-deep: #0a0420;
    --bg-mid:  #1a0935;
    --bg-up:   #2a0f4f;
    --gold:    #e9c47e;
    --gold-2:  #d4a857;
    --cyan:    #6fe9ff;
    --magenta: #ff7be0;
    --ink:     #f5e6c8;
    --dim:     rgba(245,230,200,.55);
    --glass:   rgba(255,255,255,.04);
    --glass-2: rgba(255,255,255,.08);
    --line:    rgba(212,168,87,.25);
  }
  * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
  html, body { margin: 0; padding: 0; }
  body {
    min-height: 100dvh; color: var(--ink);
    font-family: "Cormorant Garamond", Georgia, serif;
    font-size: 1.0625rem; line-height: 1.6;
    background: var(--bg-deep);
    overflow-x: hidden;
    position: relative;
  }
  h1, h2, .display { font-family: "Cinzel", serif; letter-spacing: .02em; font-weight: 500; }

  /* Animated cosmic background — 3 layers */
  .cosmos {
    position: fixed; inset: 0; z-index: -2; overflow: hidden;
    background:
      radial-gradient(ellipse 80% 60% at 30% 20%, rgba(120,60,200,.35), transparent 60%),
      radial-gradient(ellipse 70% 50% at 80% 80%, rgba(255,90,200,.18), transparent 60%),
      radial-gradient(ellipse 100% 70% at 50% 100%, rgba(60,180,255,.15), transparent 60%),
      linear-gradient(180deg, #0a0420 0%, #150830 50%, #0a0420 100%);
  }
  .stars { position: fixed; inset: 0; z-index: -1; pointer-events: none; }
  .grain { position: fixed; inset: 0; z-index: -1; pointer-events: none;
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='160' height='160'><filter id='n'><feTurbulence baseFrequency='0.9' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  0 0 0 .06 0'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>");
    opacity: .35; mix-blend-mode: overlay;
  }

  .screen { display: none; min-height: 100dvh; padding: 2rem 1.25rem 4rem; position: relative; }
  .screen.active { display: flex; flex-direction: column; }
  .wrap { max-width: 760px; margin: 0 auto; width: 100%; flex: 1; display: flex; flex-direction: column; justify-content: center; }

  /* ─── Welcome ─── */
  .hero { text-align: center; }
  .hero .eyebrow {
    display: inline-block;
    text-transform: uppercase; letter-spacing: .35em; font-size: .75rem;
    color: var(--gold); padding: .35rem .85rem;
    border: 1px solid var(--line); border-radius: 999px;
    background: rgba(212,168,87,.06);
    margin-bottom: 1.5rem;
  }
  .hero h1 {
    font-size: clamp(2.5rem, 7vw, 4.5rem); line-height: 1.05; margin: 0 0 1.25rem;
    background: linear-gradient(110deg, #fff, var(--gold) 30%, var(--magenta) 55%, var(--cyan) 75%, var(--gold) 100%);
    background-size: 200% 100%;
    -webkit-background-clip: text; background-clip: text; color: transparent;
    animation: shimmer 6s linear infinite;
    text-shadow: 0 0 40px rgba(212,168,87,.15);
  }
  @keyframes shimmer { 0% { background-position: 0% 50%; } 100% { background-position: 200% 50%; } }
  .hero p.lead { font-size: 1.25rem; color: var(--dim); max-width: 540px; margin: 0 auto 2.25rem; font-style: italic; }

  .palm-icon {
    width: 200px; height: 200px; margin: 0 auto 2rem; position: relative;
    display: flex; align-items: center; justify-content: center;
  }
  .palm-icon svg { width: 100%; height: 100%; filter: drop-shadow(0 0 24px rgba(212,168,87,.5)); }
  .palm-icon::before {
    content: ""; position: absolute; inset: -10%;
    background: radial-gradient(circle, rgba(212,168,87,.25), transparent 65%);
    animation: breathe 4s ease-in-out infinite;
    border-radius: 50%;
  }
  @keyframes breathe { 0%, 100% { transform: scale(.95); opacity: .6; } 50% { transform: scale(1.08); opacity: 1; } }

  .actions { display: flex; gap: .85rem; flex-wrap: wrap; justify-content: center; }
  button.primary {
    appearance: none; border: none; cursor: pointer;
    background: linear-gradient(135deg, var(--gold) 0%, #b8893d 100%);
    color: #1a0d2e; font-family: "Cinzel", serif; font-weight: 600;
    padding: 1.05rem 2.25rem; border-radius: 999px;
    font-size: 1rem; letter-spacing: .15em; text-transform: uppercase;
    box-shadow:
      0 12px 32px rgba(212,168,87,.3),
      0 0 0 1px rgba(245,230,200,.2) inset,
      0 0 60px rgba(212,168,87,.12);
    transition: transform .2s, box-shadow .2s;
    position: relative; overflow: hidden;
  }
  button.primary::before {
    content: ""; position: absolute; inset: 0;
    background: linear-gradient(110deg, transparent 30%, rgba(255,255,255,.5) 50%, transparent 70%);
    transform: translateX(-100%); transition: transform .8s;
  }
  button.primary:hover::before { transform: translateX(100%); }
  button.primary:hover { transform: translateY(-3px); box-shadow: 0 16px 40px rgba(212,168,87,.45), 0 0 0 1px rgba(245,230,200,.3) inset, 0 0 80px rgba(212,168,87,.2); }
  button.primary:active { transform: translateY(-1px); }
  button.primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }

  button.ghost {
    appearance: none; background: transparent;
    border: 1px solid var(--line); color: var(--ink);
    font-family: "Cinzel", serif; font-weight: 500;
    padding: .9rem 1.6rem; border-radius: 999px; cursor: pointer;
    font-size: .85rem; letter-spacing: .15em; text-transform: uppercase;
    transition: all .2s;
  }
  button.ghost:hover { border-color: var(--gold); color: var(--gold); background: rgba(212,168,87,.08); }

  .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 3rem; }
  .feature { padding: 1rem; text-align: center; }
  .feature .ico { font-family: "Cinzel", serif; font-size: 1.5rem; color: var(--gold); margin-bottom: .35rem; }
  .feature .lbl { font-size: .9rem; color: var(--dim); }

  /* ─── Scan ─── */
  .stage-wrap { display: flex; flex-direction: column; align-items: center; gap: 1.25rem; }
  .stage {
    position: relative; width: 100%; max-width: 640px;
    aspect-ratio: 4/3;
    border-radius: 28px; overflow: hidden;
    background: #000;
    border: 1px solid var(--line);
    box-shadow:
      0 0 0 1px rgba(212,168,87,.15),
      0 30px 80px rgba(0,0,0,.6),
      0 0 120px rgba(120,60,200,.2);
  }
  .stage video, .stage canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    object-fit: cover;
  }
  .stage video.mirrored { transform: scaleX(-1); }    /* only when front cam */
  .stage canvas { pointer-events: none; }

  .stage .corner {
    position: absolute; width: 36px; height: 36px;
    border: 2px solid var(--gold); pointer-events: none;
    opacity: .85;
  }
  .corner.tl { top: 18px; left: 18px;  border-right: 0; border-bottom: 0; border-top-left-radius: 12px; }
  .corner.tr { top: 18px; right: 18px; border-left: 0;  border-bottom: 0; border-top-right-radius: 12px; }
  .corner.bl { bottom: 18px; left: 18px;  border-right: 0; border-top: 0; border-bottom-left-radius: 12px; }
  .corner.br { bottom: 18px; right: 18px; border-left: 0;  border-top: 0; border-bottom-right-radius: 12px; }

  .status-pill {
    position: absolute; top: 18px; left: 50%; transform: translateX(-50%);
    background: rgba(0,0,0,.55); backdrop-filter: blur(8px);
    border: 1px solid var(--line); border-radius: 999px;
    padding: .5rem 1.1rem; font-size: .825rem; letter-spacing: .12em; text-transform: uppercase;
    color: var(--ink); display: flex; align-items: center; gap: .55rem;
    z-index: 5;
  }
  .status-pill .pulse { width: 8px; height: 8px; border-radius: 50%; background: var(--gold); box-shadow: 0 0 12px var(--gold); animation: blink 1.4s infinite; }
  .status-pill.ok .pulse { background: var(--cyan); box-shadow: 0 0 12px var(--cyan); }
  .status-pill.scan .pulse { background: var(--magenta); box-shadow: 0 0 16px var(--magenta); }
  @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: .3; } }

  .scan-title { font-size: 1.5rem; color: var(--gold); margin: 0; text-align: center; font-weight: 500; }
  .scan-help  { font-size: .95rem; color: var(--dim); text-align: center; margin: 0; }

  /* Live readout panel during scan */
  .readout {
    position: absolute; bottom: 18px; left: 18px; right: 18px;
    background: rgba(0,0,0,.55); backdrop-filter: blur(10px);
    border: 1px solid var(--line); border-radius: 14px;
    padding: .65rem .85rem;
    font-family: "Cinzel", serif; font-size: .75rem; letter-spacing: .1em; text-transform: uppercase;
    color: var(--ink); opacity: 0; transition: opacity .4s;
    z-index: 5;
  }
  .readout.show { opacity: 1; }
  .readout-row { display: flex; justify-content: space-between; padding: .15rem 0; }
  .readout-row span:last-child { color: var(--gold); }

  /* Final flash */
  .flash {
    position: fixed; inset: 0; background: #fff; z-index: 100;
    opacity: 0; pointer-events: none; transition: opacity .15s;
  }

  /* ─── Result ─── */
  .result-hero { text-align: center; padding: 2rem 0 2.5rem; }
  .result-hero h1 { font-size: clamp(2rem, 5vw, 3rem); margin: 0 0 .75rem;
    background: linear-gradient(110deg, var(--gold), var(--magenta), var(--cyan), var(--gold));
    background-size: 200% 100%;
    -webkit-background-clip: text; background-clip: text; color: transparent;
    animation: shimmer 8s linear infinite;
  }
  .result-hero .tagline { font-style: italic; color: var(--ink); font-size: 1.25rem; max-width: 520px; margin: 0 auto; }

  .stats {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: .85rem; margin: 0 0 2rem;
  }
  .stat {
    background: var(--glass);
    border: 1px solid var(--line); border-radius: 16px;
    padding: 1rem; text-align: center;
    backdrop-filter: blur(8px);
    position: relative; overflow: hidden;
  }
  .stat::before {
    content: ""; position: absolute; inset: 0;
    background: radial-gradient(circle at 50% 100%, rgba(212,168,87,.18), transparent 60%);
    opacity: 0; transition: opacity .4s;
  }
  .stat:hover::before { opacity: 1; }
  .stat .lbl { font-family: "Cinzel", serif; font-size: .7rem; letter-spacing: .2em; text-transform: uppercase; color: var(--dim); }
  .stat .val {
    font-family: "Cinzel", serif; font-size: 2.2rem; font-weight: 500; margin-top: .35rem;
    background: linear-gradient(135deg, var(--gold), var(--magenta));
    -webkit-background-clip: text; background-clip: text; color: transparent;
  }
  .stat .val::after { content: "%"; font-size: 1rem; margin-left: 2px; opacity: .7; }
  .stat .bar {
    height: 3px; background: rgba(255,255,255,.08); border-radius: 99px; margin-top: .65rem; overflow: hidden;
  }
  .stat .bar > span {
    display: block; height: 100%;
    background: linear-gradient(90deg, var(--gold), var(--magenta));
    width: 0; transition: width 1.6s cubic-bezier(.2,.8,.2,1);
  }

  .reading-section {
    background: var(--glass);
    border: 1px solid var(--line);
    border-radius: 22px; padding: 1.5rem 1.75rem; margin-bottom: 1rem;
    backdrop-filter: blur(12px);
    position: relative; overflow: hidden;
    opacity: 0; transform: translateY(12px);
    animation: fadeUp .7s forwards;
  }
  .reading-section:nth-child(1) { animation-delay: .1s; }
  .reading-section:nth-child(2) { animation-delay: .25s; }
  .reading-section:nth-child(3) { animation-delay: .4s; }
  .reading-section:nth-child(4) { animation-delay: .55s; }
  .reading-section:nth-child(5) { animation-delay: .7s; }
  @keyframes fadeUp { to { opacity: 1; transform: none; } }
  .reading-section::before {
    content: ""; position: absolute; left: 0; top: 0; width: 4px; height: 100%;
    background: linear-gradient(180deg, var(--gold), var(--magenta), var(--cyan));
    opacity: .85;
  }
  .reading-section h2 {
    margin: 0 0 .65rem; font-size: 1.35rem; color: var(--gold);
    display: flex; align-items: center; gap: .65rem;
  }
  .reading-section h2 .num {
    font-family: "Cinzel", serif; font-size: .75rem;
    border: 1px solid var(--line); border-radius: 50%;
    width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center;
    color: var(--dim);
  }
  .reading-section p { margin: 0; font-size: 1.0625rem; color: var(--ink); line-height: 1.7; }

  .loading { display: flex; gap: .55rem; align-items: center; justify-content: center; color: var(--dim); padding: 3rem 0; }
  .loading .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--gold); animation: pulse 1.4s infinite ease-in-out; }
  .loading .dot:nth-child(2) { animation-delay: .2s; }
  .loading .dot:nth-child(3) { animation-delay: .4s; }
  @keyframes pulse { 0%, 80%, 100% { opacity: .25; transform: scale(.7); } 40% { opacity: 1; transform: scale(1.1); } }

  .footer-actions {
    display: flex; gap: .85rem; flex-wrap: wrap; justify-content: center;
    margin-top: 2rem; padding-top: 2rem;
    border-top: 1px solid rgba(212,168,87,.1);
  }

  .err {
    background: rgba(255,90,90,.08); border: 1px solid rgba(255,90,90,.3);
    color: #ffb0b0; padding: 1rem 1.25rem; border-radius: 12px; margin: 1rem 0;
    font-size: .95rem;
  }

  footer.brand {
    text-align: center; padding: 3rem 1rem 1.5rem;
    color: var(--dim); font-size: .8rem; letter-spacing: .1em;
  }
  footer.brand .sep { margin: 0 .65rem; opacity: .4; }

  @media (max-width: 540px) {
    .stage { aspect-ratio: 3/4; }
    .palm-icon { width: 160px; height: 160px; }
  }
</style>
</head>
<body>

<div class="cosmos"></div>
<canvas class="stars" id="stars"></canvas>
<div class="grain"></div>

<!-- ════════ WELCOME ════════ -->
<section id="screen-welcome" class="screen active">
  <div class="wrap hero">
    <span class="eyebrow">✦  Mistic · AI · Personalizat  ✦</span>

    <div class="palm-icon">
      <svg viewBox="0 0 200 200" fill="none" stroke="url(#gpalm)" stroke-width="1.5" stroke-linecap="round">
        <defs>
          <linearGradient id="gpalm" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#e9c47e"/>
            <stop offset="50%" stop-color="#ff7be0"/>
            <stop offset="100%" stop-color="#6fe9ff"/>
          </linearGradient>
        </defs>
        <!-- palm outline -->
        <path d="M70 170 C 60 150, 55 130, 60 110 L 60 80 Q 62 70, 72 72 L 72 60 Q 75 48, 85 48 L 87 38 Q 90 28, 100 28 Q 110 28, 113 38 L 115 50 Q 118 42, 128 44 L 130 65 Q 138 58, 145 65 L 145 100 C 145 130, 140 155, 130 170 Z"/>
        <!-- heart line -->
        <path d="M70 95 Q 95 88, 130 95" stroke-dasharray="2 3" opacity=".7"/>
        <!-- head line -->
        <path d="M70 115 Q 95 110, 132 120" stroke-dasharray="2 3" opacity=".7"/>
        <!-- life line -->
        <path d="M85 75 Q 75 110, 80 155" stroke-dasharray="2 3" opacity=".7"/>
      </svg>
    </div>

    <h1>Citește-ți palma<br>cu AI</h1>
    <p class="lead">Așază mâna în fața camerei. Inteligența artificială măsoară liniile palmei tale și îți dezvăluie ce spun ele despre tine — în doar câteva secunde.</p>

    <div class="actions">
      <button class="primary" onclick="startFlow()">Începe analiza</button>
      <button class="ghost" onclick="document.getElementById('how').scrollIntoView({behavior:'smooth'})">Cum funcționează</button>
    </div>

    <div class="features" id="how">
      <div class="feature"><div class="ico">✦</div><div class="lbl">Detecție 21<br>puncte palmă</div></div>
      <div class="feature"><div class="ico">⌬</div><div class="lbl">Analiză locală<br>în browser</div></div>
      <div class="feature"><div class="ico">⚝</div><div class="lbl">Citire generată<br>de AI</div></div>
      <div class="feature"><div class="ico">∞</div><div class="lbl">Fără cont,<br>fără tracking</div></div>
    </div>
  </div>
</section>

<!-- ════════ SCAN ════════ -->
<section id="screen-scan" class="screen">
  <div class="wrap stage-wrap">
    <h1 class="scan-title display">Analiză palmă</h1>
    <p class="scan-help" id="scan-help">Permite accesul la cameră pentru a începe.</p>

    <div class="stage" id="stage">
      <video id="cam" autoplay muted playsinline></video>
      <canvas id="overlay"></canvas>
      <div class="corner tl"></div><div class="corner tr"></div>
      <div class="corner bl"></div><div class="corner br"></div>
      <div class="status-pill" id="status">
        <span class="pulse"></span><span id="status-text">Așteptăm camera…</span>
      </div>
      <div class="readout" id="readout">
        <div class="readout-row"><span>Stabilitate</span><span id="ro-stab">—</span></div>
        <div class="readout-row"><span>Lățime palmă</span><span id="ro-pw">—</span></div>
        <div class="readout-row"><span>Mână</span><span id="ro-hand">—</span></div>
      </div>
    </div>

    <div id="scan-error" class="err" style="display:none"></div>

    <div class="actions">
      <button class="ghost" onclick="cancelScan()">Anulează</button>
    </div>
  </div>
</section>

<!-- ════════ RESULT ════════ -->
<section id="screen-result" class="screen">
  <div class="wrap" id="result-wrap">
    <div class="result-hero">
      <span class="eyebrow">✦  Citirea ta  ✦</span>
      <h1>Ce spune palma ta</h1>
      <p class="tagline" id="tagline">—</p>
    </div>
    <div id="result-loading" class="loading">
      <span class="dot"></span><span class="dot"></span><span class="dot"></span>
      <span style="margin-left:.5rem">Se interpretează liniile…</span>
    </div>
    <div id="result-content" style="display:none">
      <div class="stats" id="stats"></div>
      <div id="sections"></div>
    </div>
    <div class="footer-actions">
      <button class="primary" onclick="resetFlow()">Mai încearcă</button>
      <button class="ghost" onclick="saveAsPDF()" id="save-btn">Salvează ca PDF</button>
    </div>
  </div>
</section>

<div class="flash" id="flash"></div>

<footer class="brand">
  Palma · Citit în palmă cu AI<span class="sep">·</span><?= date('Y') ?>
</footer>

<!-- MediaPipe Hands from CDN — more stable than npm version -->
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/hands@0.4/hands.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous"></script>

<script>
// ════════════════════════════════════════════════════════════════════════════
//  PALMA — MAIN APP SCRIPT
// ════════════════════════════════════════════════════════════════════════════

// ─── State machine ─────────────────────────────────────────────────────────
const Screen = { current: 'welcome' };
function goTo(name) {
  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
  document.getElementById('screen-' + name).classList.add('active');
  Screen.current = name;
  window.scrollTo({ top: 0, behavior: 'instant' });
}

// ─── Animated starfield (welcome / result backdrop) ────────────────────────
(() => {
  const cv = document.getElementById('stars');
  const ctx = cv.getContext('2d');
  let stars = [], dpr = Math.min(window.devicePixelRatio || 1, 2);
  function resize() {
    cv.width  = innerWidth  * dpr;
    cv.height = innerHeight * dpr;
    cv.style.width = innerWidth + 'px';
    cv.style.height = innerHeight + 'px';
    const count = Math.floor((innerWidth * innerHeight) / 6000);
    stars = Array.from({length: count}, () => ({
      x: Math.random() * cv.width,
      y: Math.random() * cv.height,
      r: Math.random() * 1.5 * dpr + .3 * dpr,
      a: Math.random(),
      sp: Math.random() * .015 + .005,
      hue: Math.random() < .15 ? (Math.random() < .5 ? 'cyan' : 'magenta') : 'gold'
    }));
  }
  resize(); addEventListener('resize', resize);
  function tick() {
    ctx.clearRect(0, 0, cv.width, cv.height);
    for (const s of stars) {
      s.a += s.sp; if (s.a > 1) s.sp *= -1; if (s.a < 0) s.sp *= -1;
      const c = s.hue === 'cyan' ? `rgba(111,233,255,${s.a*.9})`
              : s.hue === 'magenta' ? `rgba(255,123,224,${s.a*.85})`
              : `rgba(255,235,180,${s.a})`;
      ctx.fillStyle = c;
      ctx.beginPath(); ctx.arc(s.x, s.y, s.r, 0, Math.PI*2); ctx.fill();
    }
    requestAnimationFrame(tick);
  }
  tick();
})();

// ─── Audio (subtle scan synth) ─────────────────────────────────────────────
const Audio = {
  ctx: null, master: null, drone: null, droneGain: null,
  init() {
    if (this.ctx) return;
    this.ctx = new (window.AudioContext || window.webkitAudioContext)();
    this.master = this.ctx.createGain();
    this.master.gain.value = .25;
    this.master.connect(this.ctx.destination);
  },
  drone(start = true) {
    if (!this.ctx) return;
    if (start && !this.droneOsc) {
      const o = this.ctx.createOscillator(); o.type = 'sine'; o.frequency.value = 110;
      const o2 = this.ctx.createOscillator(); o2.type = 'sine'; o2.frequency.value = 138.6; // perfect 5th-ish
      const g = this.ctx.createGain(); g.gain.value = 0;
      o.connect(g); o2.connect(g); g.connect(this.master);
      o.start(); o2.start();
      g.gain.linearRampToValueAtTime(.12, this.ctx.currentTime + 1.2);
      this.droneOsc = [o, o2]; this.droneGain = g;
    }
    if (!start && this.droneOsc) {
      this.droneGain.gain.linearRampToValueAtTime(0, this.ctx.currentTime + .8);
      this.droneOsc.forEach(o => o.stop(this.ctx.currentTime + 1));
      this.droneOsc = null;
    }
  },
  sweep(durationMs = 2000) {
    if (!this.ctx) return;
    const t = this.ctx.currentTime;
    const o = this.ctx.createOscillator(); o.type = 'sawtooth';
    const f = this.ctx.createBiquadFilter(); f.type = 'bandpass'; f.Q.value = 12;
    const g = this.ctx.createGain(); g.gain.value = 0;
    o.connect(f); f.connect(g); g.connect(this.master);
    o.frequency.setValueAtTime(220, t);
    o.frequency.exponentialRampToValueAtTime(880, t + durationMs/1000);
    f.frequency.setValueAtTime(400, t);
    f.frequency.exponentialRampToValueAtTime(2000, t + durationMs/1000);
    g.gain.linearRampToValueAtTime(.08, t + .15);
    g.gain.linearRampToValueAtTime(0, t + durationMs/1000);
    o.start(t); o.stop(t + durationMs/1000 + .05);
  },
  ping(freq = 880, durMs = 320) {
    if (!this.ctx) return;
    const t = this.ctx.currentTime;
    const o = this.ctx.createOscillator(); o.type = 'sine'; o.frequency.value = freq;
    const g = this.ctx.createGain(); g.gain.value = 0;
    o.connect(g); g.connect(this.master);
    g.gain.linearRampToValueAtTime(.12, t + .02);
    g.gain.exponentialRampToValueAtTime(.001, t + durMs/1000);
    o.start(t); o.stop(t + durMs/1000 + .05);
  },
};

// ─── MediaPipe + scan flow ─────────────────────────────────────────────────
const Scan = {
  hands: null,
  videoEl: null,
  overlayEl: null,
  rafId: null,
  cameraTrack: null,
  lastResults: null,
  stableSince: 0,
  stableHistory: [],   // last N centroid positions
  STABILITY_WINDOW_MS: 1500,
  STABILITY_THRESHOLD: 0.025,  // normalized
  scanning: false,
  measurements: null,
  capturedFrame: null,
  isFrontCamera: false,

  async start() {
    this.videoEl = document.getElementById('cam');
    this.overlayEl = document.getElementById('overlay');
    this.scanning = false;
    this.stableSince = 0;
    this.stableHistory = [];

    setStatus('Așteptăm camera…', '');

    // 1. Camera permission — prefer back camera (better for palm), fall back to front
    let stream;
    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: { ideal: 'environment' },
          width:  { ideal: 1280 },
          height: { ideal: 720 },
        },
        audio: false,
      });
    } catch (e) {
      try {
        stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
          audio: false,
        });
      } catch (e2) {
        showError('Nu am putut accesa camera. Verifică permisiunile browser-ului.');
        return;
      }
    }
    this.cameraTrack = stream.getVideoTracks()[0];
    // Detect actual camera direction so we know whether to mirror
    const settings = this.cameraTrack.getSettings ? this.cameraTrack.getSettings() : {};
    this.isFrontCamera = settings.facingMode ? settings.facingMode === 'user' : true;
    this.videoEl.classList.toggle('mirrored', this.isFrontCamera);
    this.videoEl.srcObject = stream;
    await new Promise(r => this.videoEl.onloadedmetadata = r);
    await this.videoEl.play();
    this.resizeOverlay();
    addEventListener('resize', () => this.resizeOverlay());

    // 2. Init MediaPipe
    setStatus('Încarcă modelul…', '');
    if (!window.Hands) { showError('Modelul nu s-a încărcat.'); return; }
    this.hands = new Hands({
      locateFile: f => `https://cdn.jsdelivr.net/npm/@mediapipe/hands@0.4/${f}`,
    });
    this.hands.setOptions({
      maxNumHands: 1, modelComplexity: 1,
      minDetectionConfidence: 0.65, minTrackingConfidence: 0.55,
    });
    this.hands.onResults(r => this.onResults(r));

    setStatus('Așază palma în cadru', '');
    document.getElementById('scan-help').textContent =
      'Ține palma deschisă, la ~30 cm, cu degetele întinse.';

    // 3. Init audio after user gesture
    Audio.init(); Audio.drone(true);

    // 4. Start frame loop
    const loop = async () => {
      if (Screen.current !== 'scan') return;
      if (this.videoEl.readyState >= 2) {
        try { await this.hands.send({ image: this.videoEl }); } catch (_) {}
      }
      this.rafId = requestAnimationFrame(loop);
    };
    loop();
  },

  resizeOverlay() {
    const v = this.videoEl, c = this.overlayEl;
    const rect = v.getBoundingClientRect();
    const dpr = Math.min(devicePixelRatio || 1, 2);
    c.width = rect.width * dpr; c.height = rect.height * dpr;
    c.style.width = rect.width + 'px'; c.style.height = rect.height + 'px';
    c.getContext('2d').setTransform(dpr, 0, 0, dpr, 0, 0);
  },

  onResults(results) {
    if (this.scanning) return;
    const ctx = this.overlayEl.getContext('2d');
    const W = this.overlayEl.width / (window.devicePixelRatio||1);
    const H = this.overlayEl.height / (window.devicePixelRatio||1);
    ctx.clearRect(0, 0, W, H);

    const lm = results.multiHandLandmarks?.[0];
    if (!lm) {
      // Draw guide silhouette
      this.drawGuide(ctx, W, H);
      this.stableSince = 0; this.stableHistory = [];
      setStatus('Așază palma în cadru', '');
      return;
    }

    // Mirror x only when front camera (video is mirrored visually then)
    const pts = this.isFrontCamera
      ? lm.map(p => ({ x: 1 - p.x, y: p.y, z: p.z }))
      : lm.map(p => ({ x: p.x,     y: p.y, z: p.z }));

    // Compute palm centroid
    const cx = pts.reduce((a, p) => a + p.x, 0) / pts.length;
    const cy = pts.reduce((a, p) => a + p.y, 0) / pts.length;

    // Track stability
    const now = performance.now();
    this.stableHistory.push({ cx, cy, t: now });
    while (this.stableHistory.length && (now - this.stableHistory[0].t) > this.STABILITY_WINDOW_MS) {
      this.stableHistory.shift();
    }
    let stable = false;
    if (this.stableHistory.length > 5) {
      const xs = this.stableHistory.map(h => h.cx), ys = this.stableHistory.map(h => h.cy);
      const dx = Math.max(...xs) - Math.min(...xs), dy = Math.max(...ys) - Math.min(...ys);
      stable = dx < this.STABILITY_THRESHOLD && dy < this.STABILITY_THRESHOLD;
    }

    // Draw landmarks
    this.drawLandmarks(ctx, pts, W, H);

    // Live readout
    const palmW = Math.hypot(pts[5].x - pts[17].x, pts[5].y - pts[17].y);
    const stabPct = Math.min(100, Math.round((this.stableHistory.length / 30) * 100));
    document.getElementById('readout').classList.add('show');
    document.getElementById('ro-stab').textContent = stabPct + '%';
    document.getElementById('ro-pw').textContent = (palmW * 100).toFixed(1) + 'u';
    const handed = results.multiHandedness?.[0]?.label;
    // MediaPipe assumes mirrored input (selfie). With front cam we mirror visually,
    // so flip the label. With back cam, label is already correct.
    let handLabel = '—';
    if (handed === 'Right') handLabel = this.isFrontCamera ? 'Stângă' : 'Dreaptă';
    else if (handed === 'Left') handLabel = this.isFrontCamera ? 'Dreaptă' : 'Stângă';
    document.getElementById('ro-hand').textContent = handLabel;

    if (stable) {
      if (!this.stableTimer) {
        this.stableTimer = now;
        setStatus('Detectat — stabilizare…', 'ok');
      } else if (now - this.stableTimer > this.STABILITY_WINDOW_MS) {
        this.stableTimer = null;
        this.beginScanSequence(pts, results);
      }
    } else {
      this.stableTimer = null;
      setStatus('Detectat — ține palma stabilă', 'ok');
    }
  },

  drawGuide(ctx, W, H) {
    ctx.save();
    const cx = W/2, cy = H/2;
    const t = performance.now() / 1000;
    const pulse = 0.5 + 0.5 * Math.sin(t * 2);
    ctx.strokeStyle = `rgba(212,168,87,${0.25 + pulse*0.25})`;
    ctx.lineWidth = 2; ctx.setLineDash([6, 8]);
    // simple oval guide
    ctx.beginPath();
    ctx.ellipse(cx, cy, W*0.22, H*0.32, 0, 0, Math.PI*2);
    ctx.stroke();
    ctx.setLineDash([]);
    ctx.fillStyle = `rgba(212,168,87,${0.5 + pulse*0.3})`;
    ctx.font = '500 14px Cinzel, serif';
    ctx.textAlign = 'center';
    ctx.fillText('☉  AȘAZĂ PALMA AICI  ☉', cx, cy + H*0.4);
    ctx.restore();
  },

  drawLandmarks(ctx, pts, W, H) {
    // Connections
    const conn = [
      [0,1],[1,2],[2,3],[3,4],
      [0,5],[5,6],[6,7],[7,8],
      [5,9],[9,10],[10,11],[11,12],
      [9,13],[13,14],[14,15],[15,16],
      [13,17],[17,18],[18,19],[19,20],
      [0,17],
    ];
    ctx.lineWidth = 1.5; ctx.strokeStyle = 'rgba(111,233,255,0.65)';
    ctx.beginPath();
    for (const [a, b] of conn) {
      ctx.moveTo(pts[a].x * W, pts[a].y * H);
      ctx.lineTo(pts[b].x * W, pts[b].y * H);
    }
    ctx.stroke();
    // Points
    for (let i = 0; i < pts.length; i++) {
      const p = pts[i];
      const x = p.x * W, y = p.y * H;
      ctx.fillStyle = 'rgba(233,196,126,0.95)';
      ctx.beginPath(); ctx.arc(x, y, 3.5, 0, Math.PI*2); ctx.fill();
      ctx.fillStyle = 'rgba(233,196,126,0.18)';
      ctx.beginPath(); ctx.arc(x, y, 8, 0, Math.PI*2); ctx.fill();
    }
  },

  // ─── Build the three palm lines from landmarks ───────────────────────────
  //
  // Defines a palm-local coordinate system:
  //   u ∈ [0,1]: horizontal across MCP arch — 0 = index MCP, 1 = pinky MCP
  //              (interpolates through 5 → 9 → 13 → 17, following the natural arch)
  //   v ∈ [0,1]: vertical from MCP arch (0) down to wrist (1)
  //
  // This keeps lines anchored to the palm trapezoid regardless of hand
  // orientation, tilt, or distance. Lines are then sampled as Catmull-Rom
  // splines for smooth organic curves instead of angular polylines.
  computePalmLines(pts) {
    const lerp = (a, b, t) => ({ x: a.x + (b.x - a.x) * t, y: a.y + (b.y - a.y) * t });
    const wrist    = pts[0];
    const thumbCMC = pts[1];
    const thumbMCP = pts[2];
    const indexMCP = pts[5];
    const pinkyMCP = pts[17];

    // Use the MCP arch as the top edge of the palm trapezoid, but EXTEND the
    // radial side outward toward the thumb base so the trapezoid actually
    // covers the visible palm (otherwise it sits only between fingers).
    const radialTop = lerp(indexMCP, thumbMCP, 0.40);   // pulled out toward thumb
    const ulnarTop  = pinkyMCP;
    // Also extend the bottom — wrist landmark is at the *crease*, but the palm
    // visually continues a bit lower. Use a synthetic wrist anchor.
    const wristR    = lerp(wrist, thumbCMC, 0.20);      // wrist anchor, thumb side
    const wristU    = wrist;                            // wrist anchor, pinky side

    // arch interpolation (radialTop → middleMCP → ringMCP → ulnarTop)
    const arch = [radialTop, pts[9], pts[13], ulnarTop];
    const archAt = (u) => {
      if (u <= 0) return arch[0];
      if (u >= 1) return arch[3];
      const idx = u * 3, i = Math.min(2, Math.floor(idx));
      return lerp(arch[i], arch[i+1], idx - i);
    };
    // wrist interpolation across the bottom (radial → ulnar side)
    const wristAt = (u) => lerp(wristR, wristU, u);
    // Bilinear: u horizontal across palm (0=thumb side, 1=pinky side),
    //           v vertical (0=top, 1=wrist)
    const palmPoint = (u, v) => lerp(archAt(u), wristAt(u), v);

    // HEART LINE — sweeps pinky → index, ~42% down (clearly mid-upper palm)
    const heartCtrl = [
      palmPoint(0.95, 0.42),
      palmPoint(0.70, 0.38),
      palmPoint(0.42, 0.36),
      palmPoint(0.18, 0.40),
    ];

    // HEAD LINE — middle of palm, ~62% down, gently arched
    const headCtrl = [
      palmPoint(0.92, 0.62),
      palmPoint(0.65, 0.65),
      palmPoint(0.38, 0.66),
      palmPoint(0.12, 0.62),
    ];

    // LIFE LINE — radial edge: starts top, bulges INTO palm around thumb mound,
    // arcs back to wrist on thumb side
    const lifeCtrl = [
      palmPoint(0.08, 0.30),
      palmPoint(0.20, 0.55),
      palmPoint(0.18, 0.82),
      palmPoint(0.05, 0.96),
    ];

    // Catmull-Rom spline sampler for organic smooth curves
    const sample = (cp, n = 64) => {
      if (cp.length < 2) return cp.slice();
      const p = [cp[0], ...cp, cp[cp.length - 1]];
      const segs = p.length - 3;
      const out = [];
      for (let s = 0; s <= n; s++) {
        const u = (s / n) * segs;
        const i = Math.min(Math.floor(u), segs - 1);
        const t = u - i;
        const t2 = t*t, t3 = t2*t;
        const a = p[i], b = p[i+1], c = p[i+2], d = p[i+3];
        out.push({
          x: 0.5 * (2*b.x + (-a.x + c.x)*t + (2*a.x - 5*b.x + 4*c.x - d.x)*t2 + (-a.x + 3*b.x - 3*c.x + d.x)*t3),
          y: 0.5 * (2*b.y + (-a.y + c.y)*t + (2*a.y - 5*b.y + 4*c.y - d.y)*t2 + (-a.y + 3*b.y - 3*c.y + d.y)*t3),
        });
      }
      return out;
    };

    return {
      heart: sample(heartCtrl),
      head:  sample(headCtrl),
      life:  sample(lifeCtrl),
      // Palm trapezoid (4 corners) — drawn as faint outline so user can see
      // exactly which area is being treated as "palm".
      _trap: [
        palmPoint(0, 0),     // top-radial (between thumb and index)
        palmPoint(1, 0),     // top-ulnar (pinky MCP)
        palmPoint(1, 1),     // bottom-ulnar (wrist on pinky side)
        palmPoint(0, 1),     // bottom-radial (wrist on thumb side)
      ],
    };
  },

  // ─── Compute measurements from 21 landmarks ──────────────────────────────
  computeMeasurements(pts, results) {
    const dist = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
    // Palm width: 5 (index MCP) → 17 (pinky MCP)
    const palmWidth = dist(pts[5], pts[17]);
    // Palm height: 0 (wrist) → midpoint(5,9,13,17)
    const midTop = {
      x: (pts[5].x + pts[9].x + pts[13].x + pts[17].x) / 4,
      y: (pts[5].y + pts[9].y + pts[13].y + pts[17].y) / 4,
    };
    const palmHeight = dist(pts[0], midTop);

    // Sum segment lengths along the actual control-point polylines.
    const lines = this.computePalmLines(pts);
    const polyLen = (poly) => {
      let s = 0;
      for (let i = 1; i < poly.length; i++) s += dist(poly[i-1], poly[i]);
      return s;
    };
    const heartLineNorm = polyLen(lines.heart);
    const headLineNorm  = polyLen(lines.head);
    const lifeLineNorm  = polyLen(lines.life) * 1.08;  // arc correction (curves longer than chords)

    // Thumb angle: vector(1→4) vs vector(5→0)
    const v1 = { x: pts[4].x - pts[1].x, y: pts[4].y - pts[1].y };
    const v2 = { x: pts[0].x - pts[5].x, y: pts[0].y - pts[5].y };
    const cos = (v1.x*v2.x + v1.y*v2.y) / (Math.hypot(v1.x,v1.y) * Math.hypot(v2.x,v2.y) || 1);
    const thumbAngle = Math.acos(Math.max(-1, Math.min(1, cos))) * 180 / Math.PI;

    // Convert normalized → "cm" using palm width as scale (typical adult palm ~9 cm wide)
    const cmPerUnit = 9 / palmWidth;

    const handedness = results.multiHandedness?.[0]?.label;
    let handLabel = 'necunoscută';
    if (handedness === 'Right') handLabel = this.isFrontCamera ? 'stângă' : 'dreaptă';
    else if (handedness === 'Left') handLabel = this.isFrontCamera ? 'dreaptă' : 'stângă';

    return {
      heartLineLength: +(heartLineNorm * cmPerUnit).toFixed(2),
      headLineLength:  +(headLineNorm  * cmPerUnit).toFixed(2),
      lifeLineLength:  +(lifeLineNorm  * cmPerUnit).toFixed(2),
      palmRatio:       +(palmWidth / palmHeight).toFixed(2),
      thumbAngle:      +(thumbAngle).toFixed(1),
      handedness:      handLabel,
      _palmWidth: palmWidth,
      _palmHeight: palmHeight,
      _pts: pts,
      _lines: lines,
    };
  },

  // ─── Capture current frame as base64 jpeg ────────────────────────────────
  captureFrame() {
    const v = this.videoEl;
    const tmp = document.createElement('canvas');
    tmp.width = 640; tmp.height = Math.round(640 * v.videoHeight / v.videoWidth);
    const tctx = tmp.getContext('2d');
    // Mirror only when front camera (so it matches what user sees on screen)
    if (this.isFrontCamera) {
      tctx.translate(tmp.width, 0); tctx.scale(-1, 1);
    }
    tctx.drawImage(v, 0, 0, tmp.width, tmp.height);
    return tmp.toDataURL('image/jpeg', 0.78);
  },

  // ─── 4-phase scan timeline ───────────────────────────────────────────────
  async beginScanSequence(pts, results) {
    if (this.scanning) return;
    this.scanning = true;
    setStatus('Scanare…', 'scan');

    this.measurements = this.computeMeasurements(pts, results);
    this.capturedFrame = this.captureFrame();

    // Kick off API call in parallel — don't block visual sequence
    const apiPromise = fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        measurements: { ...this.measurements, _pts: undefined, _palmWidth: undefined, _palmHeight: undefined },
        frame: this.capturedFrame,
      }),
    }).then(r => r.json()).catch(e => ({ error: e.message }));

    // Phase 1 — horizontal scan line (2s)
    Audio.sweep(2000);
    await this.phase1_scanLine(2000);

    // Phase 2 — line draws (3s)
    await this.phase2_palmLines(3000);

    // Phase 3 — particles + counters (2s)
    await this.phase3_particles(2000);

    // Phase 4 — flash + fade (1s)
    await this.phase4_flash();

    // Stop camera + audio
    this.stop();

    // Show result, await API
    goTo('result');
    document.getElementById('result-loading').style.display = 'flex';
    document.getElementById('result-content').style.display = 'none';
    const reading = await apiPromise;
    if (reading?.error) {
      document.getElementById('result-loading').innerHTML = '<span style="color:#ffb0b0">Nu am putut genera citirea: ' + reading.error + '</span>';
      return;
    }
    renderReading(reading, this.measurements);
  },

  // Helper: draw landmark connections (used as base layer during scan phases)
  drawLandmarkBase(ctx, pts, W, H, alpha = 0.45) {
    const conn = [
      [0,1],[1,2],[2,3],[3,4],[0,5],[5,6],[6,7],[7,8],
      [5,9],[9,10],[10,11],[11,12],[9,13],[13,14],[14,15],[15,16],
      [13,17],[17,18],[18,19],[19,20],[0,17],
    ];
    ctx.strokeStyle = `rgba(111,233,255,${alpha*0.6})`;
    ctx.lineWidth = 1.2;
    ctx.beginPath();
    for (const [a,b] of conn) {
      ctx.moveTo(pts[a].x*W, pts[a].y*H); ctx.lineTo(pts[b].x*W, pts[b].y*H);
    }
    ctx.stroke();
    for (const p of pts) {
      ctx.fillStyle = `rgba(233,196,126,${alpha})`;
      ctx.beginPath(); ctx.arc(p.x*W, p.y*H, 2.5, 0, Math.PI*2); ctx.fill();
    }
  },

  phase1_scanLine(dur) {
    return new Promise(resolve => {
      const ctx = this.overlayEl.getContext('2d');
      const W = this.overlayEl.width / (devicePixelRatio||1);
      const H = this.overlayEl.height / (devicePixelRatio||1);
      const pts = this.measurements._pts;
      const t0 = performance.now();
      const tick = () => {
        const t = (performance.now() - t0) / dur;
        if (t >= 1) {
          ctx.clearRect(0, 0, W, H);
          this.drawLandmarkBase(ctx, pts, W, H, .55);
          resolve(); return;
        }
        ctx.clearRect(0, 0, W, H);
        this.drawLandmarkBase(ctx, pts, W, H, .55);
        const y = t * H;
        // trail
        for (let i = 0; i < 8; i++) {
          const yy = y - i*4;
          if (yy < 0) continue;
          ctx.fillStyle = `rgba(111,233,255,${(1-i/8) * .12})`;
          ctx.fillRect(0, yy-1, W, 2);
        }
        // main beam
        const grad = ctx.createLinearGradient(0, y-12, 0, y+12);
        grad.addColorStop(0, 'rgba(111,233,255,0)');
        grad.addColorStop(.5, 'rgba(111,233,255,1)');
        grad.addColorStop(1, 'rgba(111,233,255,0)');
        ctx.fillStyle = grad; ctx.fillRect(0, y-12, W, 24);
        ctx.fillStyle = '#fff'; ctx.fillRect(0, y-1, W, 2);
        // glow blooms at landmarks crossing the beam
        for (const p of pts) {
          if (Math.abs(p.y * H - y) < 10) {
            ctx.fillStyle = 'rgba(111,233,255,.9)';
            ctx.beginPath(); ctx.arc(p.x*W, p.y*H, 14, 0, Math.PI*2); ctx.fill();
            ctx.fillStyle = '#fff';
            ctx.beginPath(); ctx.arc(p.x*W, p.y*H, 4, 0, Math.PI*2); ctx.fill();
          }
        }
        requestAnimationFrame(tick);
      };
      tick();
    });
  },

  phase2_palmLines(dur) {
    return new Promise(resolve => {
      const ctx = this.overlayEl.getContext('2d');
      const W = this.overlayEl.width / (devicePixelRatio||1);
      const H = this.overlayEl.height / (devicePixelRatio||1);
      const pts = this.measurements._pts;
      const m = this.measurements;

      // Use the pre-computed control points (correctly positioned inside the palm)
      const toPx = poly => poly.map(p => ({ x: p.x * W, y: p.y * H }));
      const heart = toPx(m._lines.heart);
      const head  = toPx(m._lines.head);
      const life  = toPx(m._lines.life);

      const lines = [
        { name: 'Linia inimii', label: 'heart', pts: heart, color: '#ff7be0', value: m.heartLineLength, start: 0,    end: 1100, ping: 720 },
        { name: 'Linia minții', label: 'head',  pts: head,  color: '#6fe9ff', value: m.headLineLength,  start: 950,  end: 2050, ping: 880 },
        { name: 'Linia vieții', label: 'life',  pts: life,  color: '#e9c47e', value: m.lifeLineLength,  start: 1900, end: 3000, ping: 1040 },
      ];
      const t0 = performance.now();
      const labels = [];
      let pingedAt = -1;

      const drawSpline = (ctx, pts, t) => {
        if (pts.length < 2) return;
        ctx.beginPath();
        ctx.moveTo(pts[0].x, pts[0].y);
        // Catmull-Rom-ish: just connect with quadratic via midpoints
        for (let i = 1; i < pts.length - 1; i++) {
          const mid = { x: (pts[i].x + pts[i+1].x)/2, y: (pts[i].y + pts[i+1].y)/2 };
          ctx.quadraticCurveTo(pts[i].x, pts[i].y, mid.x, mid.y);
        }
        ctx.lineTo(pts[pts.length-1].x, pts[pts.length-1].y);
        // Trick to clip to t: re-render with dashes
      };

      const tick = () => {
        const elapsed = performance.now() - t0;
        ctx.clearRect(0, 0, W, H);
        this.drawLandmarkBase(ctx, pts, W, H, .35);

        // Faint palm trapezoid outline — shows the user the detected palm region
        const trap = m._lines._trap;
        ctx.save();
        ctx.strokeStyle = 'rgba(212,168,87,.28)';
        ctx.fillStyle   = 'rgba(212,168,87,.05)';
        ctx.lineWidth = 1; ctx.setLineDash([4, 6]);
        ctx.beginPath();
        ctx.moveTo(trap[0].x*W, trap[0].y*H);
        for (let k = 1; k < trap.length; k++) ctx.lineTo(trap[k].x*W, trap[k].y*H);
        ctx.closePath();
        ctx.fill(); ctx.stroke();
        ctx.setLineDash([]);
        ctx.restore();

        for (let i = 0; i < lines.length; i++) {
          const ln = lines[i];
          if (elapsed < ln.start) continue;
          const lt = Math.min(1, (elapsed - ln.start) / (ln.end - ln.start));

          // Compute total length
          let segLens = []; let total = 0;
          for (let j = 1; j < ln.pts.length; j++) {
            const d = Math.hypot(ln.pts[j].x - ln.pts[j-1].x, ln.pts[j].y - ln.pts[j-1].y);
            segLens.push(d); total += d;
          }
          const drawLen = total * lt;

          // Glow underlay
          ctx.save();
          ctx.shadowColor = ln.color; ctx.shadowBlur = 18;
          ctx.strokeStyle = ln.color; ctx.lineWidth = 3.5; ctx.lineCap = 'round'; ctx.lineJoin = 'round';

          let acc = 0;
          ctx.beginPath();
          ctx.moveTo(ln.pts[0].x, ln.pts[0].y);
          for (let j = 1; j < ln.pts.length; j++) {
            const sl = segLens[j-1];
            if (acc + sl <= drawLen) {
              ctx.lineTo(ln.pts[j].x, ln.pts[j].y);
              acc += sl;
            } else {
              const remaining = drawLen - acc;
              const r = remaining / sl;
              const x = ln.pts[j-1].x + (ln.pts[j].x - ln.pts[j-1].x) * r;
              const y = ln.pts[j-1].y + (ln.pts[j].y - ln.pts[j-1].y) * r;
              ctx.lineTo(x, y);
              break;
            }
          }
          ctx.stroke();
          ctx.restore();

          // Sparkle at line head
          const head = (() => {
            let acc2 = 0;
            for (let j = 1; j < ln.pts.length; j++) {
              const sl = segLens[j-1];
              if (acc2 + sl >= drawLen) {
                const r = (drawLen - acc2) / sl;
                return { x: ln.pts[j-1].x + (ln.pts[j].x - ln.pts[j-1].x) * r,
                         y: ln.pts[j-1].y + (ln.pts[j].y - ln.pts[j-1].y) * r };
              }
              acc2 += sl;
            }
            return ln.pts[ln.pts.length-1];
          })();
          if (lt < 1) {
            ctx.fillStyle = '#fff';
            ctx.beginPath(); ctx.arc(head.x, head.y, 3.5, 0, Math.PI*2); ctx.fill();
            ctx.fillStyle = ln.color + 'aa';
            ctx.beginPath(); ctx.arc(head.x, head.y, 10, 0, Math.PI*2); ctx.fill();
          }

          // Audio ping at line start
          if (elapsed >= ln.start && pingedAt < i) {
            Audio.ping(ln.ping, 360);
            pingedAt = i;
          }

          // Floating label that follows progress
          if (lt > 0.05) {
            const lblX = Math.min(W - 16, head.x + 14);
            const lblY = Math.max(20, head.y - 10);
            ctx.font = '500 11px Cinzel, serif';
            ctx.textAlign = 'left';
            const txt1 = ln.name.toUpperCase();
            const txt2 = ln.value.toFixed(1) + ' cm';
            const padX = 8, padY = 6;
            const w1 = ctx.measureText(txt1).width;
            const w2 = ctx.measureText(txt2).width;
            const bw = Math.max(w1, w2) + padX*2;
            const bh = 30;
            // line from head to label
            ctx.strokeStyle = ln.color + '88'; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(head.x, head.y); ctx.lineTo(lblX, lblY + bh/2); ctx.stroke();
            // box
            ctx.fillStyle = 'rgba(10,4,32,.85)';
            ctx.strokeStyle = ln.color;
            ctx.lineWidth = 1;
            roundRect(ctx, lblX, lblY, bw, bh, 6);
            ctx.fill(); ctx.stroke();
            // text
            ctx.fillStyle = ln.color; ctx.fillText(txt1, lblX + padX, lblY + 12);
            ctx.fillStyle = '#fff';   ctx.fillText(txt2, lblX + padX, lblY + 25);
          }
        }
        if (elapsed < dur) requestAnimationFrame(tick); else resolve();
      };
      tick();
    });
  },

  phase3_particles(dur) {
    return new Promise(resolve => {
      const ctx = this.overlayEl.getContext('2d');
      const W = this.overlayEl.width / (devicePixelRatio||1);
      const H = this.overlayEl.height / (devicePixelRatio||1);
      const pts = this.measurements._pts;
      const t0 = performance.now();

      // Counters (visible top-left)
      const labels = ['VITALITATE', 'CREATIVITATE', 'EMPATIE', 'INTUIȚIE', 'REZISTENȚĂ'];
      const targets = labels.map(() => 70 + Math.floor(Math.random()*26));

      // Particles: orbit each landmark
      const particles = [];
      for (const p of pts) {
        for (let i = 0; i < 3; i++) {
          particles.push({
            cx: p.x*W, cy: p.y*H,
            r: 8 + Math.random()*22,
            angle: Math.random() * Math.PI * 2,
            speed: (0.5 + Math.random()*1.5) * (Math.random() < .5 ? -1 : 1),
            size: 1 + Math.random()*2,
            hue: Math.random() < .33 ? '#6fe9ff' : (Math.random() < .5 ? '#ff7be0' : '#e9c47e'),
          });
        }
      }

      Audio.ping(440, 600);

      const tick = () => {
        const elapsed = performance.now() - t0;
        const t = Math.min(1, elapsed / dur);
        ctx.clearRect(0, 0, W, H);
        this.drawLandmarkBase(ctx, pts, W, H, .25);

        // Draw connecting lines (faint constellation)
        ctx.strokeStyle = 'rgba(212,168,87,.15)'; ctx.lineWidth = 1;
        ctx.beginPath();
        for (let i = 0; i < pts.length; i++) {
          for (let j = i+1; j < pts.length; j++) {
            const dx = pts[i].x - pts[j].x, dy = pts[i].y - pts[j].y;
            if (dx*dx + dy*dy < 0.04) {
              ctx.moveTo(pts[i].x*W, pts[i].y*H);
              ctx.lineTo(pts[j].x*W, pts[j].y*H);
            }
          }
        }
        ctx.stroke();

        // Particles
        for (const pp of particles) {
          pp.angle += 0.03 * pp.speed;
          const x = pp.cx + Math.cos(pp.angle) * pp.r;
          const y = pp.cy + Math.sin(pp.angle) * pp.r;
          ctx.fillStyle = pp.hue;
          ctx.shadowColor = pp.hue; ctx.shadowBlur = 8;
          ctx.beginPath(); ctx.arc(x, y, pp.size, 0, Math.PI*2); ctx.fill();
          ctx.shadowBlur = 0;
        }

        // Counters panel (top-left of canvas)
        ctx.font = '500 11px Cinzel, serif';
        ctx.textAlign = 'left';
        const panelX = 20, panelY = 60;
        ctx.fillStyle = 'rgba(10,4,32,.7)';
        roundRect(ctx, panelX-8, panelY-18, 165, labels.length*22 + 12, 8);
        ctx.fill();
        ctx.strokeStyle = 'rgba(212,168,87,.4)';
        roundRect(ctx, panelX-8, panelY-18, 165, labels.length*22 + 12, 8);
        ctx.stroke();
        for (let i = 0; i < labels.length; i++) {
          const v = Math.floor(targets[i] * Math.min(1, t * 1.4 - i*0.15));
          const yy = panelY + i*22;
          ctx.fillStyle = '#f5e6c8'; ctx.fillText(labels[i], panelX, yy);
          ctx.fillStyle = '#e9c47e'; ctx.fillText(Math.max(0, v) + '%', panelX + 110, yy);
          // progress bar
          ctx.fillStyle = 'rgba(255,255,255,.06)';
          ctx.fillRect(panelX, yy + 4, 100, 2);
          ctx.fillStyle = '#e9c47e';
          ctx.fillRect(panelX, yy + 4, Math.max(0, v), 2);
        }

        if (elapsed < dur) requestAnimationFrame(tick); else resolve();
      };
      tick();
    });
  },

  phase4_flash() {
    return new Promise(resolve => {
      const flash = document.getElementById('flash');
      Audio.ping(1320, 800);
      flash.style.transition = 'opacity .15s';
      flash.style.opacity = '1';
      setTimeout(() => {
        flash.style.transition = 'opacity .6s';
        flash.style.opacity = '0';
        setTimeout(resolve, 600);
      }, 180);
    });
  },

  stop() {
    if (this.rafId) cancelAnimationFrame(this.rafId);
    if (this.cameraTrack) this.cameraTrack.stop();
    if (this.videoEl?.srcObject) {
      this.videoEl.srcObject.getTracks().forEach(t => t.stop());
      this.videoEl.srcObject = null;
    }
    Audio.drone(false);
    this.scanning = false;
  },
};

function roundRect(ctx, x, y, w, h, r) {
  ctx.beginPath();
  ctx.moveTo(x+r, y);
  ctx.arcTo(x+w, y,   x+w, y+h, r);
  ctx.arcTo(x+w, y+h, x,   y+h, r);
  ctx.arcTo(x,   y+h, x,   y,   r);
  ctx.arcTo(x,   y,   x+w, y,   r);
  ctx.closePath();
}

function setStatus(text, klass = '') {
  const s = document.getElementById('status');
  s.className = 'status-pill' + (klass ? ' ' + klass : '');
  document.getElementById('status-text').textContent = text;
}
function showError(msg) {
  const e = document.getElementById('scan-error');
  e.style.display = 'block'; e.textContent = msg;
}

// ─── Flow control ──────────────────────────────────────────────────────────
function startFlow() {
  goTo('scan');
  Scan.start();
}
function cancelScan() {
  Scan.stop();
  goTo('welcome');
}
function resetFlow() {
  document.getElementById('result-content').style.display = 'none';
  document.getElementById('result-loading').style.display = 'flex';
  document.getElementById('stats').innerHTML = '';
  document.getElementById('sections').innerHTML = '';
  document.getElementById('tagline').textContent = '—';
  goTo('welcome');
}

// ─── Result rendering ──────────────────────────────────────────────────────
function renderReading(data, m) {
  document.getElementById('result-loading').style.display = 'none';
  document.getElementById('result-content').style.display = 'block';

  if (data.tagline) document.getElementById('tagline').textContent = data.tagline;
  else document.getElementById('tagline').textContent =
    `Linia inimii ${m.heartLineLength.toFixed(1)} cm · linia minții ${m.headLineLength.toFixed(1)} cm · linia vieții ${m.lifeLineLength.toFixed(1)} cm`;

  // Stats
  const statsEl = document.getElementById('stats');
  statsEl.innerHTML = '';
  for (const [k, v] of Object.entries(data.scores || {})) {
    const el = document.createElement('div');
    el.className = 'stat';
    el.innerHTML = `
      <div class="lbl">${escapeHtml(k)}</div>
      <div class="val">${v|0}</div>
      <div class="bar"><span></span></div>`;
    statsEl.appendChild(el);
    requestAnimationFrame(() => {
      el.querySelector('.bar > span').style.width = (v|0) + '%';
    });
  }

  // Sections
  const secEl = document.getElementById('sections');
  secEl.innerHTML = '';
  (data.sections || []).forEach((s, i) => {
    const div = document.createElement('div');
    div.className = 'reading-section';
    div.innerHTML = `<h2><span class="num">${i+1}</span>${escapeHtml(s.title)}</h2><p>${escapeHtml(s.body)}</p>`;
    secEl.appendChild(div);
  });

  if (data.fallback_reason) {
    const note = document.createElement('div');
    note.className = 'err';
    note.style.borderColor = 'rgba(233,196,126,.3)';
    note.style.background = 'rgba(233,196,126,.06)';
    note.style.color = 'var(--dim)';
    note.textContent = 'Notă: ' + data.fallback_reason + ' — afișăm o citire de rezervă.';
    secEl.prepend(note);
  }
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}

// ─── Save as PDF (multi-page, A4) ──────────────────────────────────────────
async function saveAsPDF() {
  const btn = document.getElementById('save-btn');
  if (!window.html2canvas || !window.jspdf) { alert('Bibliotecă neîncărcată.'); return; }
  const orig = btn.textContent; btn.textContent = 'Se generează PDF…'; btn.disabled = true;
  try {
    const node = document.getElementById('result-wrap');
    // Render at high DPI for crisp text in PDF
    const canvas = await html2canvas(node, {
      backgroundColor: '#0a0420',
      scale: 2,
      useCORS: true,
      logging: false,
      windowWidth: node.scrollWidth,
      windowHeight: node.scrollHeight,
    });

    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4', compress: true });
    const pageW = pdf.internal.pageSize.getWidth();
    const pageH = pdf.internal.pageSize.getHeight();
    const margin = 8;
    const printableW = pageW - margin * 2;
    const printableH = pageH - margin * 2;

    const ratio = printableW / canvas.width;          // canvas-px → mm
    const totalImgH = canvas.height * ratio;          // total image height in mm

    if (totalImgH <= printableH) {
      // Fits on one page
      pdf.addImage(canvas.toDataURL('image/jpeg', 0.92), 'JPEG',
        margin, margin, printableW, totalImgH, undefined, 'FAST');
    } else {
      // Slice the canvas vertically and place each slice on its own page.
      const sliceCanvasH = Math.floor(printableH / ratio);  // slice height in canvas px
      let yOffset = 0;
      let firstPage = true;
      while (yOffset < canvas.height) {
        const h = Math.min(sliceCanvasH, canvas.height - yOffset);
        const slice = document.createElement('canvas');
        slice.width = canvas.width;
        slice.height = h;
        const sctx = slice.getContext('2d');
        // Paint deep cosmic background so margins around image read consistent
        sctx.fillStyle = '#0a0420';
        sctx.fillRect(0, 0, slice.width, slice.height);
        sctx.drawImage(canvas, 0, -yOffset);
        if (!firstPage) pdf.addPage();
        firstPage = false;
        pdf.addImage(slice.toDataURL('image/jpeg', 0.9), 'JPEG',
          margin, margin, printableW, h * ratio, undefined, 'FAST');
        yOffset += h;
      }
    }

    pdf.setProperties({
      title: 'Palma — Citire generată',
      subject: 'Citit în palmă cu AI',
      creator: 'sambla.ro/palma',
    });
    pdf.save('palma-citire.pdf');
  } catch (e) {
    alert('Eroare la generarea PDF: ' + e.message);
  } finally {
    btn.textContent = orig; btn.disabled = false;
  }
}

// Stop camera if user navigates away
addEventListener('beforeunload', () => Scan.stop());
addEventListener('pagehide',     () => Scan.stop());
</script>

</body>
</html>
