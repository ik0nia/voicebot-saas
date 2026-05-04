<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\BillingController;
use App\Http\Controllers\Dashboard\BookingAdminController;
use App\Http\Controllers\Dashboard\BotController;
use App\Http\Controllers\Dashboard\ClonedVoiceController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\CallController;
use App\Http\Controllers\Dashboard\ChannelController;
use App\Http\Controllers\Dashboard\ConversationController;
use App\Http\Controllers\Dashboard\KnowledgeController;
use App\Http\Controllers\Dashboard\PhoneNumberController;
use App\Http\Controllers\Dashboard\SiteController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\Dashboard\TeamController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminSocialController;
use App\Http\Controllers\SwipeController;
use App\Http\Controllers\Webhook\FacebookWebhookController;
use App\Http\Controllers\Webhook\InstagramWebhookController;
use App\Http\Controllers\Webhook\TelnyxWebhookController;
use App\Http\Controllers\Webhook\TwilioWebhookController;
use App\Http\Controllers\Webhook\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Password reset
    Route::get('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showLinkForm'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// GDPR consent log — client widget POSTs here on every consent
// change so we have proof-of-consent per the ePrivacy directive.
// Rate-limited to stop a noisy banner from filling the table.
Route::post('/consent', [ConsentController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('consent.store');

// Public contact form — saves to contact_messages, notifies
// support, fires generate_lead in the analytics stack. Throttled
// to stop form-spam bots from flooding the support inbox.
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

// Email verification
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');
});

// Public landing pages — post-cutover canonical site (formerly /new/*).
// Wrapped in PublicPageCache so anonymous visitors get a 5-minute browser
// cache instead of Laravel's default no-cache. Authenticated users skip
// the cache automatically. Legacy view files (resources/views/home,
// landing/niche, etc.) remain on disk as a rollback parachute.
Route::middleware(\App\Http\Middleware\PublicPageCache::class)->group(function () {
    $c = \App\Http\Controllers\NewSite\NewSiteController::class;
    Route::get('/',                     [$c, 'home'])->name('new.home');
    Route::get('/functionalitati',      [$c, 'functionalitati'])->name('new.functionalitati');
    Route::get('/preturi',              [$c, 'preturi'])->name('new.preturi');
    Route::get('/de-ce-sambla',         [$c, 'deCeSambla'])->name('new.deCeSambla');
    Route::get('/despre',               [$c, 'despre'])->name('new.despre');
    Route::get('/contact',              [$c, 'contact'])->name('new.contact');
    Route::get('/blog',                 [$c, 'blog'])->name('new.blog');
    Route::get('/industrii',            [$c, 'industrii'])->name('new.industrii');
    Route::get('/termeni',              fn () => app($c)->legal('termeni'))->name('new.legal.termeni');
    Route::get('/confidentialitate',    fn () => app($c)->legal('confidentialitate'))->name('new.legal.confidentialitate');
    Route::get('/cookie-uri',           fn () => app($c)->legal('cookie-uri'))->name('new.legal.cookies');
    Route::get('/pentru/{niche:slug}',  [$c, 'niche'])->name('new.niche');
});

// /new/* legacy URLs → 301 redirects to canonical root URLs.
// Kept indefinitely so any inbound link / Google cache transfers
// PageRank to the canonical equivalent. Cheap, no controller churn.
Route::redirect('/new',                         '/',                  301);
Route::redirect('/new/functionalitati',         '/functionalitati',   301);
Route::redirect('/new/preturi',                 '/preturi',           301);
Route::redirect('/new/de-ce-sambla',            '/de-ce-sambla',      301);
Route::redirect('/new/despre',                  '/despre',            301);
Route::redirect('/new/contact',                 '/contact',           301);
Route::redirect('/new/blog',                    '/blog',              301);
Route::redirect('/new/industrii',               '/industrii',         301);
Route::redirect('/new/legal/termeni',           '/termeni',           301);
Route::redirect('/new/legal/confidentialitate', '/confidentialitate', 301);
Route::redirect('/new/legal/cookie-uri',        '/cookie-uri',        301);
Route::get('/new/pentru/{slug}', fn (string $slug) => redirect('/pentru/' . $slug, 301));

// Design previews — variante statice de redesign (noindex în view-uri).
Route::prefix('preview')->group(function () {
    Route::view('/',                    'preview.index')->name('preview.index');
    Route::view('/home/warm',           'preview.home-warm');
    Route::view('/home/stripe',         'preview.home-stripe');
    Route::view('/home/claude',         'preview.home-claude');
    Route::view('/home/bold',           'preview.home-bold');
    Route::view('/home/redesign',       'preview.home-redesign');
    Route::view('/dashboard/linear',    'preview.dashboard-linear');
    Route::view('/dashboard/notion',    'preview.dashboard-notion');
    Route::view('/dashboard/stripe',    'preview.dashboard-stripe');
    Route::view('/niche/stomatologie',  'preview.niche-stomatologie');

    // v2 — redesign integrat (Notion base + Linear density pe operator pages)
    Route::prefix('v2')->group(function () {
        Route::view('/',          'preview.v2.index');
        Route::view('/dashboard', 'preview.v2.dashboard');
        Route::view('/inbox',     'preview.v2.inbox');
    });
});

/*
 * OG image generator — SVG 1200×630 warm cu titlu + subtitle + accent.
 * LinkedIn / Twitter / Slack / Discord acceptă SVG; Facebook/Instagram
 * sunt mai pretențioase dar citesc și SVG dacă e servit cu Content-Type
 * corect. Pentru Twitter summary_large_image, adaugă `format=png` mai
 * târziu când avem un binding imagick disponibil.
 */
Route::get('/new/og.svg', function (\Illuminate\Http\Request $request) {
    $title    = mb_substr((string) $request->query('title', 'Agenți AI pentru afaceri românești'), 0, 80);
    $subtitle = mb_substr((string) $request->query('sub', 'Sambla — chat, voce, 24/7, în limba română'), 0, 120);
    $accent   = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $request->query('accent', '#DC2626'))
        ? $request->query('accent')
        : '#DC2626';
    $eyebrow  = mb_substr((string) $request->query('eyebrow', 'sambla.ro'), 0, 40);

    // Break long title pe 2 rânduri aproximativ la 30 caractere.
    $titleLines = [];
    if (mb_strlen($title) > 32) {
        $words = preg_split('/\s+/', $title);
        $line1 = '';
        foreach ($words as $i => $w) {
            if (mb_strlen($line1 . ' ' . $w) > 32) {
                $titleLines = [trim($line1), trim(implode(' ', array_slice($words, $i)))];
                break;
            }
            $line1 .= ' ' . $w;
        }
        if (empty($titleLines)) $titleLines = [$title];
    } else {
        $titleLines = [$title];
    }

    try {
        $svg = view('new.og-image', [
            'title'      => $titleLines,
            'subtitle'   => $subtitle,
            'eyebrow'    => $eyebrow,
            'accent'     => $accent,
        ])->render();
    } catch (\Throwable $e) {
        // Defensive: never 500 on this endpoint — if the view chokes,
        // serve a minimal valid SVG so social-share crawlers still get
        // a usable OG image. Log the error for diagnosis.
        \Illuminate\Support\Facades\Log::warning('og.svg render failed', [
            'err' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
        ]);
        $safeTitle = htmlspecialchars(implode(' ', $titleLines), ENT_XML1);
        $safeSub   = htmlspecialchars($subtitle, ENT_XML1);
        $svg = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">' .
            '<rect width="1200" height="630" fill="#F5F1E8"/>' .
            '<text x="80" y="320" font-family="serif" font-size="64" font-weight="600" fill="#1C1917">' . $safeTitle . '</text>' .
            '<text x="80" y="380" font-family="sans-serif" font-size="24" fill="#3A3532">' . $safeSub . '</text>' .
            '<text x="80" y="560" font-family="sans-serif" font-size="20" fill="#78716C">sambla.ro</text>' .
            '</svg>';
    }

    return response($svg, 200, [
        'Content-Type'  => 'image/svg+xml; charset=utf-8',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('new.og');

// Dynamic sitemap — includes all public pages + active niche landing pages.
Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => '/',                  'changefreq' => 'weekly',  'priority' => '1.0'],
        ['loc' => '/de-ce-sambla',      'changefreq' => 'weekly',  'priority' => '0.95'],
        ['loc' => '/functionalitati',   'changefreq' => 'weekly',  'priority' => '0.9'],
        ['loc' => '/preturi',           'changefreq' => 'weekly',  'priority' => '0.9'],
        ['loc' => '/industrii',         'changefreq' => 'weekly',  'priority' => '0.8'],
        ['loc' => '/despre',            'changefreq' => 'monthly', 'priority' => '0.7'],
        ['loc' => '/contact',           'changefreq' => 'monthly', 'priority' => '0.7'],
        ['loc' => '/blog',              'changefreq' => 'weekly',  'priority' => '0.6'],
        ['loc' => '/termeni',           'changefreq' => 'yearly',  'priority' => '0.3'],
        ['loc' => '/confidentialitate', 'changefreq' => 'yearly',  'priority' => '0.3'],
        ['loc' => '/cookie-uri',        'changefreq' => 'yearly',  'priority' => '0.3'],
    ];

    $niches = \App\Models\Niche::where('is_active', true)
        ->orderBy('sort_order')
        ->get(['slug', 'updated_at']);

    foreach ($niches as $niche) {
        $urls[] = [
            'loc'        => '/pentru/' . $niche->slug,
            'lastmod'    => $niche->updated_at->toW3cString(),
            'changefreq' => 'weekly',
            'priority'   => '0.8',
        ];
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($urls as $entry) {
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>https://sambla.ro' . $entry['loc'] . '</loc>' . "\n";
        if (isset($entry['lastmod'])) {
            $xml .= '    <lastmod>' . $entry['lastmod'] . '</lastmod>' . "\n";
        }
        $xml .= '    <changefreq>' . $entry['changefreq'] . '</changefreq>' . "\n";
        $xml .= '    <priority>' . $entry['priority'] . '</priority>' . "\n";
        $xml .= '  </url>' . "\n";
    }

    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
});

// Niche lead capture (POST — outside PublicPageCache so CSRF + flash work normally).
// Throttled to stop a bot from flooding niche leads (and the support inbox).
Route::post('/pentru/{niche:slug}/lead', [\App\Http\Controllers\NicheLandingController::class, 'storeLead'])
    ->middleware('throttle:5,1')
    ->name('public.niche.lead');

// Chatbot embed routes are in routes/api.php under /chatbot prefix (no auth/session middleware)

// Public demo & test pages (no auth required)
Route::get('/demo/{slug}', [\App\Http\Controllers\PublicDemoController::class, 'show'])->name('public.demo');
Route::get('/chat-demo/{slug}', [\App\Http\Controllers\PublicDemoController::class, 'chat'])->name('public.chatDemo');
Route::get('/dashboard/agenti/{bot}/test-vocal', [\App\Http\Controllers\PublicDemoController::class, 'testById'])->name('dashboard.bots.testVocal');

// Legacy /dashboard/boti/* paths: permanent redirect to /dashboard/agenti/*
// so bookmarks and links shared before the rename stay usable.
Route::redirect('/dashboard/boti', '/dashboard/agenti', 301);
Route::any('/dashboard/boti/{rest?}', function ($rest = '') {
    $query = request()->getQueryString();
    $target = '/dashboard/agenti' . ($rest !== '' ? '/' . $rest : '') . ($query ? '?' . $query : '');
    return redirect($target, 301);
})->where('rest', '.*');

// Setup wizard (onboarding). The wizard seeds a bot + prompt and sets
// tenant-level onboarding flags. Only admin runs onboarding.
// Bot Workspace — unified single-screen overview per bot.
// Additive; all legacy dashboard routes remain canonical edit
// surfaces. Workspace links out to them via "Deschide" buttons.
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/workspace/{bot}', [\App\Http\Controllers\Dashboard\WorkspaceController::class, 'show'])
        ->name('dashboard.workspace.show');
    Route::get('/dashboard/workspace/{bot}/automations',
        [\App\Http\Controllers\Dashboard\WorkspaceAutomationController::class, 'show'])
        ->name('dashboard.workspace.automations');
    Route::put('/dashboard/workspace/{bot}/automations',
        [\App\Http\Controllers\Dashboard\WorkspaceAutomationController::class, 'update'])
        ->name('dashboard.workspace.automations.update');
});

