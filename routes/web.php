<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\BillingController;
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
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Webhook\FacebookWebhookController;
use App\Http\Controllers\Webhook\InstagramWebhookController;
use App\Http\Controllers\Webhook\TwilioWebhookController;
use App\Http\Controllers\Webhook\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Landing pages
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

// Chatbot embed routes are in routes/api.php under /chatbot prefix (no auth/session middleware)

// Public demo & test pages (no auth required)
Route::get('/demo/{slug}', [\App\Http\Controllers\PublicDemoController::class, 'show'])->name('public.demo');
Route::get('/dashboard/boti/{bot}/test-vocal', [\App\Http\Controllers\PublicDemoController::class, 'testById'])->name('dashboard.bots.testVocal');

// Setup wizard (onboarding)
Route::middleware('auth')->prefix('dashboard/setup')->group(function () {
    Route::get('/', [\App\Http\Controllers\Dashboard\SetupWizardController::class, 'index'])->name('dashboard.setup.index');
    Route::post('/business-type', [\App\Http\Controllers\Dashboard\SetupWizardController::class, 'storeBusinessType'])->name('dashboard.setup.businessType');
    Route::post('/generate-prompt', [\App\Http\Controllers\Dashboard\SetupWizardController::class, 'generatePrompt'])->name('dashboard.setup.generatePrompt');
    Route::post('/complete', [\App\Http\Controllers\Dashboard\SetupWizardController::class, 'complete'])->name('dashboard.setup.complete');
});

// Dashboard home
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');
Route::post('/dashboard/toggle-admin-view', [DashboardController::class, 'toggleAdminView'])->middleware(['auth'])->name('dashboard.toggleAdminView');

// Billing routes (dashboard)
Route::get('/dashboard/facturare', [BillingController::class, 'index'])->middleware('auth')->name('dashboard.billing.index');

// Bot routes (dashboard)
Route::middleware('auth')->prefix('dashboard/boti')->group(function () {
    Route::get('/', [BotController::class, 'index'])->name('dashboard.bots.index');
    Route::get('/nou', [BotController::class, 'create'])->name('dashboard.bots.create');
    Route::post('/', [BotController::class, 'store'])->name('dashboard.bots.store');
    Route::get('/{bot}', [BotController::class, 'show'])->name('dashboard.bots.show');
    Route::get('/{bot}/editare', [BotController::class, 'edit'])->name('dashboard.bots.edit');
    Route::put('/{bot}', [BotController::class, 'update'])->name('dashboard.bots.update');
    Route::delete('/{bot}', [BotController::class, 'destroy'])->name('dashboard.bots.destroy');
    Route::patch('/{bot}/toggle', [BotController::class, 'toggleActive'])->name('dashboard.bots.toggle');
    Route::patch('/{bot}/update-field', [BotController::class, 'updateField'])->name('dashboard.bots.updateField');
    Route::post('/{bot}/policy', [BotController::class, 'updatePolicy'])->name('dashboard.bots.updatePolicy');

    // Voice cloning
    Route::get('/{bot}/voice-clone', [ClonedVoiceController::class, 'create'])->name('dashboard.bots.voiceClone.create');
    Route::post('/{bot}/voice-clone', [ClonedVoiceController::class, 'store'])->name('dashboard.bots.voiceClone.store');
    Route::post('/{bot}/voice-clone/{clonedVoice}/activate', [ClonedVoiceController::class, 'activate'])->name('dashboard.bots.voiceClone.activate');
    Route::post('/{bot}/voice-clone/deactivate', [ClonedVoiceController::class, 'deactivate'])->name('dashboard.bots.voiceClone.deactivate');
    Route::delete('/{bot}/voice-clone/{clonedVoice}', [ClonedVoiceController::class, 'destroy'])->name('dashboard.bots.voiceClone.destroy');
    Route::get('/{bot}/voice-clone/{clonedVoice}/status', [ClonedVoiceController::class, 'status'])->name('dashboard.bots.voiceClone.status');
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
    Route::delete('/conversatie/{conversation}', [ConversationController::class, 'destroy'])->name('dashboard.conversations.destroy');
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
    Route::post('/', [PhoneNumberController::class, 'store'])->name('dashboard.numbers.store');
    Route::put('/{phoneNumber}', [PhoneNumberController::class, 'update'])->name('dashboard.numbers.update');
    Route::delete('/{phoneNumber}', [PhoneNumberController::class, 'destroy'])->name('dashboard.numbers.destroy');
    Route::patch('/{phoneNumber}/toggle', [PhoneNumberController::class, 'toggleActive'])->name('dashboard.numbers.toggle');
});

