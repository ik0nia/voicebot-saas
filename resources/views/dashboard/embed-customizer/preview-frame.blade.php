<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Preview widget · {{ $bot->name }}</title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; font-family: system-ui, sans-serif; color: #1f2937; background: #fff; }
  .hero { background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 3rem 1.5rem 4rem; }
  .nav { display: flex; align-items: center; justify-content: space-between; max-width: 720px; margin: 0 auto 2rem; }
  .logo { display: inline-flex; align-items: center; gap: .5rem; font-weight: 700; font-size: 1rem; }
  .logo-mark { width: 24px; height: 24px; background: {{ $color }}; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: .7rem; }
  h1 { font-size: 1.6rem; margin: 0 0 .75rem; line-height: 1.2; max-width: 30ch; }
  .lede { font-size: .85rem; color: #6b7280; max-width: 40ch; }
  .container { max-width: 720px; margin: 0 auto; }
  .features { padding: 2rem 1.5rem; background: white; }
  .features-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; max-width: 720px; margin: 0 auto; }
  .feature { padding: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; background: #fafafa; }
  .feature h3 { margin: 0 0 .25rem; font-size: .9rem; }
  .feature p { margin: 0; color: #6b7280; font-size: .75rem; }
</style>
</head>
<body>

<header class="hero">
    <nav class="nav">
        <span class="logo"><span class="logo-mark">EX</span> Site Demo</span>
        <div style="font-size: .75rem; color: #6b7280;">Servicii · Preturi</div>
    </nav>
    <div class="container">
        <h1>Așa va arăta widget-ul tău pe un site real.</h1>
        <p class="lede">Widget-ul agentului „{{ $bot->name }}" e poziționat în <strong>{{ $position }}</strong>. Apasă pe el ca să vezi greeting-ul și culoarea brand.</p>
    </div>
</header>

<section class="features">
    <div class="features-grid">
        <div class="feature"><h3>Răspuns rapid</h3><p>Vizitatorii primesc instant răspuns.</p></div>
        <div class="feature"><h3>Programări automate</h3><p>Agentul preia direct cereri.</p></div>
    </div>
</section>

{{-- Widget-ul cu config-ul nou --}}
<script src="{{ rtrim(config('app.url'), '/') }}/widget/sambla-chat.min.js"
        data-channel-id="{{ $channel->id }}"
        data-color="{{ $color }}"
        data-position="{{ $position }}"
        data-lang="{{ $lang }}"
        data-greeting="{{ $greeting }}"
        async defer></script>
</body>
</html>