// Onboarding v2 — runs in parallel with legacy /dashboard/setup.
// Tenants reach it via direct link (admin-gated rollout) until
// the onboarding_v2_enabled platform flag is flipped for everyone.
Route::middleware('auth')->prefix('dashboard/setup-wow')->group(function () {
    Route::get('/', [\App\Http\Controllers\Dashboard\SetupWowController::class, 'start'])->name('dashboard.setup-wow.start');
    Route::get('/{step}', [\App\Http\Controllers\Dashboard\SetupWowController::class, 'step'])->name('dashboard.setup-wow.step');
    Route::post('/niche',   [\App\Http\Controllers\Dashboard\SetupWowController::class, 'saveNiche'])->name('dashboard.setup-wow.saveNiche');
    Route::post('/website', [\App\Http\Controllers\Dashboard\SetupWowController::class, 'saveWebsite'])->name('dashboard.setup-wow.saveWebsite');
    Route::post('/agent',   [\App\Http\Controllers\Dashboard\SetupWowController::class, 'saveAgent'])->name('dashboard.setup-wow.saveAgent');
    Route::post('/publish', [\App\Http\Controllers\Dashboard\SetupWowController::class, 'publish'])->name('dashboard.setup-wow.publish');
});

Route::middleware('auth')->prefix('dashboard/setup')->group(function () {
    Route::get('/', [\App\Http\Controllers\Dashboard\SetupWizardController::class, 'index'])->name('dashboard.setup.index');
    Route::middleware('tenant.role:tenant_admin')->group(function () {
        Route::post('/business-type', [\App\Http\Controllers\Dashboard\SetupWizardController::class, 'storeBusinessType'])->name('dashboard.setup.businessType');
        Route::post('/generate-prompt', [\App\Http\Controllers\Dashboard\SetupWizardController::class, 'generatePrompt'])->name('dashboard.setup.generatePrompt');
        Route::post('/complete', [\App\Http\Controllers\Dashboard\SetupWizardController::class, 'complete'])->name('dashboard.setup.complete');
    });
});

// Dashboard home
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');
Route::post('/dashboard/toggle-admin-view', [DashboardController::class, 'toggleAdminView'])->middleware(['auth'])->name('dashboard.toggleAdminView');

// Iteration B: tenant-scoped AI spend summary for today — feeds the
// progress bar on the bot edit page and any future spend widgets.
Route::get('/dashboard/ai-usage-today', [DashboardController::class, 'aiUsageToday'])
    ->middleware(['auth'])
    ->name('dashboard.ai-usage-today');
Route::middleware(['auth'])->group(function () {
    // "stop" must be declared BEFORE the {tenant} route, otherwise Laravel
    // matches /admin/view-as/stop against {tenant} and tries to resolve a
    // tenant named "stop".
    Route::post('/admin/view-as/stop', [DashboardController::class, 'stopViewingAs'])->name('admin.viewAs.stop');
    Route::post('/admin/view-as/{tenant}', [DashboardController::class, 'viewAsTenant'])->name('admin.viewAs.enter');
    Route::get('/admin/tenants/search', [DashboardController::class, 'searchTenants'])->name('admin.tenants.search');
});

// Billing routes (dashboard).
// Plan changes, cancellations, top-ups and the Stripe portal all touch
// the tenant's subscription and card file — a tenant_viewer canceling
// the subscription would stop the entire tenant from operating. Admin
// only.
Route::middleware('auth')->prefix('dashboard/facturare')->group(function () {
    Route::get('/', [BillingController::class, 'index'])->name('dashboard.billing.index');
    Route::get('/facturi', [BillingController::class, 'invoices'])->name('dashboard.billing.invoices');
    Route::get('/facturi/{invoice}/download', [BillingController::class, 'downloadInvoice'])->name('dashboard.billing.downloadInvoice');

    Route::middleware('tenant.role:tenant_admin')->group(function () {
        Route::post('/subscribe/{plan}', [BillingController::class, 'subscribe'])->name('dashboard.billing.subscribe');
        Route::post('/change-plan/{plan}', [BillingController::class, 'changePlan'])->name('dashboard.billing.changePlan');
        Route::post('/cancel', [BillingController::class, 'cancelSubscription'])->name('dashboard.billing.cancel');
        Route::post('/resume', [BillingController::class, 'resumeSubscription'])->name('dashboard.billing.resume');
        Route::post('/topup/{plan}/{bundleIndex}', [BillingController::class, 'topup'])->name('dashboard.billing.topup');
        Route::get('/portal', [BillingController::class, 'portal'])->name('dashboard.billing.portal');
    });
});

