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

// Landing pages
// Public landing pages — wrapped in PublicPageCache so anonymous
// visitors get a 5-minute browser cache instead of Laravel's default
// no-cache. Authenticated users skip the cache automatically.
Route::middleware(\App\Http\Middleware\PublicPageCache::class)->group(function () {
    Route::get('/', function () {
        return view('home');
    });

    Route::get('/functionalitati', function () {
        return view('functionalitati');
    });

    Route::get('/preturi', function () {
        try {
            $webchatPlans = \App\Models\Plan::active()->webchat()->orderBy('sort_order')->get();
            $voicePlans = \App\Models\Plan::active()->voice()->orderBy('sort_order')->get();
        } catch (\Exception $e) {
            $webchatPlans = collect();
            $voicePlans = collect();
        }
        return view('preturi', compact('webchatPlans', 'voicePlans'));
    });

    Route::get('/despre', function () {
        return view('despre');
    });

    Route::get('/blog', function () {
        return view('blog');
    });

    Route::get('/contact', function () {
        return view('contact');
    });

    Route::view('/de-ce-sambla', 'de-ce-sambla')->name('public.deCeSambla');

    // Legal pages (GDPR compliance)
    Route::view('/termeni', 'legal.termeni')->name('legal.termeni');
    Route::view('/confidentialitate', 'legal.confidentialitate')->name('legal.confidentialitate');
    Route::view('/cookie-uri', 'legal.cookie-uri')->name('legal.cookie-uri');

    // Niche landing pages (public, cached)
    Route::get('/pentru/{niche:slug}', function (\App\Models\Niche $niche) {
        abort_unless($niche->is_active, 404);
        return view('landing.niche', [
            'niche' => $niche,
            'theme' => \App\Support\NicheTheme::get($niche->color_theme),
        ]);
    })->name('public.niche');
});

// Dynamic sitemap — includes all public pages + active niche landing pages.
Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => '/',                  'changefreq' => 'weekly',  'priority' => '1.0'],
        ['loc' => '/de-ce-sambla',      'changefreq' => 'weekly',  'priority' => '0.95'],
        ['loc' => '/functionalitati',   'changefreq' => 'weekly',  'priority' => '0.9'],
        ['loc' => '/preturi',           'changefreq' => 'weekly',  'priority' => '0.9'],
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

// Niche lead capture (POST — outside PublicPageCache so CSRF + flash work normally)
Route::post('/pentru/{niche:slug}/lead', [\App\Http\Controllers\NicheLandingController::class, 'storeLead'])
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
    Route::middleware('tenant.role:tenant_admin,tenant_manager')->group(function () {
        Route::post('/', [ChannelController::class, 'store'])->name('dashboard.bots.channels.store');
        Route::put('/{channel}', [ChannelController::class, 'update'])->name('dashboard.bots.channels.update');
        Route::delete('/{channel}', [ChannelController::class, 'destroy'])->name('dashboard.bots.channels.destroy');
        Route::patch('/{channel}/toggle', [ChannelController::class, 'toggleActive'])->name('dashboard.bots.channels.toggle');
    });
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
    });
