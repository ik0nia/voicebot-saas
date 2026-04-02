@extends('layouts.admin')

@section('title', 'Audit Platformă')
@section('breadcrumb')<span class="text-slate-900 font-medium">Audit Platformă</span>@endsection

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-900">Audit Platformă</h1>
            <p class="text-sm text-slate-500">Raport complet de audit — algoritmi, securitate, performanță</p>
        </div>
        <div class="ml-auto text-xs text-slate-400">Generat: 24 Martie 2026</div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Critice</span>
            </div>
            <div class="text-2xl font-bold text-red-600">10</div>
            <div class="text-xs text-slate-400 mt-1">Necesită rezolvare imediată</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">High</span>
            </div>
            <div class="text-2xl font-bold text-amber-600">20</div>
            <div class="text-xs text-slate-400 mt-1">Prioritate ridicată</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Medium</span>
            </div>
            <div class="text-2xl font-bold text-blue-600">30</div>
            <div class="text-xs text-slate-400 mt-1">De planificat</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Zone auditate</span>
            </div>
            <div class="text-2xl font-bold text-emerald-600">15</div>
            <div class="text-xs text-slate-400 mt-1">Module analizate</div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div x-data="{ tab: 'critical' }" class="space-y-4">
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex gap-x-1 overflow-x-auto">
                @php
                    $tabs = [
                        'critical' => 'Critice (10)',
                        'high' => 'High Priority (20)',
                        'medium' => 'Medium (30)',
                        'security' => 'Securitate',
                        'performance' => 'Performanță',
                        'voice' => 'Voce & Realtime',
                        'billing' => 'Billing & Costuri',
                        'queue' => 'Queue & Jobs',
                    ];
                @endphp
                @foreach($tabs as $key => $label)
                    <button @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-red-600 text-red-700 bg-red-50/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                            class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- CRITICE --}}
        <div x-show="tab === 'critical'" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-red-50/50">
                    <h2 class="text-base font-semibold text-red-900">Probleme Critice — Rezolvare Imediată</h2>
                    <p class="text-xs text-red-700 mt-1">Aceste probleme afectează securitatea, stabilitatea sau corectitudinea financiară a platformei.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @php
                        $criticals = [
                            [
                                'title' => 'Rate limit bypass prin session_id injection',
                                'zone' => 'ChatbotApiController',
                                'file' => 'app/Http/Controllers/Api/ChatbotApiController.php:63',
                                'desc' => 'Atacatorii pot furniza session_id-uri unice la fiecare request, creând bucket-uri noi de rate limit. Fiecare UUID = 30 req/min suplimentare.',
                                'fix' => 'Combinați session_id cu IP: hash(session_id + IP). Validați UUID format.',
                                'impact' => 'Abuz API, cost explosion pe AI calls',
                            ],
                            [
                                'title' => 'Fără limită pe apeluri concurente per tenant',
                                'zone' => 'Telnyx / RealtimeSession',
                                'file' => 'app/Http/Controllers/Api/RealtimeSessionController.php:22',
                                'desc' => 'Nicio verificare înainte de a crea sesiuni realtime. Un tenant poate crea nelimitat de sesiuni simultane.',
                                'fix' => 'Adăugați canMakeCall() în PlanLimitService. Verificați înainte de creare sesiune.',
                                'impact' => 'Costuri OpenAI nelimitate, resource exhaustion',
                            ],
                            [
                                'title' => 'API key ElevenLabs returnat la frontend',
                                'zone' => 'RealtimeSessionController',
                                'file' => 'app/Http/Controllers/Api/RealtimeSessionController.php:241',
                                'desc' => 'elevenlabs_api_key este trimis în JSON response la frontend. Oricine poate extrage cheia din JavaScript.',
                                'fix' => 'Folosiți backend relay endpoint în loc de a trimite cheia direct.',
                                'impact' => 'Token theft, quota exhaustion ElevenLabs',
                            ],
                            [
                                'title' => 'Channel fără tenant isolation — cross-tenant data leak',
                                'zone' => 'Multi-tenancy',
                                'file' => 'app/Http/Controllers/Dashboard/ConversationController.php:26',
                                'desc' => 'Channel model nu are BelongsToTenant. ConversationController face Channel::where(type) fără tenant filter, expunând conversații din toți tenanții.',
                                'fix' => 'Adăugați tenant_id pe Channel. Filtrați în ConversationController.',
                                'impact' => 'Cross-tenant data leakage',
                            ],
                            [
                                'title' => 'Sanctum tokens fără expirare',
                                'zone' => 'Auth / API',
                                'file' => 'config/sanctum.php:50',
                                'desc' => 'expiration = null. Token-urile API nu expiră niciodată. Un token compromis rămâne valid permanent.',
                                'fix' => 'Setați SANCTUM_EXPIRATION=365 (sau 90 zile).',
                                'impact' => 'Token-uri compromise permanente',
                            ],
                            [
                                'title' => 'ProcessKnowledgeDocument fără timeout',
                                'zone' => 'Queue Jobs',
                                'file' => 'app/Jobs/ProcessKnowledgeDocument.php',
                                'desc' => 'Job-ul nu are $timeout definit. PDF-uri mari pot rula ore întregi, blocând worker-ul.',
                                'fix' => 'Adăugați public int $timeout = 1800; (30 min).',
                                'impact' => 'Workers blocați, queue backlog',
                            ],
                            [
                                'title' => 'Cost tracking trunchiat — pierdere revenue',
                                'zone' => 'Billing',
                                'file' => 'app/Http/Controllers/Api/ChatbotApiController.php:191',
                                'desc' => '(int) round(0.045) = 0. Costuri mici (sub 0.5 cenți) se pierd complet. Schema folosește integer în loc de decimal.',
                                'fix' => 'Migrare: schimbați cost_cents de la integer la decimal(10,4). Eliminați (int) cast.',
                                'impact' => 'Revenue loss pe mesaje chat',
                            ],
                            [
                                'title' => 'Messages table fără niciun index',
                                'zone' => 'Database',
                                'file' => 'database/migrations/2026_03_20_110200_create_messages_table.php',
                                'desc' => 'Tabela messages nu are niciun index definit. Toate query-urile fac full table scan (conversation history, cost aggregation).',
                                'fix' => 'Adăugați indexuri pe conversation_id, direction, created_at.',
                                'impact' => 'Query-uri lente pe toate endpoint-urile chat',
                            ],
                            [
                                'title' => 'handleFunctionCall() lipsă în demo voice',
                                'zone' => 'Frontend',
                                'file' => 'resources/views/public/demo.blade.php:713',
                                'desc' => 'Funcția handleFunctionCall() este apelată dar nu este definită. Voice calls cu function calling (search_products) eșuează silențios.',
                                'fix' => 'Implementați funcția: parse function results, trimiteți response.create la OpenAI.',
                                'impact' => 'Voice product search complet nefuncțional',
                            ],
                            [
                                'title' => 'ChatCompletionService fără error handling pe API calls',
                                'zone' => 'AI Services',
                                'file' => 'app/Services/ChatCompletionService.php:48,92',
                                'desc' => 'callOpenAI() și callAnthropic() nu au try/catch. Orice eroare API (network, rate limit, auth) crashează request-ul.',
                                'fix' => 'Adăugați try/catch cu retry logic și fallback la alt provider.',
                                'impact' => 'Crash-uri negestionate la orice eroare AI',
                            ],
                        ];
                    @endphp
                    @foreach($criticals as $i => $item)
                        <div class="px-5 py-4 hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-700 text-xs font-bold shrink-0">{{ $i + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm font-semibold text-slate-900">{{ $item['title'] }}</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-700">CRITIC</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">{{ $item['zone'] }}</span>
                                    </div>
                                    <p class="text-sm text-slate-600 mt-1">{{ $item['desc'] }}</p>
                                    <div class="mt-2 flex flex-col sm:flex-row sm:items-center gap-2">
                                        <code class="text-xs bg-slate-100 text-slate-700 px-2 py-1 rounded font-mono">{{ $item['file'] }}</code>
                                    </div>
                                    <div class="mt-2 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-emerald-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <p class="text-xs text-emerald-800"><strong>Fix:</strong> {{ $item['fix'] }}</p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-red-600 mt-1"><strong>Impact:</strong> {{ $item['impact'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- HIGH PRIORITY --}}
        <div x-show="tab === 'high'" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-amber-50/50">
                    <h2 class="text-base font-semibold text-amber-900">Prioritate Ridicată — Sprint-ul următor</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @php
                        $highs = [
                            ['Anthropic API calls fără timeout', 'ChatCompletionService', 'Calls pot atârna la infinit. Adăugați ->timeout(30) la factory.'],
                            ['Token-urile din chat nu se trackează în billing', 'PlanLimitService', 'ChatbotApiController salvează tokens în Message dar nu apelează recordTokensUsed(). Revenue leakage.'],
                            ['N+1 masiv pe dashboard (21 queries pt chart)', 'DashboardController', '7 iterații × 3 queries separate. Folosiți single GROUP BY query cu date range.'],
                            ['pg_trgm posibil neînregistrat în migrații', 'Database', 'similarity() necesită extensia pg_trgm. Nu există migrare CREATE EXTENSION.'],
                            ['WooCommerce sync — memory explosion la cleanup', 'WooCommerceConnector', '.get() încarcă TOATE produsele în memorie, apoi filtrează client-side. Folosiți whereNotIn() în SQL.'],
                            ['Knowledge cache fără invalidare la document update', 'KnowledgeSearch', 'Cache 24h pe query embeddings. Dacă documente se actualizează, bot-ul servește date vechi.'],
                            ['OrderLookup fără cache', 'OrderLookupService', 'Fiecare lookup = HTTP request la WooCommerce API. Adăugați Cache::remember() cu 5-10 min TTL.'],
                            ['Autoscaler ignoră queue-urile crawling și agents', 'QueueAutoScale', 'Monitorizează doar high,default. Crawling și agents nu se auto-scalează.'],
                            ['Workers autoscalați cu 128MB', 'QueueAutoScale', 'Insuficient pentru PDF-uri mari. ProcessKnowledgeDocument încarcă fișiere întregi în memorie.'],
                            ['RRF weights inversate (FTS > vector)', 'KnowledgeSearchService', 'FTS are weight 1.5, vector doar 1.0. Pentru semantic search, vector ar trebui >= FTS.'],
                            ['Race condition: duplicate calls pe webhook retry', 'TelnyxWebhookController', 'Telnyx poate trimite webhook-ul de mai multe ori. Nu se verifică dacă call-ul există deja.'],
                            ['Missing status callback events', 'TelnyxService', 'Lipsesc busy, no-answer, canceled, failed din statusCallbackEvent array.'],
                            ['Outbound calls fără rate limiting', 'CallApiController', 'API endpoint fără throttle. Un user poate spamma apeluri outbound.'],
                            ['Transcript not encrypted at rest', 'Transcript model', 'Conținutul transcrierii stocat plaintext. GDPR necesită criptare.'],
                            ['No call cleanup for stale sessions', 'RealtimeSession', 'Sesiuni in_progress care nu se mai termină rămân în DB. Niciun job de cleanup.'],
                            ['Instruction injection via knowledge base', 'RealtimeSessionController', 'Conținut uploadat de user injectat direct în system prompt. Posibil jailbreak.'],
                            ['Exception details leaked to client', 'CallApiController:86', 'Returnează raw exception message care poate conține detalii interne.'],
                            ['ElevenLabs WebSocket fără reconnection', 'demo.blade.php', 'Dacă WebSocket-ul se închide, TTS se oprește complet fără retry.'],
                            ['Product cards fără null safety', 'frame.blade.php', 'p.name, p.price, p.currency pot fi undefined → afișează "undefined" în chat.'],
                            ['postMessage cu origin "*"', 'frame.blade.php:141', 'Cart notifications trimise fără origin check. Orice site poate intercepta.'],
                        ];
                    @endphp
                    @foreach($highs as $i => $item)
                        <div class="px-5 py-3 hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-700 text-xs font-bold shrink-0">{{ $i + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm font-medium text-slate-900">{{ $item[0] }}</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700">HIGH</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">{{ $item[1] }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">{{ $item[2] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- MEDIUM --}}
        <div x-show="tab === 'medium'" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-blue-50/50">
                    <h2 class="text-base font-semibold text-blue-900">Prioritate Medie — De planificat</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @php
                        $mediums = [
                            ['Similarity threshold prea mic (0.35)', 'KnowledgeSearch', 'Pentru text-embedding-3-small, 0.35 e scăzut. Crește la 0.50 pentru rezultate mai relevante.'],
                            ['Stopwords list incompletă', 'ProductSearchService', 'Lipsesc: "pana", "spre", "sub", "peste", "are", "e", "iar", "inca", "deci". "bun"/"buna" nu ar trebui să fie stopwords.'],
                            ['Session.update prea frecvent în voice', 'RealtimeSession', 'Se trimite la fiecare transcriere. Adăugați throttle de 3 secunde între update-uri.'],
                            ['Context stale în voice', 'RealtimeSession', 'conversationContext nu se curăță. Produsele de la query-ul anterior rămân în context.'],
                            ['Hardcoded call costs (20¢/min)', 'TelnyxWebhookController', 'Cost fix fără legătură cu costurile reale Telnyx/OpenAI. Nicio configurabilitate.'],
                            ['Sentiment analysis truncheaza la 4000 chars', 'AnalyzeCallSentiment', 'Păstrează doar ultimele 4000 caractere. Pierde contextul de la începutul apelului.'],
                            ['Zero ShouldBeUnique pe jobs', 'Toate job-urile', 'Niciun job nu implementează ShouldBeUnique. Dispatch dublu = procesare dublă.'],
                            ['content_hash niciodată populat', 'ProcessKnowledgeDocument', 'Coloana există dar nu e setată. Re-embedding inutil la re-upload document identic.'],
                            ['tokens_count niciodată actualizat', 'ProcessKnowledgeDocument', 'Coloana există dar nu se populează. Nu se pot trackui costurile per document.'],
                            ['Text search config "simple" în loc de romanian', 'KnowledgeSearchService', 'Nu face stemming românesc. "roboți" ≠ "robot", "companie" ≠ "companii".'],
                            ['Parameter binding repetitiv (5x query_or)', 'KnowledgeSearchService', 'Același parametru legat de 5 ori. Consolidare posibilă.'],
                            ['Scoring formula — category weight prea mic (0.5)', 'ProductSearchService', 'Categoria contează prea puțin (0.5 din max 4.5). Creșteți la 1.0.'],
                            ['Single-char word expansions', 'ProductSearchService', 'cm11 → expandează la "c", "m", "1" care matchează fals. Minim 2 caractere.'],
                            ['Phone lookup scanează doar 20 comenzi', 'OrderLookupService', 'Per_page:20 client-side filter. Comanda #100 nu va fi găsită.'],
                            ['Missing detection patterns (retur, factură, plată)', 'OrderLookupService', 'detectOrderQuery nu recunoaște "când vine", "retur", "factură", "plată".'],
                            ['Follow-up detection case-sensitive', 'ChatbotApiController', 'str_contains() nu e case-insensitive. "Emailul" vs "emailul" — doar lowercase matchează.'],
                            ['Stock status — doar boolean, fără cantitate', 'WooCommerceConnector', 'Bot-ul spune "în stoc" dar nu poate spune "5 bucăți disponibile".'],
                            ['Price formatting redundant', 'WooCommerceConnector', 'Afișează price + regular_price + sale_price. Confuz pentru embeddings.'],
                            ['No data retention policy', 'Analytics', 'Apeluri, transcrieri, mesaje — stocate permanent. Nicio politică de curățare.'],
                            ['Month boundary bug în billing', 'BillingController', 'whereMonth/whereYear include date incorect la schimbarea lunii. Folosiți whereBetween.'],
                            ['Chat model routing — hardcoded', 'ChatModelRouter', 'Modele și praguri fixe. Nu se pot configura per bot/tenant.'],
                            ['No streaming support', 'ChatCompletionService', 'Răspunsuri non-streaming. Userii așteaptă generarea completă.'],
                            ['Orphaned conversations (no TTL)', 'ChatbotApiController', 'Conversații active fără al doilea mesaj rămân în DB permanent.'],
                            ['Missing composite indexes pe calls', 'Database', 'Lipsesc (tenant_id, created_at), (bot_id, created_at) — analytics queries lente.'],
                            ['Missing indexes pe conversations', 'Database', 'Lipsesc (channel_id, status), (tenant_id, created_at).'],
                            ['Scheduler loop — orphaned processes', 'docker-compose.yml', 'Shell loop spawns background process la fiecare 60s fără wait.'],
                            ['VerifySite — doar 1 try', 'VerifySite job', 'DNS propagation poate dura. Creșteți la 3 tries cu backoff.'],
                            ['No token scopes pe API keys', 'SettingsController', 'Token-uri create cu wildcard scope (*). Nicio permisiune granulară.'],
                            ['Mobile not responsive pe demo page', 'demo.blade.php', 'Simulatorul telefon e fix 550px height. Nu se adaptează pe mobile.'],
                            ['Sentiment word matching (substring)', 'demo.blade.php', '"nu sunt mulțumit" matchează "mulțumit" → detectat pozitiv fals.'],
                        ];
                    @endphp
                    @foreach($mediums as $i => $item)
                        <div class="px-5 py-2.5 hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold shrink-0">{{ $i + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm font-medium text-slate-800">{{ $item[0] }}</h3>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700">MED</span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500">{{ $item[1] }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $item[2] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- SECURITY --}}
        <div x-show="tab === 'security'" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Securitate & Multi-Tenancy</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-red-900 mb-2">Modele fără tenant isolation</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                            @foreach(['Channel', 'BotKnowledge', 'WooCommerceProduct', 'KnowledgeConnector', 'Message', 'Transcript', 'CallEvent', 'WebsiteScan', 'WebsiteScanPage', 'KnowledgeAgentRun'] as $model)
                                <div class="flex items-center gap-1.5 text-xs text-red-800 bg-red-100 rounded px-2 py-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    {{ $model }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-amber-900 mb-2">withoutGlobalScopes() usage — 21 locații</h3>
                        <p class="text-xs text-amber-800">Multe controllere publice (ChatbotApiController, RealtimeSessionController, PublicDemoController) și webhook-uri folosesc withoutGlobalScopes() fără validare explicită de tenant.</p>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-emerald-900 mb-2">Corect implementate</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @foreach(['Bot', 'Call', 'Conversation', 'PhoneNumber', 'Site', 'ClonedVoice', 'UsageRecord', 'UsageTracking'] as $model)
                                <div class="flex items-center gap-1.5 text-xs text-emerald-800 bg-emerald-100 rounded px-2 py-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    {{ $model }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PERFORMANCE --}}
        <div x-show="tab === 'performance'" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Performanță Database</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-red-900 mb-2">Indexuri lipsă (critice)</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead><tr class="text-left text-red-700">
                                    <th class="pb-2 font-medium">Tabelă</th>
                                    <th class="pb-2 font-medium">Index recomandat</th>
                                    <th class="pb-2 font-medium">Impact</th>
                                </tr></thead>
                                <tbody class="text-red-800">
                                    <tr><td class="py-1 font-mono">messages</td><td>conversation_id, direction, created_at</td><td>Full table scan pe orice query chat</td></tr>
                                    <tr><td class="py-1 font-mono">calls</td><td>(tenant_id, created_at), (bot_id, created_at)</td><td>Analytics queries lente</td></tr>
                                    <tr><td class="py-1 font-mono">conversations</td><td>(channel_id, status), (tenant_id, created_at)</td><td>Dashboard filtering lent</td></tr>
                                    <tr><td class="py-1 font-mono">woocommerce_products</td><td>name GIN trigram, (bot_id, stock_status)</td><td>Product search sequential scan</td></tr>
                                    <tr><td class="py-1 font-mono">transcripts</td><td>timestamp_ms</td><td>Ordering transcripts lent</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-amber-900 mb-2">N+1 Query Patterns</h3>
                        <ul class="text-xs text-amber-800 space-y-1">
                            <li>Dashboard super-admin chart: <strong>21 queries</strong> pentru 7 zile (7 × 3 queries separate)</li>
                            <li>AdminBotController knowledge stats: <strong>9 queries</strong> per bot view</li>
                            <li>DashboardController tenant stats: <strong>8+ queries</strong> cu clone() repetitiv</li>
                        </ul>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-emerald-900 mb-2">Bine implementate</h3>
                        <ul class="text-xs text-emerald-800 space-y-1">
                            <li>HNSW index pe bot_knowledge embedding — corect configurat (m=16, ef_construction=128)</li>
                            <li>GIN FTS index pe content — funcțional</li>
                            <li>Composite indexes pe bot_knowledge — comprehensive</li>
                            <li>SQL parameterizat — nicio vulnerabilitate SQL injection găsită</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- VOICE & REALTIME --}}
        <div x-show="tab === 'voice'" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Voice Bot & Realtime API</h2>
                </div>
                <div class="p-5 space-y-4">
                    @php
                        $voiceIssues = [
                            ['Session.update pe fiecare transcriere', 'MEDIUM', 'Cauzează lag — OpenAI re-procesează instrucțiunile. Throttle la 3s.'],
                            ['Context stale (nu se resetează)', 'HIGH', 'Produse din query-ul anterior rămân în context. Bot recomandă produse greșite.'],
                            ['Knowledge search sincron (blocking)', 'MEDIUM', 'Embedding API call (500ms-2s) blochează răspunsul vocal.'],
                            ['Race condition: transcription vs response', 'HIGH', 'Session.update poate ajunge DUPĂ ce OpenAI a început deja răspunsul.'],
                            ['Prompt length unbounded', 'MEDIUM', 'Instrucțiunile cresc la 3000+ chars. Mai lung = răspuns mai lent.'],
                            ['No call timeout', 'CRITICAL', 'Apelurile pot dura nelimitat. Costuri OpenAI Realtime nelimitate.'],
                            ['No reconnection logic', 'HIGH', 'Dacă WebSocket-ul cade, apelul moare fără retry.'],
                            ['TTS ElevenLabs fără fallback', 'MEDIUM', 'Dacă ElevenLabs eșuează, nu face fallback la OpenAI native TTS.'],
                            ['transcriptBuffer declarat dar nefolosit', 'LOW', 'Code mort — array declarat dar niciodată populat.'],
                            ['hasProducts verificat în constructor (fix aplicat)', 'FIXED', 'Cache-uit în constructor, eliminat verificări repetitive.'],
                        ];
                    @endphp
                    @foreach($voiceIssues as $item)
                        <div class="flex items-start gap-3">
                            @php
                                $colors = ['CRITICAL' => 'bg-red-100 text-red-700', 'HIGH' => 'bg-amber-100 text-amber-700', 'MEDIUM' => 'bg-blue-100 text-blue-700', 'LOW' => 'bg-slate-100 text-slate-600', 'FIXED' => 'bg-emerald-100 text-emerald-700'];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $colors[$item[1]] }} shrink-0 mt-0.5">{{ $item[1] }}</span>
                            <div>
                                <h4 class="text-sm font-medium text-slate-800">{{ $item[0] }}</h4>
                                <p class="text-xs text-slate-500">{{ $item[2] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- BILLING --}}
        <div x-show="tab === 'billing'" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Billing & Cost Tracking</h2>
                </div>
                <div class="p-5 space-y-4">
                    @php
                        $billingIssues = [
                            ['Costuri apeluri hardcodate (20¢/min)', 'CRITICAL', 'Nu reflectă costurile reale. ElevenLabs adaugă doar 7¢. Nu se pot actualiza fără deploy.'],
                            ['Cost tracking trunchiat la integer', 'CRITICAL', 'round(0.045) → 0. Mesaje ieftine = cost 0. Schema trebuie decimal(10,4).'],
                            ['Chat tokens nu se trackează în PlanLimit', 'HIGH', 'recordTokensUsed() nu e apelat din ChatbotApiController. Nicio limită pe chat.'],
                            ['Pricing hardcodat în ChatCompletionService', 'MEDIUM', 'Prețuri modele fixe în cod. Nu se pot actualiza fără deploy.'],
                            ['Month boundary bug', 'MEDIUM', 'whereMonth/whereYear include date incorect la tranziție. Folosiți whereBetween.'],
                            ['Phone number monthly costs netrakuite', 'LOW', 'monthly_cost_cents pe PhoneNumber dar niciodată facturat.'],
                            ['Nicio alertă la 80%/100% usage', 'HIGH', 'Tenanții nu sunt avertizați când se apropie de limită.'],
                            ['ceil() pe cost calculation', 'LOW', 'Rotunjire în sus — 61 secunde = 2 minute facturate.'],
                        ];
                    @endphp
                    @foreach($billingIssues as $item)
                        @php
                            $colors = ['CRITICAL' => 'bg-red-100 text-red-700', 'HIGH' => 'bg-amber-100 text-amber-700', 'MEDIUM' => 'bg-blue-100 text-blue-700', 'LOW' => 'bg-slate-100 text-slate-600'];
                        @endphp
                        <div class="flex items-start gap-3 py-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $colors[$item[1]] }} shrink-0 mt-0.5">{{ $item[1] }}</span>
                            <div>
                                <h4 class="text-sm font-medium text-slate-800">{{ $item[0] }}</h4>
                                <p class="text-xs text-slate-500">{{ $item[2] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- QUEUE & JOBS --}}
        <div x-show="tab === 'queue'" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Queue Jobs & Scheduling</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead><tr class="text-left border-b border-slate-200">
                                <th class="pb-2 font-medium text-slate-500">Job</th>
                                <th class="pb-2 font-medium text-slate-500">Queue</th>
                                <th class="pb-2 font-medium text-slate-500">Tries</th>
                                <th class="pb-2 font-medium text-slate-500">Timeout</th>
                                <th class="pb-2 font-medium text-slate-500">Unique</th>
                                <th class="pb-2 font-medium text-slate-500">Probleme</th>
                            </tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr><td class="py-2 font-mono text-slate-800">ProcessKnowledgeDocument</td><td>default</td><td>3</td><td class="text-red-600 font-bold">NONE</td><td class="text-red-600">NO</td><td class="text-red-600">Fără timeout, poate rula la infinit</td></tr>
                                <tr><td class="py-2 font-mono text-slate-800">CrawlWebsite</td><td>crawling</td><td>2</td><td>600s</td><td class="text-red-600">NO</td><td class="text-amber-600">Timeout prea scurt pt site-uri mari</td></tr>
                                <tr><td class="py-2 font-mono text-slate-800">RunKnowledgeAgent</td><td>agents</td><td>3</td><td class="text-red-600 font-bold">NONE</td><td class="text-red-600">NO</td><td class="text-red-600">Fără timeout</td></tr>
                                <tr><td class="py-2 font-mono text-slate-800">SyncConnector</td><td>default</td><td>2</td><td>300s</td><td class="text-red-600">NO</td><td class="text-amber-600">5 min insuficient pt cataloage mari</td></tr>
                                <tr><td class="py-2 font-mono text-slate-800">AnalyzeCallSentiment</td><td>default</td><td>2</td><td>30s</td><td class="text-red-600">NO</td><td class="text-amber-600">Fără failed() handler</td></tr>
                                <tr><td class="py-2 font-mono text-slate-800">ProcessVoiceCloning</td><td>default</td><td>2</td><td>180s</td><td class="text-red-600">NO</td><td class="text-slate-500">OK</td></tr>
                                <tr><td class="py-2 font-mono text-slate-800">VerifySite</td><td>default</td><td>1</td><td>30s</td><td class="text-red-600">NO</td><td class="text-amber-600">Doar 1 try, DNS needs retry</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mt-4">
                        <h3 class="text-sm font-semibold text-amber-900 mb-1">Worker Memory</h3>
                        <p class="text-xs text-amber-800">Autoscaled workers: <strong>128MB</strong> — insuficient. ProcessKnowledgeDocument poate încărca PDF-uri de 50MB+ în memorie. Creșteți la 512MB.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush

@push('styles')
<style>[x-cloak] { display: none !important; }</style>
@endpush
