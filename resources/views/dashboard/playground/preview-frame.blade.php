<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Preview · {{ $bot->name }}</title>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #1f2937; background: #f9fafb; }
  .hero { background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 4rem 2rem 6rem; }
  .container { max-width: 960px; margin: 0 auto; }
  .nav { display: flex; align-items: center; justify-content: space-between; max-width: 960px; margin: 0 auto 3rem; padding-top: 1rem; }
  .logo { display: inline-flex; align-items: center; gap: .5rem; font-weight: 700; font-size: 1.1rem; color: #1f2937; }
  .logo-mark { width: 28px; height: 28px; background: {{ $color }}; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: .85rem; }
  .nav-links { display: flex; gap: 1.5rem; font-size: .9rem; color: #4b5563; }
  h1 { font-size: 2.5rem; margin: 0 0 1rem; line-height: 1.15; max-width: 36ch; }
  .lede { font-size: 1.05rem; color: #6b7280; max-width: 50ch; line-height: 1.6; margin: 0 0 1.5rem; }
  .ctas { display: flex; gap: .75rem; flex-wrap: wrap; }
  .btn { padding: .65rem 1.25rem; border-radius: 999px; font-weight: 600; font-size: .9rem; text-decoration: none; display: inline-flex; align-items: center; gap: .35rem; }
  .btn-primary { background: {{ $color }}; color: white; }
  .btn-secondary { background: white; color: #1f2937; border: 1px solid #d1d5db; }

  .features { padding: 4rem 2rem; background: white; }
  .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; max-width: 960px; margin: 0 auto; }
  .feature { padding: 1.5rem; border: 1px solid #e5e7eb; border-radius: 12px; background: #fafafa; }
  .feature h3 { margin: 0 0 .5rem; font-size: 1rem; }
  .feature p { margin: 0; color: #6b7280; font-size: .85rem; line-height: 1.5; }

  footer { padding: 2rem; text-align: center; color: #9ca3af; font-size: .8rem; background: #f9fafb; border-top: 1px solid #e5e7eb; }

  @media (max-width: 640px) {
    .features-grid { grid-template-columns: 1fr; }
    h1 { font-size: 1.8rem; }
  }
</style>
</head>
<body>

<header class="hero">
    <nav class="nav">
        <span class="logo">
            <span class="logo-mark">EX</span> Site Exemplu
        </span>
        <div class="nav-links">
            <span>Servicii</span>
            <span>Preturi</span>
            <span>Contact</span>
        </div>
    </nav>
    <div class="container">
        <h1>Site demo pentru a-ți arăta widget-ul live.</h1>
        <p class="lede">Așa va apărea agentul AI „<strong>{{ $bot->name }}</strong>" pe site-ul real al clientului tău. Apasă pe bula din dreapta-jos.</p>
        <div class="ctas">
            <a href="#" class="btn btn-primary">Solicită demo</a>
            <a href="#" class="btn btn-secondary">Află mai multe</a>
        </div>
    </div>
</header>

<section class="features">
    <div class="features-grid">
        <div class="feature">
            <h3>Răspuns rapid</h3>
            <p>Vizitatorii primesc instant răspunsuri la întrebări, fără să aștepte program.</p>
        </div>
        <div class="feature">
            <h3>Programări automate</h3>
            <p>Agentul preia direct cereri de programare și le trimite în calendar.</p>
        </div>
        <div class="feature">
            <h3>Lead-uri capturate</h3>
            <p>Conversațiile cu intent comercial devin automat lead-uri în pipeline.</p>
        </div>
    </div>
</section>

<footer>
    Site exemplu · widget-ul Sambla este injectat în colțul dreapta-jos
</footer>

{{-- Widget-ul real al bot-ului --}}
<script src="@samblaWidgetUrl"
        data-channel-id="{{ $channel->id }}"
        data-color="{{ $color }}"
        data-lang="ro"
        async defer></script>
</body>
</html>
