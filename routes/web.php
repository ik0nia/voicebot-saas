<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\BillingController;
use App\Http\Controllers\Dashboard\BotController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\CallController;
use App\Http\Controllers\Dashboard\KnowledgeController;
use App\Http\Controllers\Dashboard\PhoneNumberController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\Dashboard\TeamController;
use App\Http\Controllers\Webhook\TwilioWebhookController;
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
    return view('preturi');
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

// Dashboard home
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'tenant'])->name('dashboard');

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
});

// Calls routes (dashboard)
Route::middleware('auth')->prefix('dashboard/apeluri')->group(function () {
    Route::get('/', [CallController::class, 'index'])->name('dashboard.calls.index');
    Route::get('/{call}', [CallController::class, 'show'])->name('dashboard.calls.show');
    Route::delete('/{call}', [CallController::class, 'destroy'])->name('dashboard.calls.destroy');
    Route::get('/{call}/export/{format?}', [CallController::class, 'exportTranscript'])->name('dashboard.calls.export-transcript');
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

// Knowledge base routes (dashboard)
Route::middleware('auth')->prefix('dashboard/boti/{bot}')->group(function () {
    Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('dashboard.bots.knowledge.index');
    Route::post('/knowledge', [KnowledgeController::class, 'store'])->name('dashboard.bots.knowledge.store');
    Route::delete('/knowledge/{title}', [KnowledgeController::class, 'destroy'])->name('dashboard.bots.knowledge.destroy');
});

// Twilio webhooks (no CSRF, no auth - signature verified by middleware)
Route::prefix('webhook/twilio')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('twilio.verify')
    ->group(function () {
        Route::post('/voice', [TwilioWebhookController::class, 'handleVoice'])->name('webhook.twilio.voice');
        Route::post('/status', [TwilioWebhookController::class, 'handleStatus'])->name('webhook.twilio.status');
    });