// Team routes (dashboard)
Route::middleware('auth')->prefix('dashboard/echipa')->group(function () {
    Route::get('/', [TeamController::class, 'index'])->name('dashboard.team.index');
    Route::post('/invite', [TeamController::class, 'invite'])->name('dashboard.team.invite');
    Route::patch('/{user}/role', [TeamController::class, 'updateRole'])->name('dashboard.team.updateRole');
    Route::delete('/{user}/remove', [TeamController::class, 'remove'])->name('dashboard.team.remove');
});

// Settings routes (dashboard)
Route::middleware('auth')->prefix('dashboard/setari')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('dashboard.settings.index');
    Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('dashboard.settings.updateProfile');
    Route::put('/password', [SettingsController::class, 'updatePassword'])->name('dashboard.settings.updatePassword');
    Route::put('/company', [SettingsController::class, 'updateCompany'])->name('dashboard.settings.updateCompany');
    Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('dashboard.settings.updateNotifications');
    Route::post('/api-keys', [SettingsController::class, 'generateApiKey'])->name('dashboard.settings.generateApiKey');
    Route::delete('/api-keys/{tokenId}', [SettingsController::class, 'revokeApiKey'])->name('dashboard.settings.revokeApiKey');
    Route::delete('/account', [SettingsController::class, 'destroyAccount'])->name('dashboard.settings.destroyAccount');
});

// Channel management routes (dashboard)
Route::middleware('auth')->prefix('dashboard/boti/{bot}/canale')->group(function () {
    Route::get('/', [ChannelController::class, 'index'])->name('dashboard.bots.channels.index');
    Route::post('/', [ChannelController::class, 'store'])->name('dashboard.bots.channels.store');
    Route::put('/{channel}', [ChannelController::class, 'update'])->name('dashboard.bots.channels.update');
    Route::delete('/{channel}', [ChannelController::class, 'destroy'])->name('dashboard.bots.channels.destroy');
    Route::patch('/{channel}/toggle', [ChannelController::class, 'toggleActive'])->name('dashboard.bots.channels.toggle');
});

// Site management routes (dashboard)
Route::middleware('auth')->prefix('dashboard/sites')->group(function () {
    Route::get('/', [SiteController::class, 'index'])->name('dashboard.sites.index');
    Route::get('/new', [SiteController::class, 'create'])->name('dashboard.sites.create');
    Route::post('/', [SiteController::class, 'store'])->name('dashboard.sites.store');
    Route::get('/{site}', [SiteController::class, 'show'])->name('dashboard.sites.show');
    Route::put('/{site}', [SiteController::class, 'update'])->name('dashboard.sites.update');
    Route::delete('/{site}', [SiteController::class, 'destroy'])->name('dashboard.sites.destroy');
    Route::post('/{site}/verify', [SiteController::class, 'verify'])->name('dashboard.sites.verify');
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

// Knowledge base routes (dashboard)
Route::middleware('auth')->prefix('dashboard/boti/{bot}')->group(function () {
    Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('dashboard.bots.knowledge.index');
    Route::delete('/knowledge/{title}', [KnowledgeController::class, 'destroy'])->name('dashboard.bots.knowledge.destroy');

    // Rate-limited mutation routes (10 requests per minute per user)
    Route::middleware('throttle:10,1')->group(function () {
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
    Route::put('/setari/twilio', [AdminSettingsController::class, 'updateTwilio'])->name('admin.settings.updateTwilio');
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

// Twilio webhooks (no CSRF, no auth - signature verified by middleware)
Route::prefix('webhook/twilio')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('twilio.verify')
    ->group(function () {
        Route::post('/voice', [TwilioWebhookController::class, 'handleVoice'])->name('webhook.twilio.voice');
        Route::post('/status', [TwilioWebhookController::class, 'handleStatus'])->name('webhook.twilio.status');
    });