// Bot routes (dashboard)
Route::middleware('auth')->prefix('dashboard/agenti')->group(function () {
    Route::get('/', [BotController::class, 'index'])->name('dashboard.bots.index');
    Route::get('/nou', [BotController::class, 'create'])->name('dashboard.bots.create');
    Route::post('/', [BotController::class, 'store'])->name('dashboard.bots.store');
    Route::get('/{bot}', [BotController::class, 'show'])->name('dashboard.bots.show');
    Route::get('/{bot}/editare', [BotController::class, 'edit'])->name('dashboard.bots.edit');
    Route::put('/{bot}', [BotController::class, 'update'])->name('dashboard.bots.update');

    // Session-authenticated helpers for the structured-profile editor.
    // These wrap the Sanctum-gated /api/v1 endpoints so the dashboard
    // can call them with CSRF instead of minting a bearer token.
    Route::post('/{bot}/ai-generate', [BotController::class, 'aiGenerate'])
        ->name('dashboard.bots.ai-generate');
    Route::get('/{bot}/prompt-preview', [BotController::class, 'promptPreview'])
        ->name('dashboard.bots.prompt-preview');
    Route::get('/{bot}/ai-cost-today', [BotController::class, 'aiCostToday'])
        ->name('dashboard.bots.ai-cost-today');

    Route::delete('/{bot}', [BotController::class, 'destroy'])->name('dashboard.bots.destroy');
    Route::patch('/{bot}/toggle', [BotController::class, 'toggleActive'])->name('dashboard.bots.toggle');
    Route::patch('/{bot}/update-field', [BotController::class, 'updateField'])->name('dashboard.bots.updateField');

    // Setup-AI cost summary (session-auth mirror of the Sanctum API).
    // Lives here so the dashboard Blade/Alpine UI can hit it with
    // CSRF + session cookie instead of minting a Sanctum token for
    // the same logged-in user.
    Route::get('/{bot}/ai-cost-summary', [\App\Http\Controllers\Api\V1\BotAiCostSummaryController::class, 'show'])
        ->name('dashboard.bots.aiCostSummary');
    Route::post('/{bot}/policy', [BotController::class, 'updatePolicy'])->name('dashboard.bots.updatePolicy');

    // WooCommerce meta-mapping UI — tenant admin decides how raw
    // WP meta keys map onto standardized product fields the bot
    // prompt consumes. Gated by tenant.role because it's a data-
    // structure decision, not a daily operation.
    Route::get('/{bot}/wc-meta', [\App\Http\Controllers\Dashboard\WcMetaMappingController::class, 'index'])
        ->name('dashboard.bots.wcMeta.index');
    Route::put('/{bot}/wc-meta', [\App\Http\Controllers\Dashboard\WcMetaMappingController::class, 'update'])
        ->middleware('tenant.role:tenant_admin,tenant_manager')
        ->name('dashboard.bots.wcMeta.update');

    // Voice cloning — ElevenLabs jobs are billable and identity-sensitive
    // (a cloned voice gets attached to outbound calls). Admin/manager only.
    Route::get('/{bot}/voice-clone', [ClonedVoiceController::class, 'create'])->name('dashboard.bots.voiceClone.create');
    Route::get('/{bot}/voice-clone/{clonedVoice}/status', [ClonedVoiceController::class, 'status'])->name('dashboard.bots.voiceClone.status');
    Route::middleware('tenant.role:tenant_admin,tenant_manager')->group(function () {
        Route::post('/{bot}/voice-clone', [ClonedVoiceController::class, 'store'])->name('dashboard.bots.voiceClone.store');
        Route::post('/{bot}/voice-clone/{clonedVoice}/activate', [ClonedVoiceController::class, 'activate'])->name('dashboard.bots.voiceClone.activate');
        Route::post('/{bot}/voice-clone/deactivate', [ClonedVoiceController::class, 'deactivate'])->name('dashboard.bots.voiceClone.deactivate');
        Route::delete('/{bot}/voice-clone/{clonedVoice}', [ClonedVoiceController::class, 'destroy'])->name('dashboard.bots.voiceClone.destroy');
    });
});

// Booking admin routes (Iteration E) — services / staff / hours CRUD for
// bots on the booking/hybrid engine. Controller enforces bot tenancy +
// engine_type check on every action; here we only gate on auth.
Route::middleware('auth')->prefix('dashboard/agenti/{bot}/programari')->group(function () {
    Route::get('/', [BookingAdminController::class, 'index'])->name('dashboard.bots.booking');
    Route::post('/servicii',                         [BookingAdminController::class, 'storeService'])->name('dashboard.bots.booking.services.store');
    Route::patch('/servicii/{serviceType}',          [BookingAdminController::class, 'updateService'])->name('dashboard.bots.booking.services.update');
    Route::delete('/servicii/{serviceType}',         [BookingAdminController::class, 'destroyService'])->name('dashboard.bots.booking.services.destroy');
    Route::post('/personal',                         [BookingAdminController::class, 'storeStaff'])->name('dashboard.bots.booking.staff.store');
    Route::patch('/personal/{staff}',                [BookingAdminController::class, 'updateStaff'])->name('dashboard.bots.booking.staff.update');
    Route::delete('/personal/{staff}',               [BookingAdminController::class, 'destroyStaff'])->name('dashboard.bots.booking.staff.destroy');
    Route::put('/program',                           [BookingAdminController::class, 'updateHours'])->name('dashboard.bots.booking.hours.update');
    Route::post('/advanced-mode',                    [BookingAdminController::class, 'toggleAdvanced'])->name('dashboard.bots.booking.advancedMode');
});

// Calls routes (dashboard)
Route::middleware('auth')->prefix('dashboard/apeluri')->group(function () {
    Route::get('/', [CallController::class, 'index'])->name('dashboard.calls.index');
    Route::get('/{call}', [CallController::class, 'show'])->name('dashboard.calls.show');
    Route::delete('/{call}', [CallController::class, 'destroy'])->name('dashboard.calls.destroy');
    Route::get('/{call}/export/{format?}', [CallController::class, 'exportTranscript'])->name('dashboard.calls.export-transcript');
});

// Conversations routes (dashboard) — text-based channels
Route::middleware('auth')->prefix('dashboard/transcrieri')->group(function () {
    Route::get('/conversatie/{conversation}', [ConversationController::class, 'show'])->name('dashboard.conversations.show');
    Route::post('/conversatie/{conversation}/take-over', [ConversationController::class, 'takeOver'])
        ->middleware('tenant.role:tenant_admin,tenant_manager')
        ->name('dashboard.conversations.take-over');
    Route::post('/conversatie/{conversation}/hand-back', [ConversationController::class, 'handBack'])
        ->middleware('tenant.role:tenant_admin,tenant_manager')
        ->name('dashboard.conversations.hand-back');
    Route::delete('/conversatie/{conversation}', [ConversationController::class, 'destroy'])
        ->middleware('tenant.role:tenant_admin,tenant_manager')
        ->name('dashboard.conversations.destroy');
    Route::get('/{channelType}', [ConversationController::class, 'index'])->name('dashboard.conversations.index');
});

// Analytics routes (dashboard)
Route::middleware('auth')->prefix('dashboard/analiza')->group(function () {
    Route::get('/', [AnalyticsController::class, 'index'])->name('dashboard.analytics.index');
    Route::get('/export', [AnalyticsController::class, 'export'])->name('dashboard.analytics.export');
});

// Phone numbers routes (dashboard)
Route::middleware('auth')->prefix('dashboard/numere')->group(function () {
    Route::get('/', [PhoneNumberController::class, 'index'])->name('dashboard.numbers.index');
    Route::get('/available', [PhoneNumberController::class, 'availableNumbers'])->name('dashboard.numbers.available');

    // Mutation surface — admin or manager only. Previously a tenant_viewer
    // could provision numbers (billable Telnyx orders) or toggle/delete the
    // tenant's telephony inventory.
    Route::middleware('tenant.role:tenant_admin,tenant_manager')->group(function () {
        Route::post('/', [PhoneNumberController::class, 'store'])->name('dashboard.numbers.store');
        Route::put('/{phoneNumber}', [PhoneNumberController::class, 'update'])->name('dashboard.numbers.update');
        Route::delete('/{phoneNumber}', [PhoneNumberController::class, 'destroy'])->name('dashboard.numbers.destroy');
        Route::patch('/{phoneNumber}/toggle', [PhoneNumberController::class, 'toggleActive'])->name('dashboard.numbers.toggle');
        Route::post('/sync-statuses', [PhoneNumberController::class, 'syncStatuses'])->name('dashboard.numbers.syncStatuses');
    });
});

// Team routes (dashboard).
// Invite / role-change / remove are admin-only — before iter 13 they were
// guarded only by a same-tenant check, so any tenant_viewer could invite
// new users and `updateRole` themselves to `tenant_admin`. Full privilege
// escalation path from a viewer account.
Route::middleware('auth')->prefix('dashboard/echipa')->group(function () {
    Route::get('/', [TeamController::class, 'index'])->name('dashboard.team.index');
    Route::post('/invite', [TeamController::class, 'invite'])
        ->middleware('tenant.role:tenant_admin')
        ->name('dashboard.team.invite');
    Route::patch('/{user}/role', [TeamController::class, 'updateRole'])
        ->middleware('tenant.role:tenant_admin')
        ->name('dashboard.team.updateRole');
    Route::delete('/{user}/remove', [TeamController::class, 'remove'])
        ->middleware('tenant.role:tenant_admin')
        ->name('dashboard.team.remove');
});

