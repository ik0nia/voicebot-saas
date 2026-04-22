<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<title>Swipe — Sambla Social</title>
<link rel="manifest" href="/swipe-manifest.json">
<meta name="theme-color" content="#DC2626">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Swipe">
<link rel="apple-touch-icon" href="/images/logo-icon.png">
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
  :root {
    --cream: #F5F1E8;
    --paper: #FAF7EF;
    --sand: #EFE5D0;
    --ink: #1C1917;
    --muted: #78716C;
    --line: #E7E0CE;
    --coral: #DC2626;
    --coral-dark: #991B1B;
    --emerald: #10B981;
  }
  * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
  html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; overscroll-behavior: none; }
  body {
    background: var(--cream);
    color: var(--ink);
    font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;
    -webkit-font-smoothing: antialiased;
    user-select: none;
    -webkit-user-select: none;
  }
  .safe { padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left); }
  header {
    position: fixed;
    top: env(safe-area-inset-top);
    left: 0; right: 0;
    padding: 14px 20px 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 5;
    background: linear-gradient(to bottom, var(--cream) 70%, transparent);
  }
  .brand {
    display: flex; align-items: center; gap: 8px;
    font-weight: 600; font-size: 0.95rem; letter-spacing: -0.01em;
  }
  .brand .logo {
    width: 28px; height: 28px; border-radius: 8px;
    background: url('/images/logo-icon.png') center/cover, var(--coral);
  }
  .counter {
    font-family: 'JetBrains Mono', ui-monospace, monospace;
    font-size: 0.72rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    background: var(--paper);
    border: 1px solid var(--line);
    padding: 5px 10px;
    border-radius: 999px;
  }

  .stage {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 16px 150px;
  }

  .card {
    position: absolute;
    width: min(420px, 94vw);
    max-height: calc(100vh - 210px);
    background: var(--paper);
    border-radius: 28px;
    box-shadow: 0 30px 60px -20px rgba(0,0,0,0.18);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transform-origin: center bottom;
    will-change: transform;
    touch-action: pan-y;
  }
  .card:nth-child(1) { z-index: 3; }
  .card:nth-child(2) { z-index: 2; transform: scale(0.96) translateY(10px); }
  .card:nth-child(3) { z-index: 1; transform: scale(0.92) translateY(20px); opacity: 0.7; }

  .card-image {
    width: 100%;
    aspect-ratio: 4/5;
    background: var(--sand);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
  }
  .card-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .card-image .nope { color: var(--muted); font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; padding: 40px; text-align: center; }

  .card-body {
    padding: 16px 18px 18px;
    flex: 1 1 auto;
    overflow-y: auto;
  }
  .card-meta {
    display: flex;
    justify-content: space-between;
    font-family: 'JetBrains Mono', ui-monospace, monospace;
    font-size: 0.7rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 10px;
  }
  .platform-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.62rem;
    letter-spacing: 0.08em;
  }
  .platform-facebook { background: #1877F2; color: #fff; }
  .platform-instagram { background: linear-gradient(45deg, #F58529, #DD2A7B, #8134AF, #515BD4); color: #fff; }
  .card-text {
    font-size: 0.92rem;
    line-height: 1.5;
    white-space: pre-wrap;
    color: var(--ink);
  }
  .card-hashtags {
    color: #00376B;
    margin-top: 10px;
    font-size: 0.82rem;
  }
  .siblings-note {
    margin-top: 12px;
    padding: 8px 10px;
    background: var(--cream);
    border: 1px dashed var(--line);
    border-radius: 10px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.7rem;
    color: var(--muted);
  }

  /* Swipe overlays */
  .swipe-badge {
    position: absolute;
    top: 22px;
    padding: 8px 18px;
    border-radius: 999px;
    font-weight: 800;
    font-size: 1.1rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.08s ease;
    border: 3px solid;
  }
  .badge-approve {
    right: 20px;
    color: var(--emerald);
    border-color: var(--emerald);
    transform: rotate(8deg);
  }
  .badge-reject {
    left: 20px;
    color: var(--coral);
    border-color: var(--coral);
    transform: rotate(-8deg);
  }

  /* Bottom action bar */
  .actions {
    position: fixed;
    bottom: calc(env(safe-area-inset-bottom) + 16px);
    left: 0; right: 0;
    display: flex;
    justify-content: center;
    gap: 20px;
    z-index: 10;
  }
  .btn {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: var(--paper);
    border: 1px solid var(--line);
    box-shadow: 0 10px 22px -6px rgba(0,0,0,0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.55rem;
    cursor: pointer;
    transition: transform 0.1s ease, background 0.1s;
  }
  .btn:active { transform: scale(0.9); }
  .btn.reject { color: var(--coral); }
  .btn.reject:hover { background: #FEE2E2; }
  .btn.edit { color: var(--ink); }
  .btn.edit:hover { background: var(--sand); }
  .btn.approve { color: var(--emerald); }
  .btn.approve:hover { background: #D1FAE5; }

  .empty {
    position: fixed; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 40px;
    text-align: center;
  }
  .empty .mark {
    width: 84px; height: 84px; border-radius: 20px;
    background: var(--coral);
    color: #fff; font-weight: 600; font-size: 2rem;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 18px;
  }
  .empty h2 { font-weight: 500; font-size: 1.35rem; margin: 0 0 8px; letter-spacing: -0.02em; }
  .empty p { color: var(--muted); font-size: 0.95rem; line-height: 1.5; margin: 0 0 20px; max-width: 320px; }
  .empty button {
    background: var(--coral); color: #fff;
    border: 0; padding: 12px 26px;
    border-radius: 999px; font-weight: 600; font-size: 0.95rem;
    cursor: pointer;
  }

  .toast {
    position: fixed;
    bottom: calc(env(safe-area-inset-bottom) + 110px);
    left: 50%; transform: translateX(-50%) translateY(30px);
    background: var(--ink); color: #fff;
    padding: 10px 18px; border-radius: 999px;
    font-size: 0.85rem; font-weight: 500;
    opacity: 0; pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
    z-index: 20;
  }
  .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
</style>
</head>
<body class="safe">

<header>
  <div class="brand">
    <div class="logo"></div>
    <span>Sambla Swipe</span>
  </div>
  <div class="counter" id="counter">…</div>
</header>

<div class="stage" id="stage"></div>

<div class="empty" id="empty" style="display:none">
  <div class="mark">✓</div>
  <h2>Totul aprobat</h2>
  <p>Nu e nimic de review acum. Cron-ul completează coada la fiecare 15 min.</p>
  <button onclick="refreshQueue()">Reîncarcă</button>
</div>

<div class="actions" id="actions" style="display:none">
  <button class="btn reject" id="btn-reject" aria-label="Respinge">✗</button>
  <button class="btn edit" id="btn-edit" aria-label="Editează">✎</button>
  <button class="btn approve" id="btn-approve" aria-label="Aprobă">✓</button>
</div>

<div class="toast" id="toast"></div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const stage = document.getElementById('stage');
const emptyEl = document.getElementById('empty');
const actions = document.getElementById('actions');
const counter = document.getElementById('counter');
const toast = document.getElementById('toast');

let queue = [];
let inflight = false;

function showToast(msg, ms = 1500) {
  toast.textContent = msg;
  toast.classList.add('show');
  clearTimeout(toast._t);
  toast._t = setTimeout(() => toast.classList.remove('show'), ms);
}

function buildCard(post) {
  const card = document.createElement('article');
  card.className = 'card';
  card.dataset.id = post.id;

  const siblings = post.siblings_count > 0
    ? `<div class="siblings-note">Grup cu ${post.siblings_count} altă/alte postare — aprobarea/respingerea se aplică la toate</div>`
    : '';

  const hashtags = (post.hashtags || []).length
    ? `<div class="card-hashtags">${post.hashtags.map(h => '#' + String(h).replace(/^#/, '')).join(' ')}</div>`
    : '';

  const imgHtml = post.image_url
    ? `<img src="${post.image_url}" alt="post ${post.id}">`
    : `<div class="nope">Fără imagine încă</div>`;

  card.innerHTML = `
    <div class="swipe-badge badge-reject">Respins</div>
    <div class="swipe-badge badge-approve">Aprobat</div>
    <div class="card-image">${imgHtml}</div>
    <div class="card-body">
      <div class="card-meta">
        <div>
          <span class="platform-pill platform-${post.platform}">${post.platform}</span>
          &middot; ${post.post_type} &middot; #${post.id}
        </div>
      </div>
      <div class="card-text">${escapeHtml(post.content || '')}</div>
      ${hashtags}
      ${siblings}
    </div>
  `;
  attachSwipe(card);
  return card;
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function attachSwipe(card) {
  let startX = 0, startY = 0, dx = 0, dy = 0, dragging = false;
  const badgeApprove = card.querySelector('.badge-approve');
  const badgeReject = card.querySelector('.badge-reject');

  const onDown = (e) => {
    if (card !== stage.firstElementChild) return;
    dragging = true;
    const p = e.touches ? e.touches[0] : e;
    startX = p.clientX; startY = p.clientY; dx = 0; dy = 0;
    card.style.transition = 'none';
  };
  const onMove = (e) => {
    if (!dragging) return;
    const p = e.touches ? e.touches[0] : e;
    dx = p.clientX - startX;
    dy = p.clientY - startY;
    const rot = dx * 0.06;
    card.style.transform = `translate(${dx}px, ${dy * 0.3}px) rotate(${rot}deg)`;
    const op = Math.min(Math.abs(dx) / 120, 1);
    if (dx > 0) { badgeApprove.style.opacity = op; badgeReject.style.opacity = 0; }
    else if (dx < 0) { badgeReject.style.opacity = op; badgeApprove.style.opacity = 0; }
  };
  const onUp = () => {
    if (!dragging) return;
    dragging = false;
    card.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
    const threshold = 110;
    if (dx > threshold) { commitSwipe(card, 'approve'); }
    else if (dx < -threshold) { commitSwipe(card, 'reject'); }
    else {
      card.style.transform = '';
      badgeApprove.style.opacity = 0;
      badgeReject.style.opacity = 0;
    }
  };

  card.addEventListener('touchstart', onDown, { passive: true });
  card.addEventListener('touchmove', onMove, { passive: true });
  card.addEventListener('touchend', onUp);
  card.addEventListener('mousedown', onDown);
  window.addEventListener('mousemove', onMove);
  window.addEventListener('mouseup', onUp);
}

async function commitSwipe(card, action) {
  if (inflight) return;
  const id = card.dataset.id;
  const flyX = action === 'approve' ? window.innerWidth : -window.innerWidth;
  card.style.transform = `translate(${flyX}px, 50px) rotate(${action === 'approve' ? 20 : -20}deg)`;
  card.style.opacity = 0;
  try { if (navigator.vibrate) navigator.vibrate(action === 'approve' ? 20 : 15); } catch {}

  inflight = true;
  try {
    const res = await fetch(`/swipe/${id}/${action}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    });
    const data = await res.json();
    showToast(action === 'approve' ? `Aprobat (${data.scheduled || 1} postări)` : `Respins (${data.deleted || 1} postări)`);
  } catch (e) {
    showToast('Eroare — reîncarcă', 2200);
  } finally {
    inflight = false;
  }

  setTimeout(() => {
    card.remove();
    queue.shift();
    updateCounter();
    if (stage.children.length < 2) refreshQueue(true);
    if (!stage.children.length) emptyEl.style.display = 'flex', actions.style.display = 'none';
  }, 320);
}

function updateCounter() {
  counter.textContent = `${queue.length} de aprobat`;
}

async function refreshQueue(append = false) {
  const res = await fetch('/swipe/queue', { headers: { 'Accept': 'application/json' } });
  const data = await res.json();
  const existing = new Set(queue.map(p => p.id));
  const fresh = data.posts.filter(p => !existing.has(p.id));

  if (append) {
    queue.push(...fresh);
    for (const p of fresh) stage.appendChild(buildCard(p));
  } else {
    stage.innerHTML = '';
    queue = data.posts;
    for (const p of queue.slice(0, 3).reverse()) stage.appendChild(buildCard(p));
    // Reverse so top card is last (rendered on top via z-index order).
  }

  counter.textContent = `${data.total} de aprobat`;
  if (!queue.length) {
    emptyEl.style.display = 'flex';
    actions.style.display = 'none';
  } else {
    emptyEl.style.display = 'none';
    actions.style.display = 'flex';
  }
}

function topCard() { return stage.firstElementChild; }

document.getElementById('btn-reject').addEventListener('click', () => {
  const c = topCard(); if (c) commitSwipe(c, 'reject');
});
document.getElementById('btn-approve').addEventListener('click', () => {
  const c = topCard(); if (c) commitSwipe(c, 'approve');
});
document.getElementById('btn-edit').addEventListener('click', () => {
  const c = topCard(); if (!c) return;
  const id = c.dataset.id;
  const post = queue.find(p => String(p.id) === String(id));
  if (post && post.edit_url) window.location.href = post.edit_url;
});

refreshQueue();
setInterval(() => { if (!document.hidden) refreshQueue(); }, 60000);
</script>
</body>
</html>