// Tenant audit log — read-only, vizibil tenant_admin/manager
// Listează cine a editat ce, când, de unde, cu diff complet.
Route::middleware(['auth', 'tenant.role:tenant_admin,tenant_manager'])
    ->get('/dashboard/activitate', [\App\Http\Controllers\Dashboard\AuditController::class, 'index'])
    ->name('dashboard.audit.index');

// Hub global pentru canale — toate canalele cross-bot, accesibil în 1 click din sidebar
Route::middleware('auth')
    ->get('/dashboard/canale', [\App\Http\Controllers\Dashboard\ChannelsHubController::class, 'index'])
    ->name('dashboard.channels-hub.index');

// Live activity snapshot endpoint — JSON, polled la 5s de widget-ul live
Route::middleware('auth')
    ->get('/dashboard/live-activity', [\App\Http\Controllers\Dashboard\LiveActivityController::class, 'snapshot'])
    ->name('dashboard.live-activity');

// AI Insights — POST endpoint, cache 30 min/tenant, force=1 pentru regenerare
Route::middleware('auth')
    ->post('/dashboard/insights', [\App\Http\Controllers\Dashboard\InsightsController::class, 'generate'])
    ->name('dashboard.insights.generate');

// Conversation heatmap — JSON pentru widget pe /dashboard/analiza
Route::middleware('auth')
    ->get('/dashboard/heatmap', [\App\Http\Controllers\Dashboard\AnalyticsController::class, 'heatmap'])
    ->name('dashboard.analytics.heatmap');

// Conversion funnel — visitors → conv → engaged → lead → callback → done
Route::middleware('auth')
    ->get('/dashboard/funnel', [\App\Http\Controllers\Dashboard\AnalyticsController::class, 'funnel'])
    ->name('dashboard.analytics.funnel');

// Cost forecast — proiecție end-of-month bazată pe last 7d rate
Route::middleware('auth')
    ->get('/dashboard/cost-forecast', [\App\Http\Controllers\Dashboard\CostForecastController::class, 'snapshot'])
    ->name('dashboard.cost-forecast');

// Smart reply suggestions — operator în conversație cere 3 sugestii LLM
Route::middleware('auth')
    ->post('/dashboard/conversatie/{conversation}/smart-reply', [\App\Http\Controllers\Dashboard\SmartReplyController::class, 'suggest'])
    ->name('dashboard.conversation.smart-reply');

// Auto-tag conversație cu LLM — intent + sentiment + urgency + topics
Route::middleware('auth')
    ->post('/dashboard/conversatie/{conversation}/auto-tag', [\App\Http\Controllers\Dashboard\AutoTagController::class, 'tag'])
    ->name('dashboard.conversation.auto-tag');

// Replay conversație — playback timing-aware cu speed control 1x..16x
Route::middleware('auth')
    ->get('/dashboard/transcrieri/conversatie/{conversation}/replay', [\App\Http\Controllers\Dashboard\ConversationReplayController::class, 'show'])
    ->name('dashboard.conversations.replay');

// Operator console PWA + Web Push — preluare live conversații
Route::middleware('auth')->group(function () {
    $op = \App\Http\Controllers\Dashboard\OperatorConsoleController::class;
    Route::get('/dashboard/operator',                        [$op, 'show'])->name('dashboard.operator.show');
    Route::get('/dashboard/operator/feed',                   [$op, 'feed'])->name('dashboard.operator.feed');
    Route::get('/dashboard/operator/conv/{conversation}',    [$op, 'messages'])->name('dashboard.operator.messages');
    Route::post('/dashboard/operator/conv/{conversation}/take',    [$op, 'take'])->name('dashboard.operator.take');
    Route::post('/dashboard/operator/conv/{conversation}/release', [$op, 'release'])->name('dashboard.operator.release');
    Route::post('/dashboard/operator/conv/{conversation}/reply',   [$op, 'reply'])->name('dashboard.operator.reply');
    Route::post('/dashboard/operator/push/subscribe', [$op, 'pushSubscribe'])->name('dashboard.operator.push.subscribe');
    Route::post('/dashboard/operator/push/test',      [$op, 'pushTest'])->name('dashboard.operator.push.test');
});

// Knowledge gaps — listă query-uri zero-result + AI suggester
Route::middleware('auth')->group(function () {
    $kg = \App\Http\Controllers\Dashboard\KnowledgeGapsController::class;
    Route::get('/dashboard/agenti/{bot}/knowledge-gaps', [$kg, 'show'])->name('dashboard.knowledge-gaps.show');
    Route::post('/dashboard/agenti/{bot}/knowledge-gaps/suggest', [$kg, 'suggestFaq'])->name('dashboard.knowledge-gaps.suggest');

    // Mock customer simulator — AI joacă rol de client + raport de calitate
    $mc = \App\Http\Controllers\Dashboard\MockCustomerController::class;
    Route::get('/dashboard/agenti/{bot}/mock-customer', [$mc, 'show'])->name('dashboard.mock-customer.show');
    Route::post('/dashboard/agenti/{bot}/mock-customer/run', [$mc, 'run'])->name('dashboard.mock-customer.run');

    // Embed customizer — color, position, greeting, lang cu live preview
    $ec = \App\Http\Controllers\Dashboard\EmbedCustomizerController::class;
    Route::get('/dashboard/agenti/{bot}/embed-customizer', [$ec, 'show'])->name('dashboard.embed-customizer.show');
    Route::post('/dashboard/agenti/{bot}/embed-customizer', [$ec, 'update'])->name('dashboard.embed-customizer.update');
    Route::get('/dashboard/agenti/{bot}/embed-customizer/preview', [$ec, 'previewFrame'])->name('dashboard.embed-customizer.preview');
});

// Playground per bot: chat tester + voice preview + embed live preview
Route::middleware('auth')->group(function () {
    $pg = \App\Http\Controllers\Dashboard\PlaygroundController::class;
    Route::get('/dashboard/agenti/{bot}/playground', [$pg, 'show'])->name('dashboard.playground.show');
    Route::get('/dashboard/agenti/{bot}/playground/preview', [$pg, 'previewFrame'])->name('dashboard.playground.preview');
    Route::post('/dashboard/agenti/{bot}/playground/tts', [$pg, 'tts'])->name('dashboard.playground.tts');
});

// Public mobile preview (signed URL, 1h validity, no auth) — pentru QR scan
Route::get('/playground-mobile/{bot}', [\App\Http\Controllers\Dashboard\PlaygroundController::class, 'publicPreview'])
    ->middleware('signed')
    ->name('dashboard.playground.public');

// A/B prompt comparison — 2 variante side by side, model raw fără RAG
Route::middleware('auth')->group(function () {
    $ab = \App\Http\Controllers\Dashboard\AbPromptController::class;
    Route::get('/dashboard/agenti/{bot}/ab-prompt', [$ab, 'show'])->name('dashboard.ab-prompt.show');
    Route::post('/dashboard/agenti/{bot}/ab-prompt/compare', [$ab, 'compare'])->name('dashboard.ab-prompt.compare');

    // Personalitate wizard — 5 sliders pentru tone_guide cu preview live
    $pw = \App\Http\Controllers\Dashboard\PersonalityWizardController::class;
    Route::get('/dashboard/agenti/{bot}/personalitate', [$pw, 'show'])->name('dashboard.personality-wizard.show');
    Route::post('/dashboard/agenti/{bot}/personalitate', [$pw, 'update'])->name('dashboard.personality-wizard.update');
});

// Admin RAG analytics
Route::middleware(['auth', 'super_admin'])
    ->get('/admin/rag', [\App\Http\Controllers\Admin\AdminRagAnalyticsController::class, 'index'])
    ->name('admin.rag.index');

// Outbound webhooks tenant — CRUD + delivery log
Route::middleware(['auth', 'tenant.role:tenant_admin,tenant_manager'])
    ->prefix('dashboard/webhooks')->group(function () {
        $c = \App\Http\Controllers\Dashboard\WebhookEndpointController::class;
        Route::get('/', [$c, 'index'])->name('dashboard.webhooks.index');
        Route::get('/nou', [$c, 'create'])->name('dashboard.webhooks.create');
        Route::post('/', [$c, 'store'])->name('dashboard.webhooks.store');
        Route::get('/{endpoint}', [$c, 'show'])->name('dashboard.webhooks.show');
        Route::get('/{endpoint}/edit', [$c, 'edit'])->name('dashboard.webhooks.edit');
        Route::put('/{endpoint}', [$c, 'update'])->name('dashboard.webhooks.update');
        Route::delete('/{endpoint}', [$c, 'destroy'])->name('dashboard.webhooks.destroy');
        Route::post('/{endpoint}/test', [$c, 'testFire'])->name('dashboard.webhooks.testFire');
        Route::get('/{endpoint}/playground', [$c, 'playground'])->name('dashboard.webhooks.playground');
        Route::post('/{endpoint}/playground/fire', [$c, 'playgroundFire'])->name('dashboard.webhooks.playground.fire');
    });

// Settings routes (dashboard)
Route::middleware('auth')->prefix('dashboard/setari')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('dashboard.settings.index');
    Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('dashboard.settings.updateProfile');
    Route::put('/password', [SettingsController::class, 'updatePassword'])->name('dashboard.settings.updatePassword');
    Route::put('/company', [SettingsController::class, 'updateCompany'])
        ->middleware('tenant.role:tenant_admin')
        ->name('dashboard.settings.updateCompany');
    Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('dashboard.settings.updateNotifications');
    // Rate-limit token mint to 5/min per user. Without it an account that
    // leaks session cookies once can mint hundreds of long-lived tokens
    // before the user notices and rotates the session.
    Route::post('/api-keys', [SettingsController::class, 'generateApiKey'])
        ->middleware('throttle:5,1')
        ->name('dashboard.settings.generateApiKey');
    Route::delete('/api-keys/{tokenId}', [SettingsController::class, 'revokeApiKey'])->name('dashboard.settings.revokeApiKey');
    // destroyAccount cascade-deletes the tenant plus every user, bot, call,
    // and credit record attached to it. Before iter 13 any tenant_viewer
    // could POST STERGE and wipe the account for everybody.
    Route::delete('/account', [SettingsController::class, 'destroyAccount'])
        ->middleware('tenant.role:tenant_admin')
        ->name('dashboard.settings.destroyAccount');
});

// Channel management routes (dashboard).
// Channels hold per-integration credentials (webhook tokens, page IDs,
// API keys). Mutating them lets an attacker redirect inbound traffic or
// disconnect real channels — manager-level action.
Route::middleware('auth')->prefix('dashboard/agenti/{bot}/canale')->group(function () {
    Route::get('/', [ChannelController::class, 'index'])->name('dashboard.bots.channels.index');
    Route::get('/{channel}/chips', [ChannelController::class, 'editChips'])->name('dashboard.bots.channels.chips.edit');
    Route::get('/{channel}/setup', [ChannelController::class, 'chatbotSetup'])->name('dashboard.bots.channels.chatbot-setup');
    Route::middleware('tenant.role:tenant_admin,tenant_manager')->group(function () {
        Route::post('/', [ChannelController::class, 'store'])->name('dashboard.bots.channels.store');
        Route::put('/{channel}', [ChannelController::class, 'update'])->name('dashboard.bots.channels.update');
        Route::put('/{channel}/chips', [ChannelController::class, 'updateChips'])->name('dashboard.bots.channels.chips.update');
        Route::put('/{channel}/setup', [ChannelController::class, 'saveChatbotSetup'])->name('dashboard.bots.channels.chatbot-setup.save');
        Route::delete('/{channel}', [ChannelController::class, 'destroy'])->name('dashboard.bots.channels.destroy');
        Route::patch('/{channel}/toggle', [ChannelController::class, 'toggleActive'])->name('dashboard.bots.channels.toggle');

        // Manual-paste WhatsApp Cloud API onboarding wizard (fallback to
        // embedded signup until Meta Tech Provider status lands).
        Route::get('/whatsapp/connect', [ChannelController::class, 'connectWhatsApp'])
            ->name('dashboard.bots.channels.whatsapp.connect');
        Route::post('/whatsapp/connect', [ChannelController::class, 'storeWhatsApp'])
            ->middleware('throttle:10,1')
            ->name('dashboard.bots.channels.whatsapp.store');
        Route::get('/whatsapp/{channel}/connected', [ChannelController::class, 'whatsAppConnected'])
            ->name('dashboard.bots.channels.whatsapp.connected');

        // Manual-paste Facebook Messenger onboarding wizard. Same pattern
        // as WhatsApp; OAuth Login-for-Business will layer on top once
        // Meta App Review approves pages_messaging + pages_show_list.
        Route::get('/facebook/connect', [ChannelController::class, 'connectFacebook'])
            ->name('dashboard.bots.channels.facebook.connect');
        Route::post('/facebook/connect', [ChannelController::class, 'storeFacebook'])
            ->middleware('throttle:10,1')
            ->name('dashboard.bots.channels.facebook.store');
        Route::get('/facebook/{channel}/connected', [ChannelController::class, 'facebookConnected'])
            ->name('dashboard.bots.channels.facebook.connected');

        // Manual-paste Instagram DM onboarding wizard. IG Business
        // accounts ride on a Facebook Page — the page_access_token here is
        // the same one used by the FB Messenger channel if both are linked
        // to the same page.
        Route::get('/instagram/connect', [ChannelController::class, 'connectInstagram'])
            ->name('dashboard.bots.channels.instagram.connect');
        Route::post('/instagram/connect', [ChannelController::class, 'storeInstagram'])
            ->middleware('throttle:10,1')
            ->name('dashboard.bots.channels.instagram.store');
        Route::get('/instagram/{channel}/connected', [ChannelController::class, 'instagramConnected'])
            ->name('dashboard.bots.channels.instagram.connected');

        // WhatsApp template management (Etapa 3 of the omnichannel roadmap).
        // Templates are scoped per channel because Meta scopes per WABA.
        Route::get('/{channel}/templates', [\App\Http\Controllers\Dashboard\WhatsAppTemplateController::class, 'index'])
            ->name('dashboard.bots.channels.whatsapp-templates.index');
        Route::get('/{channel}/templates/new', [\App\Http\Controllers\Dashboard\WhatsAppTemplateController::class, 'create'])
            ->name('dashboard.bots.channels.whatsapp-templates.create');
        Route::post('/{channel}/templates', [\App\Http\Controllers\Dashboard\WhatsAppTemplateController::class, 'store'])
            ->name('dashboard.bots.channels.whatsapp-templates.store');
        Route::get('/{channel}/templates/{template}', [\App\Http\Controllers\Dashboard\WhatsAppTemplateController::class, 'edit'])
            ->name('dashboard.bots.channels.whatsapp-templates.edit');
        Route::put('/{channel}/templates/{template}', [\App\Http\Controllers\Dashboard\WhatsAppTemplateController::class, 'update'])
            ->name('dashboard.bots.channels.whatsapp-templates.update');
        Route::post('/{channel}/templates/{template}/submit', [\App\Http\Controllers\Dashboard\WhatsAppTemplateController::class, 'submit'])
            ->name('dashboard.bots.channels.whatsapp-templates.submit');
        Route::delete('/{channel}/templates/{template}', [\App\Http\Controllers\Dashboard\WhatsAppTemplateController::class, 'destroy'])
            ->name('dashboard.bots.channels.whatsapp-templates.destroy');
        Route::post('/{channel}/templates/sync', [\App\Http\Controllers\Dashboard\WhatsAppTemplateController::class, 'syncFromMeta'])
            ->name('dashboard.bots.channels.whatsapp-templates.sync');
    });
});

// Unified inbox across all channels (Etapa 6). The per-channel
// /dashboard/conversatii/{type} views still exist; this is the
// collapsed all-in-one for operators triaging across channels.
Route::middleware('auth')
    ->get('/dashboard/inbox', [\App\Http\Controllers\Dashboard\InboxController::class, 'index'])
    ->name('dashboard.inbox');

// MCP server management (per-tenant). Lets tenants register their own
// Model Context Protocol endpoints; the orchestrator surfaces those tools
// to the LLM at conversation time.
Route::middleware(['auth', 'tenant.role:tenant_admin,tenant_manager'])
    ->prefix('dashboard/mcp-servere')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Dashboard\McpServerController::class, 'index'])->name('dashboard.mcp-servers.index');
        Route::post('/', [\App\Http\Controllers\Dashboard\McpServerController::class, 'store'])->name('dashboard.mcp-servers.store');
        Route::post('/{server}/ping', [\App\Http\Controllers\Dashboard\McpServerController::class, 'ping'])->name('dashboard.mcp-servers.ping');
        Route::delete('/{server}', [\App\Http\Controllers\Dashboard\McpServerController::class, 'destroy'])->name('dashboard.mcp-servers.destroy');
    });

// Site management routes (dashboard)
Route::middleware('auth')->prefix('dashboard/sites')->group(function () {
    Route::get('/', [SiteController::class, 'index'])->name('dashboard.sites.index');
    Route::get('/new', [SiteController::class, 'create'])->name('dashboard.sites.create');
    Route::get('/{site}', [SiteController::class, 'show'])->name('dashboard.sites.show');
    Route::middleware('tenant.role:tenant_admin,tenant_manager')->group(function () {
        Route::post('/', [SiteController::class, 'store'])->name('dashboard.sites.store');
        Route::put('/{site}', [SiteController::class, 'update'])->name('dashboard.sites.update');
        Route::delete('/{site}', [SiteController::class, 'destroy'])->name('dashboard.sites.destroy');
        Route::post('/{site}/verify', [SiteController::class, 'verify'])->name('dashboard.sites.verify');
    });
});

// V2: Leads, Opportunities, Commerce Analytics (dashboard)
Route::middleware('auth')->prefix('dashboard')->group(function () {
    // Callbacks
    Route::get('/callbacks', [\App\Http\Controllers\Dashboard\CallbackController::class, 'index'])->name('dashboard.callbacks.index');
    Route::get('/callbacks/{callback}', [\App\Http\Controllers\Dashboard\CallbackController::class, 'show'])->name('dashboard.callbacks.show');
    Route::post('/callbacks/{callback}/status', [\App\Http\Controllers\Dashboard\CallbackController::class, 'updateStatus'])->name('dashboard.callbacks.updateStatus');

    // Leads
    Route::get('/leads', [\App\Http\Controllers\Dashboard\LeadController::class, 'index'])->name('dashboard.leads.index');
    Route::get('/leads/export', [\App\Http\Controllers\Dashboard\LeadController::class, 'export'])->name('dashboard.leads.export');
    Route::get('/leads/{lead}', [\App\Http\Controllers\Dashboard\LeadController::class, 'show'])->name('dashboard.leads.show');
    Route::post('/leads/{lead}/status', [\App\Http\Controllers\Dashboard\LeadController::class, 'updateStatus'])->name('dashboard.leads.status');
    Route::post('/leads/{lead}/notes', [\App\Http\Controllers\Dashboard\LeadController::class, 'addNote'])->name('dashboard.leads.notes');

    // Opportunities
    Route::get('/opportunities', [\App\Http\Controllers\Dashboard\OpportunityController::class, 'index'])->name('dashboard.opportunities.index');
    Route::get('/opportunities/{conversation}', [\App\Http\Controllers\Dashboard\OpportunityController::class, 'show'])->name('dashboard.opportunities.show');

    // Commerce Analytics
    Route::get('/conversii', [\App\Http\Controllers\Dashboard\CommerceAnalyticsController::class, 'index'])->name('dashboard.commerce.index');
});

// A/B Testing experiments routes (dashboard)
Route::middleware('auth')->prefix('dashboard/agenti/{bot}/experiments')->group(function () {
    Route::get('/', [\App\Http\Controllers\Dashboard\AbTestingController::class, 'index'])->name('dashboard.bots.experiments.index');
    Route::post('/', [\App\Http\Controllers\Dashboard\AbTestingController::class, 'store'])->name('dashboard.bots.experiments.store');
    Route::get('/{experiment}', [\App\Http\Controllers\Dashboard\AbTestingController::class, 'show'])->name('dashboard.bots.experiments.show');
    Route::put('/{experiment}', [\App\Http\Controllers\Dashboard\AbTestingController::class, 'update'])->name('dashboard.bots.experiments.update');
    Route::delete('/{experiment}', [\App\Http\Controllers\Dashboard\AbTestingController::class, 'destroy'])->name('dashboard.bots.experiments.destroy');
});

// Google OAuth (per-tenant Google Drive connection)
Route::middleware('auth')->prefix('oauth/google')->group(function () {
    Route::get('/connect', [\App\Http\Controllers\Auth\GoogleOAuthController::class, 'connect'])->name('oauth.google.connect');
    Route::get('/callback', [\App\Http\Controllers\Auth\GoogleOAuthController::class, 'callback'])->name('oauth.google.callback');
    Route::post('/disconnect', [\App\Http\Controllers\Auth\GoogleOAuthController::class, 'disconnect'])->name('oauth.google.disconnect');
});

// Knowledge base routes (dashboard).
// Writes to the knowledge base feed straight into the RAG prompt the
// agent answers customers with — a malicious viewer could poison answers
// or wipe the store. Gate writes behind admin/manager; reads stay open
// to everyone in the tenant.
Route::middleware('auth')->prefix('dashboard/agenti/{bot}')->group(function () {
    Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('dashboard.bots.knowledge.index');
    Route::delete('/knowledge/{title}', [KnowledgeController::class, 'destroy'])
        ->middleware('tenant.role:tenant_admin,tenant_manager')
        ->name('dashboard.bots.knowledge.destroy');

    // Rate-limited mutation routes (10 requests per minute per user)
    Route::middleware(['throttle:10,1', 'tenant.role:tenant_admin,tenant_manager'])->group(function () {
        Route::post('/knowledge', [KnowledgeController::class, 'store'])->name('dashboard.bots.knowledge.store');

        // AI Agents
        Route::post('/knowledge/agent/run', [KnowledgeController::class, 'runAgent'])->name('dashboard.bots.knowledge.agent.run');
        Route::post('/knowledge/agent/{run}/save', [KnowledgeController::class, 'saveAgentResult'])->name('dashboard.bots.knowledge.agent.save');
        Route::put('/knowledge/agent/{slug}/customize', [KnowledgeController::class, 'customizeAgent'])->name('dashboard.bots.knowledge.agent.customize');

        // Website Scanner
        Route::post('/knowledge/scan', [KnowledgeController::class, 'startScan'])->name('dashboard.bots.knowledge.scan.start');
        Route::post('/knowledge/scan/{scan}/cancel', [KnowledgeController::class, 'cancelScan'])->name('dashboard.bots.knowledge.scan.cancel');

        // Connectors
        Route::post('/knowledge/connector', [KnowledgeController::class, 'storeConnector'])->name('dashboard.bots.knowledge.connector.store');
        Route::post('/knowledge/connector/{connector}/test', [KnowledgeController::class, 'testConnector'])->name('dashboard.bots.knowledge.connector.test');
        Route::post('/knowledge/connector/{connector}/sync', [KnowledgeController::class, 'syncConnector'])->name('dashboard.bots.knowledge.connector.sync');
        Route::get('/knowledge/sync-progress', [KnowledgeController::class, 'syncProgress'])->name('dashboard.bots.knowledge.sync-progress');
        Route::delete('/knowledge/connector/{connector}', [KnowledgeController::class, 'destroyConnector'])->name('dashboard.bots.knowledge.connector.destroy');

        // Google Drive: import picked files + delete a single drive file
        Route::post('/knowledge/connector/{connector}/drive/import', [KnowledgeController::class, 'importDriveFiles'])->name('dashboard.bots.knowledge.drive.import');
        Route::delete('/knowledge/connector/{connector}/drive/{driveFile}', [KnowledgeController::class, 'destroyDriveFile'])->name('dashboard.bots.knowledge.drive.destroy');
    });

    // Read-only status endpoints (higher limit: 60/min)
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/knowledge/agent/{run}/status', [KnowledgeController::class, 'agentStatus'])->name('dashboard.bots.knowledge.agent.status');
        Route::get('/knowledge/scan/{scan}/status', [KnowledgeController::class, 'scanStatus'])->name('dashboard.bots.knowledge.scan.status');
    });
});

// Admin Dashboard route (super_admin only)
Route::get('/dashboard/admin', [DashboardController::class, 'admin'])
    ->middleware(['auth', 'super_admin'])
    ->name('dashboard.admin');

// Tinder-style PWA for social review (separate from /admin so it can be
// installed as its own home-screen icon via swipe-manifest.json).
Route::middleware(['auth', 'super_admin'])->prefix('swipe')->name('swipe.')->group(function () {
    Route::get('/', [SwipeController::class, 'home'])->name('home');
    Route::get('/queue', [SwipeController::class, 'queue'])->name('queue');
    Route::post('/{post}/approve', [SwipeController::class, 'approve'])->name('approve');
    Route::post('/{post}/reject', [SwipeController::class, 'reject'])->name('reject');
});

// Admin Panel (super_admin only)
Route::middleware(['auth', 'super_admin'])->prefix('admin')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/boti', [\App\Http\Controllers\Admin\AdminBotController::class, 'index'])->name('admin.bots.index');
    Route::get('/boti/{botId}', [\App\Http\Controllers\Admin\AdminBotController::class, 'show'])->name('admin.bots.show');
    Route::get('/apeluri', [\App\Http\Controllers\Admin\AdminCallController::class, 'index'])->name('admin.calls.index');
    Route::get('/apeluri/{callId}', [\App\Http\Controllers\Admin\AdminCallController::class, 'show'])->name('admin.calls.show');
    Route::get('/conversatii', [\App\Http\Controllers\Admin\AdminConversationController::class, 'index'])->name('admin.conversations.index');
    Route::get('/conversatii/{conversationId}', [\App\Http\Controllers\Admin\AdminConversationController::class, 'show'])->name('admin.conversations.show');
    Route::get('/tenanti', [\App\Http\Controllers\Admin\AdminTenantController::class, 'index'])->name('admin.tenants.index');
    Route::get('/tenanti/{tenant}', [\App\Http\Controllers\Admin\AdminTenantController::class, 'show'])->name('admin.tenants.show');
    Route::post('/tenanti/{tenant}/override', [\App\Http\Controllers\Admin\AdminTenantController::class, 'override'])->name('admin.tenants.override');
    Route::delete('/tenanti/{tenant}/override/{key}', [\App\Http\Controllers\Admin\AdminTenantController::class, 'removeOverride'])->name('admin.tenants.removeOverride');
    Route::post('/tenanti/{tenant}/plan', [\App\Http\Controllers\Admin\AdminTenantController::class, 'changePlan'])->name('admin.tenants.changePlan');

    // Admin Settings
    Route::get('/setari', [AdminSettingsController::class, 'index'])->name('admin.settings.index');
    Route::put('/setari/general', [AdminSettingsController::class, 'updateGeneral'])->name('admin.settings.updateGeneral');
    Route::put('/setari/openai', [AdminSettingsController::class, 'updateOpenai'])->name('admin.settings.updateOpenai');
    Route::put('/setari/telnyx', [AdminSettingsController::class, 'updateTelnyx'])->name('admin.settings.updateTelnyx');
    Route::put('/setari/twilio', [AdminSettingsController::class, 'updateTwilio'])->name('admin.settings.updateTwilio');
    Route::get('/twilio/consum', [\App\Http\Controllers\Admin\TwilioUsageController::class, 'index'])->name('admin.twilio.usage');
    Route::put('/setari/stripe', [AdminSettingsController::class, 'updateStripe'])->name('admin.settings.updateStripe');
    Route::put('/setari/email', [AdminSettingsController::class, 'updateEmail'])->name('admin.settings.updateEmail');
    Route::put('/setari/whatsapp', [AdminSettingsController::class, 'updateWhatsapp'])->name('admin.settings.updateWhatsapp');
    Route::put('/setari/facebook', [AdminSettingsController::class, 'updateFacebook'])->name('admin.settings.updateFacebook');
    Route::put('/setari/instagram', [AdminSettingsController::class, 'updateInstagram'])->name('admin.settings.updateInstagram');
    Route::put('/setari/elevenlabs', [AdminSettingsController::class, 'updateElevenlabs'])->name('admin.settings.updateElevenlabs');
    Route::put('/setari/anthropic', [AdminSettingsController::class, 'updateAnthropic'])->name('admin.settings.updateAnthropic');
    Route::put('/setari/sentry', [AdminSettingsController::class, 'updateSentry'])->name('admin.settings.updateSentry');
    Route::put('/setari/securitate', [AdminSettingsController::class, 'updateSecurity'])->name('admin.settings.updateSecurity');
    Route::post('/setari/clear-cache', [AdminSettingsController::class, 'clearCache'])->name('admin.settings.clearCache');
    Route::put('/setari/tenanti/{tenant}', [AdminSettingsController::class, 'updateTenant'])->name('admin.settings.updateTenant');
    Route::patch('/setari/tenanti/{tenant}/toggle', [AdminSettingsController::class, 'toggleTenant'])->name('admin.settings.toggleTenant');

    // Plans CRUD
    Route::resource('pachete', \App\Http\Controllers\Admin\AdminPlanController::class)->names('admin.plans');

    // Model Pricing CRUD
    Route::get('/preturi-modele', [\App\Http\Controllers\Admin\AdminModelPricingController::class, 'index'])->name('admin.model-pricing.index');
    Route::post('/preturi-modele', [\App\Http\Controllers\Admin\AdminModelPricingController::class, 'store'])->name('admin.model-pricing.store');
    Route::put('/preturi-modele/{pricing}', [\App\Http\Controllers\Admin\AdminModelPricingController::class, 'update'])->name('admin.model-pricing.update');
    Route::delete('/preturi-modele/{pricing}', [\App\Http\Controllers\Admin\AdminModelPricingController::class, 'destroy'])->name('admin.model-pricing.destroy');

    // Prompt Versions (A/B Testing)
    Route::get('/boti/{botId}/prompt-versions', [\App\Http\Controllers\Admin\AdminPromptVersionController::class, 'index'])->name('admin.prompt-versions.index');
    Route::post('/boti/{botId}/prompt-versions', [\App\Http\Controllers\Admin\AdminPromptVersionController::class, 'store'])->name('admin.prompt-versions.store');
    Route::put('/prompt-versions/{version}', [\App\Http\Controllers\Admin\AdminPromptVersionController::class, 'update'])->name('admin.prompt-versions.update');
    Route::delete('/prompt-versions/{version}', [\App\Http\Controllers\Admin\AdminPromptVersionController::class, 'destroy'])->name('admin.prompt-versions.destroy');

    // Reports
    Route::get('rapoarte', [AdminReportController::class, 'index'])->name('admin.reports.index');
    Route::post('rapoarte/sample-qa', [AdminReportController::class, 'sampleQA'])->name('admin.reports.sample-qa');

    // Costs & Profitability (daily rollup viewer + ad-hoc re-aggregate)
    Route::get('costuri', [\App\Http\Controllers\Admin\AdminCostReportController::class, 'index'])->name('admin.costs.index');
    Route::post('costuri/reaggregate', [\App\Http\Controllers\Admin\AdminCostReportController::class, 'reaggregate'])->name('admin.costs.reaggregate');
    // Setup-AI spend limit per tenant. Enforcement (hard-stop vs
    // warn-only) is follow-up work — this endpoint only persists
    // the threshold so ops can set it up-front.
    Route::post('costuri/setup-ai/limita/{tenantId}', [\App\Http\Controllers\Admin\AdminCostReportController::class, 'setSetupAiLimit'])->name('admin.costs.setupAiLimit');

    // Niche-demo funnel (Iteration F) — per-niche breakdown of the demo
    // → signup pipeline driven by the public landing pages.
    Route::get('demo', [\App\Http\Controllers\Admin\AdminDemoFunnelController::class, 'index'])->name('admin.demo.index');

    // P5.4: chip conversion analytics — CTR per label × page_type.
    Route::get('analytics/chips', [\App\Http\Controllers\Admin\AdminChipAnalyticsController::class, 'index'])
        ->name('admin.analytics.chips');

    // Outcomes (what the agent earned — mirrors /costuri)
    Route::get('venituri', [\App\Http\Controllers\Admin\AdminOutcomeReportController::class, 'index'])->name('admin.outcomes.index');

    // SaaS lead inbox (contact form + niche landing unified)
    Route::get('lead-uri', [\App\Http\Controllers\Admin\AdminLeadController::class, 'index'])->name('admin.leads.index');
    Route::get('lead-uri/{lead}', [\App\Http\Controllers\Admin\AdminLeadController::class, 'show'])->name('admin.leads.show');
    Route::post('lead-uri/{lead}/status', [\App\Http\Controllers\Admin\AdminLeadController::class, 'updateStatus'])->name('admin.leads.updateStatus');
    Route::post('lead-uri/{lead}/reply', [\App\Http\Controllers\Admin\AdminLeadController::class, 'reply'])->name('admin.leads.reply');

    // Marketing & Analytics platform settings
    Route::put('/setari/marketing', [AdminSettingsController::class, 'updateMarketing'])->name('admin.settings.updateMarketing');
    // Onboarding rollout switch (setup-wow vs legacy setup)
    Route::put('/setari/onboarding', [AdminSettingsController::class, 'updateOnboarding'])->name('admin.settings.updateOnboarding');

    // System Health
    Route::get('/system', [\App\Http\Controllers\Admin\AdminSystemController::class, 'index'])->name('admin.system.index');
    Route::post('/system/retry-job/{jobId}', [\App\Http\Controllers\Admin\AdminSystemController::class, 'retryJob'])->name('admin.system.retryJob');
    Route::post('/system/retry-all', [\App\Http\Controllers\Admin\AdminSystemController::class, 'retryAllJobs'])->name('admin.system.retryAll');
    Route::post('/system/clear-failed', [\App\Http\Controllers\Admin\AdminSystemController::class, 'clearFailedJobs'])->name('admin.system.clearFailed');
    Route::post('/system/reprocess-kb', [\App\Http\Controllers\Admin\AdminSystemController::class, 'reprocessFailedKnowledge'])->name('admin.system.reprocessKb');
    Route::post('/system/clear-caches', [\App\Http\Controllers\Admin\AdminSystemController::class, 'clearAllCaches'])->name('admin.system.clearCaches');

    // Niches CRUD
    Route::resource('niches', \App\Http\Controllers\Admin\AdminNicheController::class)->except(['show'])->names('admin.niches');
    Route::post('niches/{niche}/toggle', [\App\Http\Controllers\Admin\AdminNicheController::class, 'toggle'])->name('admin.niches.toggle');
    Route::post('niches/reorder', [\App\Http\Controllers\Admin\AdminNicheController::class, 'reorder'])->name('admin.niches.reorder');

    // Social Media Management
    Route::prefix('social')->name('admin.social.')->group(function () {
        Route::get('/', [AdminSocialController::class, 'index'])->name('index');
        Route::get('/review', [AdminSocialController::class, 'review'])->name('review');
        Route::post('/generate', [AdminSocialController::class, 'generate'])->name('generate');
        Route::get('/post/{post}', [AdminSocialController::class, 'show'])->name('show');
        Route::get('/post/{post}/edit', [AdminSocialController::class, 'edit'])->name('edit');
        Route::patch('/post/{post}', [AdminSocialController::class, 'patch'])->name('patch');
        Route::put('/post/{post}', [AdminSocialController::class, 'update'])->name('update');
        Route::post('/post/{post}/publish', [AdminSocialController::class, 'publish'])->name('publish');
        Route::post('/post/{post}/approve', [AdminSocialController::class, 'approve'])->name('approve');
        Route::post('/post/{post}/duplicate', [AdminSocialController::class, 'duplicate'])->name('duplicate');
        Route::post('/post/{post}/regenerate-image', [AdminSocialController::class, 'regenerateImage'])->name('regenerateImage');
        Route::post('/post/{post}/regenerate-text', [AdminSocialController::class, 'regenerateText'])->name('regenerateText');
        Route::post('/post/{post}/reject', [AdminSocialController::class, 'reject'])->name('reject');
        Route::post('/post/{post}/variant/{variant}/use', [AdminSocialController::class, 'useVariant'])->name('useVariant');
        Route::delete('/post/{post}', [AdminSocialController::class, 'destroy'])->name('destroy');
        Route::post('/post/{id}/restore', [AdminSocialController::class, 'restore'])->name('restore');
        Route::post('/bulk', [AdminSocialController::class, 'bulk'])->name('bulk');
        Route::post('/maintenance', [AdminSocialController::class, 'maintenance'])->name('maintenance');
        // Token-auth variant (outside super_admin middleware inheritance) for CI / remote ops.
        Route::post('/maintenance-api', [AdminSocialController::class, 'maintenance'])
            ->withoutMiddleware(['auth', 'super_admin'])
            ->name('maintenance.api');
        Route::post('/generate-bio', [AdminSocialController::class, 'generateBio'])->name('generateBio');
        Route::get('/style', [AdminSocialController::class, 'styleTraining'])->name('style');
        Route::post('/style/add', [AdminSocialController::class, 'addStyleExample'])->name('style.add');
        Route::post('/style/{preference}/review', [AdminSocialController::class, 'reviewStyle'])->name('style.review');
        Route::get('/accounts', [AdminSocialController::class, 'accounts'])->name('accounts');
        Route::post('/accounts', [AdminSocialController::class, 'saveAccount'])->name('accounts.save');
        Route::post('/apikeys', [AdminSocialController::class, 'saveApiKeys'])->name('apikeys.save');
        Route::get('/schedule', [AdminSocialController::class, 'schedule'])->name('schedule');
        Route::post('/schedule', [AdminSocialController::class, 'saveSchedule'])->name('schedule.save');
    });
});

// WhatsApp webhooks
Route::prefix('webhook/whatsapp')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->group(function () {
        Route::get('/', [WhatsAppWebhookController::class, 'verify'])->name('webhook.whatsapp.verify');
        Route::post('/', [WhatsAppWebhookController::class, 'handle'])->name('webhook.whatsapp.handle')
            ->middleware(\App\Http\Middleware\VerifyMetaWebhookSignature::class);
    });

// Facebook Messenger webhooks
Route::prefix('webhook/facebook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->group(function () {
        Route::get('/', [FacebookWebhookController::class, 'verify'])->name('webhook.facebook.verify');
        Route::post('/', [FacebookWebhookController::class, 'handle'])->name('webhook.facebook.handle')
            ->middleware(\App\Http\Middleware\VerifyMetaWebhookSignature::class);
    });

// Instagram DM webhooks
Route::prefix('webhook/instagram')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->group(function () {
        Route::get('/', [InstagramWebhookController::class, 'verify'])->name('webhook.instagram.verify');
        Route::post('/', [InstagramWebhookController::class, 'handle'])->name('webhook.instagram.handle')
            ->middleware(\App\Http\Middleware\VerifyMetaWebhookSignature::class);
    });

// Meta data-deletion callback (mandatory for App Review). Meta POSTs a
// signed_request when a Facebook/Instagram user revokes the Sambla app
// from their FB Settings → Apps and Websites. Signature verified inside
// the controller using META_APP_SECRET, so no shared middleware path.
// CSRF off (Meta doesn't speak Laravel session); rate limited to soak
// up any rogue probes.
Route::post('/webhook/meta/data-deletion', [\App\Http\Controllers\Webhook\MetaDataDeletionController::class, 'callback'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('throttle:60,1')
    ->name('webhook.meta.data-deletion');

// OAuth callback Meta hits after the user authorizes. Stays outside
// the auth middleware briefly so the redirect from Meta lands cleanly
// even if the session cookie is borderline; the controller pulls the
// state nonce from cache and re-verifies user_id before doing anything.
Route::get('/oauth/meta/callback', [\App\Http\Controllers\Auth\MetaOAuthController::class, 'callback'])
    ->middleware('auth')
    ->name('oauth.meta.callback');

// Per-bot OAuth init + page-pick attach. Auth + tenant guard inside
// the controller (via Bot route binding which respects BelongsToTenant).
Route::middleware('auth')->prefix('dashboard/agenti/{bot}/canale/meta')->group(function () {
    Route::get('/connect', [\App\Http\Controllers\Auth\MetaOAuthController::class, 'connect'])
        ->name('dashboard.bots.channels.meta.connect');
    Route::post('/attach', [\App\Http\Controllers\Auth\MetaOAuthController::class, 'attach'])
        ->name('dashboard.bots.channels.meta.attach');
});

// Public status page Meta sends the user to after revocation. Users
// (and Meta itself) read this to confirm the deletion completed.
Route::get('/legal/data-deletion', function (\Illuminate\Http\Request $request) {
    $code = (string) $request->query('id', '');
    $status = $code !== ''
        ? app(\App\Http\Controllers\Webhook\MetaDataDeletionController::class)->status($code)
        : null;
    return view('legal.data-deletion', compact('code', 'status'));
})->name('legal.data-deletion');

// Telnyx webhooks (no CSRF, no auth - signature verified by middleware).
// Kept live during the Twilio migration for existing Telnyx numbers.
Route::prefix('webhook/telnyx')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('telnyx.verify')
    ->group(function () {
        Route::post('/voice', [TelnyxWebhookController::class, 'handleVoice'])->name('webhook.telnyx.voice');
        Route::post('/status', [TelnyxWebhookController::class, 'handleStatus'])->name('webhook.telnyx.status');
        Route::post('/number-order', [TelnyxWebhookController::class, 'handleNumberOrder'])->name('webhook.telnyx.numberOrder');
    });

// Twilio webhooks (no CSRF, no auth - signature verified by middleware).
Route::prefix('webhook/twilio')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('twilio.verify')
    ->group(function () {
        Route::post('/voice', [TwilioWebhookController::class, 'handleVoice'])->name('webhook.twilio.voice');
        Route::post('/status', [TwilioWebhookController::class, 'handleStatus'])->name('webhook.twilio.status');

        // Warm-transfer flow. {callSid} is the INBOUND caller leg (not
        // the operator leg) so the conference name stays stable across
        // whisper, join, and status callbacks even though each hits
        // Twilio on different SIDs.
        Route::post('/transfer/whisper/{callSid}', [\App\Http\Controllers\Webhook\TwilioTransferController::class, 'whisper'])->name('webhook.twilio.transfer.whisper');
        Route::post('/transfer/join/{callSid}',    [\App\Http\Controllers\Webhook\TwilioTransferController::class, 'join'])->name('webhook.twilio.transfer.join');
        Route::post('/transfer/no-answer/{callSid}',[\App\Http\Controllers\Webhook\TwilioTransferController::class, 'noAnswer'])->name('webhook.twilio.transfer.noAnswer');
        Route::post('/transfer/status/{callSid}',  [\App\Http\Controllers\Webhook\TwilioTransferController::class, 'status'])->name('webhook.twilio.transfer.status');
    });
