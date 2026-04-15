<?php

namespace App\Console\Commands;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\SocialPostGroup;
use App\Services\Social\GeminiContentService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateDailyBatch extends Command
{
    protected $signature = 'social:generate-batch
                            {count=10 : Number of posts to generate}
                            {--date= : Date YYYY-MM-DD (default today)}
                            {--from=09:00 : Earliest posting time HH:MM (only enforced for future dates)}
                            {--until=20:00 : Schedule posts until this time (HH:MM)}
                            {--platform=both : facebook, instagram, or both}
                            {--category= : Force a specific category (e.g. verticale, tehnologie)}
                            {--drafts : Create posts as drafts with no scheduled_at (for review queue)}
                            {--dry-run : Preview without creating}';

    protected $description = 'Generate a batch of scheduled social media posts with CTA-focused images';

    /**
     * Seed list grouped by CATEGORY. Every batch picks a random category first
     * (so we rotate across themes), then a random seed within it. This forces
     * the feed to cover tech, performance, business value, security, vertical
     * use cases, etc., instead of looping on the same 2-3 angles.
     *
     * Sourced from home.blade.php (real features and tech claims).
     */
    private array $featureSeedsByCategory = [
        'tehnologie' => [
            'RAG Pipeline cu Hybrid Search: combină vector search semantic (1536 dimensiuni) cu full-text search în română (cu stemming) pentru relevanță maximă.',
            'AI Reranking: după hybrid search, un model AI reordonează 20 de candidați și alege cele mai relevante 8 chunks per întrebare.',
            '25 de grupuri de sinonime românești integrate în motorul de căutare — recunoaște «retur», «returnare», «înapoiere» ca același concept.',
            'pgvector în PostgreSQL pentru embeddings: căutare vectorială rapidă, fără infrastructură externă suplimentară.',
            '10 straturi de verificare per răspuns: base prompt, politica conversației, context produse, reguli comenzi, stil, query intelligence, strategia conversației, nivel de confidență, detector de frustrare, anti-halucinare.',
            'Pipeline cu 4 etape: înțelege intenția, caută în baza de cunoștințe, adaptează strategia conversației, răspunde verificat — totul în sub 2 secunde.',
            'Voicebot pe OpenAI Realtime API (GPT-4o voice) cu telefonie Telnyx: latență mică, transcriere live, audio bidirecțional fără lag.',
            'Conversation Strategy adaptivă: începutul conversației înțelege nevoia, mijlocul recomandă, finalul cere lead/escaladare — diferit per etapă.',
            'Query Intelligence: clasifică automat fiecare întrebare ca informațională, tranzacțională sau reclamație și schimbă strategia.',
            'Embeddings + full-text într-o singură interogare hibridă: nu pierzi nici sensul, nici cuvintele exacte.',
            'Chunking inteligent al documentelor: PDF-ul tău e tăiat în bucăți care păstrează contextul, nu pe la jumătatea propoziției.',
            'Vector search în 1536 dimensiuni: fiecare frază din documentele tale devine un punct într-un spațiu matematic în care «similar ca sens» = «aproape ca distanță».',
            'Stemming românesc: motorul recunoaște «factură», «facturi», «facturare», «facturat» ca același cuvânt-rădăcină.',
            'Detector de frustrare bazat pe semnale conversaționale: limbaj negativ, repetare, escaladare în ton — toate trigger pentru transferul la om.',
            'Sub 2 secunde end-to-end pe RAG complet: cum reușim atât de repede (vector cache + filtru pre-rerank + LLM streaming).',
            'Anti-halucinare pe nivel de confidență: dacă scor < threshold, agentul AI spune «nu am informația asta» în loc să inventeze.',
            'Audio bidirecțional pe WebSocket: ce se întâmplă la nivel de pachete între telefonul clientului și GPT-4o voice.',
            'Tenant isolation cu row-level security: cum garantăm că datele unui cont nu pot fi văzute de altul, nici accidental.',
            'Embeddings caching: aceeași întrebare pusă de două ori = un singur apel la modelul de embedding (economie + viteză).',
            'Cum funcționează un agent vocal AI pas cu pas: recunoaștere voce → text → înțelegere intenție → căutare KB → generare răspuns → text-to-speech → audio.',
            'Diferența dintre un agent AI clasic și unul cu RAG: primul memorează scenarii, al doilea citește documentele tale în timp real.',
            'Ce e un embedding și de ce contează: explicat simplu, fără matematică — cum AI-ul «înțelege» sensul cuvintelor.',
            'De ce hybrid search bate pure vector search: vectorii pierd cuvintele rare, full-text le găsește.',
            'Reranking explicat: de ce a doua trecere AI peste rezultate produce relevanță radical mai bună decât doar prima căutare.',
            'Cum protejăm contextul în conversațiile lungi: window de 16k tokens + summarizare progresivă a istoricului.',
            'Pipeline-ul nostru de ingestion: PDF → OCR (dacă e nevoie) → chunking → embedding → indexare în pgvector. Tot fluxul.',
            'De ce am ales PostgreSQL + pgvector în loc de o bază vectorială dedicată (Pinecone, Weaviate): un singur sistem, ACID, joins.',
            'Cum tratăm întrebări ambigue: agentul AI cere clarificare în loc să ghicească — o decizie de design conștientă.',
            'OpenAI GPT-4o vs alternative: de ce am ales-o pentru agentul vocal (latență, calitate voce română, suport conversație continuă).',
            'Telnyx ca furnizor de telefonie: numere RO native, SIP la nivel de carrier, webhook-uri în timp real pentru evenimentele de apel.',
        ],
        'tehnologie_explicativ' => [
            'Cum funcționează un RAG (Retrieval-Augmented Generation) la nivel conceptual: explicat în 30 de secunde fără jargon.',
            'Ce înseamnă «vector search semantic»: AI-ul transformă fiecare frază într-un șir de numere care reprezintă SENSUL, nu cuvintele.',
            'De ce un agent AI bun are nevoie de mai mult decât un LLM: arhitectura completă (LLM + vector DB + reranker + guards).',
            'Cum decide AI-ul când să escaladeze la un om: scor de confidență + clasificator de frustrare + reguli de business.',
            'Ce sunt embeddings și de ce sunt fundamentale pentru un AI care «citește» documente.',
            'Diferența dintre fine-tuning și RAG: cele două abordări de a-i da AI-ului cunoștințe noi, comparate.',
            'Cum măsoară un AI cât de relevant e un răspuns: cosine similarity, BM25, cross-encoder reranker — pe înțelesul tuturor.',
            'De ce un AI bine construit nu halucinează: arhitectura care îl ține ancorat în datele tale.',
            'Cum funcționează speech-to-text în timp real pe un apel telefonic — și de ce e atât de greu.',
            'Ce înseamnă «context window» la un LLM și de ce contează pentru conversații lungi.',
            'Cum se construiește o bază de cunoștințe care învață singură din întrebările pe care nu le poate răspunde.',
            'De ce diacriticele românești sunt o problemă reală în AI și cum le rezolvăm noi.',
        ],
        'antihalucinare' => [
            'Răspunde DOAR din datele tale: dacă nu știe, spune cinstit «nu am informația asta», nu inventează prețuri sau termene.',
            'Anti-halucinare cu nivel de confidență: dacă AI-ul nu e sigur, escaladează la om sau cere clarificare în loc să ghicească.',
            'Răspunsuri cu surse: agentul AI arată din ce document a luat fiecare informație — verificabil, nu black-box.',
            '10 straturi de verificare înainte de fiecare răspuns: ultima protecție e specific anti-halucinare.',
            'Detectează automat întrebările fără răspuns și generează FAQ-uri ca să închizi gap-urile în baza ta de cunoștințe.',
        ],
        'baza_cunostinte' => [
            'Bază de cunoștințe inteligentă: încarci PDF, DOCX, CSV sau link de site, AI-ul citește, structurează și organizează automat.',
            'Importă întregul site într-un click: scanare automată, extragere conținut, indexare semantică.',
            'FAQ-uri generate automat din întrebările reale ale clienților, nu dintr-un template generic.',
            'Documentele tale rămân live: actualizezi un PDF, agentul AI învață imediat, fără re-training manual.',
            'Health score per agent și gap analysis: vezi exact ce subiecte nu acoperă bine baza ta de cunoștințe.',
        ],
        'voce' => [
            'Voicebot cu voce naturală în română: numere RO, transcriere live, analiză de sentiment în timpul apelului.',
            'Preia apeluri 24/7 cu voce realistă, nu robotică. Clientul nici nu-și dă seama că vorbește cu AI.',
            'Sentiment live pe apel: alertă instant când un client e nervos, escaladare imediată la operator uman.',
            'Agent vocal + agent chat, același creier: clientul te sună după ce a vorbit pe site, contextul e păstrat.',
            'Detectare frustrare în voce: tonul agentul AIui devine empatic automat când simte iritare.',
        ],
        'ecommerce' => [
            'WooCommerce nativ: căutare semantică pe produse, verificare stoc live, add-to-cart direct din chat.',
            'Tracking AWB automat: clientul întreabă «unde-i comanda mea?» și primește status în timp real.',
            'Carduri produs interactive în chat: imagine, preț, stoc, buton de comandă — tot inline.',
            'Funnel de conversie complet: AI-ul ghidează clientul de la întrebare la comandă fără intervenție umană.',
            'Cross-sell și recomandări inteligente: agentul AI propune produse complementare în funcție de coș.',
        ],
        'servicii' => [
            'Programări automate: verifică agenda, oferă sloturi libere, confirmă întâlnirea, trimite reminder.',
            'Pipeline de lead-uri integrat: captare → scoring → stadii (nou → contactat → calificat → câștigat).',
            'Callback-uri programate: clientul cere să fie sunat, agentul AI îți pune apelul în calendar.',
            'Pre-calificare lead-uri: agentul AI pune întrebările potrivite înainte să-ți ajungă pe email cazul.',
            'Estimări și consultanță AI: agentul AI recomandă serviciul potrivit pentru nevoia descrisă de client.',
        ],
        'securitate' => [
            'Hosting 100% în România: date stocate pe servere RO, fără transfer în afara UE.',
            'GDPR by default: izolare per cont, log-uri de acces, ștergere la cerere — totul nativ, fără setări extra.',
            'Date izolate per tenant: nimeni altcineva nu poate accesa baza ta de cunoștințe, nici măcar accidental.',
            'Echipă de suport românească: vorbești pe românește, fără tichete în engleză prin trei nivele.',
            'Conform cu reglementările UE pentru AI (AI Act): transparență, control, audit trail.',
        ],
        'platforma' => [
            'O singură linie de cod pe site și ești live. Fără plugin-uri, fără configurări complicate.',
            'Migrare ușoară de pe altă soluție: importăm baza de cunoștințe existentă fără downtime.',
            'Chat widget premium: dark mode, carduri produs, link preview, asistență proactivă pe pagini cheie.',
            'Personalizezi tonul, culorile și avatarul în câteva click-uri — agentul AI vorbește în vocea brandului tău.',
            'Conectare la WooCommerce, calendare, CRM-uri și email marketing fără cod — doar API keys.',
            'Setup live în 10 minute: 2 min descrii afacerea, 5 min uploadezi docs, 1 min adaugi linia de cod.',
            'Răspunsuri end-to-end în sub 2 secunde: classification → search → strategy → generation, totul livrat clientului.',
            'Scalează automat cu volumul: același timp de răspuns la 10 sau 10.000 de conversații simultane.',
            'Dashboard live: vezi în timp real conversațiile, sentiment-ul, conversiile, gap-urile de cunoaștere.',
            'Health score per agent: o cifră care îți spune dacă agentul AI tău își face treaba sau are nevoie de ajustări.',
            'Recomandări automate de îmbunătățire: «adaugă FAQ pentru retururi», «documentul X are erori», «clienții întreabă des despre Y».',
        ],
        'caz_real' => [
            'Cabinet stomatologic din Cluj: după implementare, programările telefonice noaptea sunt preluate de agentul AI, iar dimineața secretariatul găsește deja agenda completată. Sambla doar gestionează — medicul rămâne expertul.',
            'Service auto din Timișoara: agentul AI răspunde la întrebări despre tarife orientative și statusul reparațiilor, eliberând mecanicul de telefoane în mijlocul unei revizii. Lucrul tehnic îl face omul.',
            'Magazin online de produse cosmetice: AI-ul recomandă produse pe baza tipului de ten, iar comenzile cresc fără ca echipa de suport să fie copleșită. Recomandările vin din baza ta de produse, nu inventate.',
            'Birou de avocatură mic: agentul AI filtrează întrebările procedurale (onorarii, documente, programări) de cele care necesită consultanță reală. Avocatul primește doar cazurile relevante.',
            'Pensiune de 12 camere: rezervările vin acum 24/7 prin chat și telefon, iar proprietarul răspunde dimineața doar la cazurile speciale. Bot-ul știe disponibilitatea din calendarul propriu al pensiunii.',
            'Salon de beauty din Iași: clienții își rezervă singuri, primesc reminder cu o zi înainte, iar no-show-urile au scăzut considerabil. Programarea o face software-ul, serviciul îl face stilistul.',
            'Firmă de contabilitate: clienții întreabă agentul AI despre termene fiscale și status declarații, iar contabilul nu mai răspunde la 30 de WhatsApp-uri pe zi. Consilierea fiscală rămâne strict la contabil.',
            'Optică medicală: agentul AI răspunde la întrebări despre rame, lentile și asigurări, lasă optometristul să facă consultația. Comenzile online au crescut natural fără agenți de vânzări.',
            'Clinică veterinară: programările vin prin agentul AI 24/7, iar instrucțiunile post-operatorii sunt trimise automat. Diagnosticul îl pune medicul veterinar.',
            'Agenție imobiliară: agentul AI preia primele întrebări despre proprietăți și calificare lead-uri (buget, zonă, tip), iar agentul ajunge la potențiali cumpărători deja pregătiți.',
        ],
        'verticale' => [
            'CONTABILITATE: agent AI care răspunde clienților firmei de contabilitate la întrebări despre termene fiscale, statusul declarațiilor, documente lipsă — agentul AI citește din baza de cunoștințe a contabilului, NU oferă consultanță fiscală. Sambla = unealta, contabilul = expertul.',
            'CABINET AVOCATURĂ: agent AI care răspunde la întrebări procedurale (program, onorarii orientative, ce documente aduci la prima întâlnire, status dosar) și programează consultații. Sambla NU dă consultanță juridică — doar reduce munca repetitivă a secretariatului.',
            'CABINET MEDICAL / STOMATOLOGIC: agent AI care preia programări 24/7, răspunde la întrebări despre tarife, asigurări, ce trebuie să aduci la consultație, instrucțiuni post-procedură. Sambla NU dă sfaturi medicale — doar gestionează programările și FAQ-urile.',
            'SERVICE AUTO: agent AI care răspunde clienților la întrebări despre tarife orientative, statusul reparației, programări la ITP/revizie, ce piese sunt în stoc. Sambla = unealta service-ului, nu oferim noi reparațiile.',
            'SALON BEAUTY / FRIZERIE: agent AI care preia rezervări, răspunde despre tarife, durata serviciilor, recomandă combinații (păr + unghii), trimite reminder. Sambla = software-ul salonului, nu serviciul în sine.',
            'AGENȚIE IMOBILIARĂ: agent AI care răspunde despre proprietățile listate, programează vizionări, calificare lead-uri (buget, zonă, tip imobil) înainte ca agentul să intervină. Sambla = AI-ul agenției, nu oferim noi imobile.',
            'RESTAURANT / DELIVERY: agent AI care preia comenzi pe telefon, răspunde despre meniu, alergeni, program, livrare. Sambla = telefonul deștept al restaurantului, nu oferim noi mâncare.',
            'CABINET PSIHOLOGIE / PSIHOTERAPIE: agent AI care răspunde la întrebări procedurale (programări, tarife, durata sesiunilor, modalități online vs fizic), NU dă sfaturi psihologice. Sambla reduce munca administrativă a cabinetului.',
            'ȘCOALĂ DE LIMBI / CURSURI: agent AI care răspunde despre programe, tarife, niveluri, programări la testare, status înscriere. Sambla = secretariat AI al școlii.',
            'AGENȚIE DE TURISM: agent AI care răspunde despre pachete, disponibilitate, documente necesare, status rezervare. Sambla NU oferă noi sejururi — doar AI-ul agenției.',
            'BIROU NOTARIAL: agent AI care răspunde la întrebări procedurale (acte necesare, tarife orientative, programări), NU dă consultanță notarială.',
            'FIRMĂ DE CURĂȚENIE / SERVICII LA DOMICILIU: agent AI care preia comenzi, răspunde despre tarife, programări, zone deservite. Sambla = AI-ul firmei.',
            'CLINICĂ VETERINARĂ: agent AI care preia programări, răspunde despre vaccinări, tarife, ce să aduci la consultație. NU dă sfaturi veterinare.',
            'PENSIUNE / HOTEL MIC: agent AI care răspunde despre disponibilitate, tarife, facilități, preia rezervări 24/7. Sambla = recepția AI a pensiunii.',
            'OPTICĂ MEDICALĂ: agent AI care răspunde despre rame, lentile, asigurări de sănătate, programări la consult. NU dă sfaturi optometrice.',
        ],
    ];

    /**
     * Ask the AI for ONE fresh post idea built around two random feature
     * seeds. Returns the same shape the rest of the pipeline expects.
     */
    private function generateTopicIdea(): ?array
    {
        // Pick a random CATEGORY first, then a random seed from it. Weights
        // are tuned for a balanced feed: not too tech, not too sales, with
        // verticals (the lead-magnet) and concrete real-world cases lifted.
        //
        // Target distribution (out of 25 weighted slots):
        //   tehnologie + tehnologie_explicativ : 5  (20%)  — educational
        //   verticale                          : 5  (20%)  — niche magnets
        //   caz_real                           : 3  (12%)  — proof
        //   voce                               : 3  (12%)  — flagship feature
        //   antihalucinare                     : 2  (8%)   — differentiator
        //   securitate                         : 2  (8%)   — differentiator
        //   ecommerce                          : 2  (8%)   — big vertical
        //   baza_cunostinte                    : 2  (8%)   — core RAG story
        //   servicii                           : 2  (8%)   — concrete use case
        //   platforma                          : 2  (8%)   — perf/setup/integration
        $weights = [
            'tehnologie' => 3,
            'tehnologie_explicativ' => 2,
            'verticale' => 5,
            'caz_real' => 3,
            'voce' => 3,
            'antihalucinare' => 2,
            'securitate' => 2,
            'ecommerce' => 2,
            'baza_cunostinte' => 2,
            'servicii' => 2,
            'platforma' => 2,
        ];
        $weighted = [];
        foreach ($weights as $cat => $w) {
            if (!isset($this->featureSeedsByCategory[$cat])) continue;
            for ($i = 0; $i < $w; $i++) {
                $weighted[] = $cat;
            }
        }

        // Anti-repetition: avoid the categories used in the last 3 posts so
        // we don't get a run of 3× tech or 3× verticals back-to-back.
        // pluck('metadata->category') tries to read $row->{'metadata->category'}
        // literally on PostgreSQL, which blows up. Use selectRaw with an alias
        // so the JSON path becomes a real column name we can pluck.
        $recentCategories = SocialPost::query()
            ->whereNotNull('metadata->category')
            ->orderByDesc('id')
            ->limit(3)
            ->selectRaw("metadata->>'category' as cat")
            ->pluck('cat')
            ->filter()
            ->all();

        $forcedCategory = $this->option('category');
        if ($forcedCategory && isset($this->featureSeedsByCategory[$forcedCategory])) {
            $category = $forcedCategory;
        } else {
            $filtered = array_values(array_filter($weighted, fn($c) => !in_array($c, $recentCategories, true)));
            if (empty($filtered)) $filtered = $weighted; // fallback if everything was filtered

            $category = $filtered[array_rand($filtered)];
        }

        // Within the category, also avoid the last seed used from this same
        // category so we don't repeat "cabinet medical" twice in a row.
        $seeds = $this->featureSeedsByCategory[$category];
        $lastSeedInCat = SocialPost::query()
            ->where('metadata->category', $category)
            ->orderByDesc('id')
            ->selectRaw("metadata->>'seed' as seed")
            ->value('seed');
        $seedPool = array_values(array_filter($seeds, fn($s) => $s !== $lastSeedInCat));
        if (empty($seedPool)) $seedPool = $seeds;
        $seed = $seedPool[array_rand($seedPool)];

        // Pull recent topic angles and opening lines from the DB so the
        // AI knows what to AVOID. Without this it loops on "Vineri seara..."
        // and "Cât costă un client care sună la 22:00..." forever.
        $recent = SocialPost::query()
            ->whereIn('status', ['draft', 'scheduled', 'published'])
            ->orderByDesc('id')
            ->limit(40)
            ->get(['content', 'metadata']);

        $recentTopics = $recent
            ->map(fn($p) => $p->metadata['topic'] ?? null)
            ->filter()
            ->unique()
            ->take(20)
            ->values()
            ->all();

        $recentOpeners = $recent
            ->map(function ($p) {
                $first = preg_split('/[.!?\n]/', (string) $p->content)[0] ?? '';
                return trim(mb_substr($first, 0, 80));
            })
            ->filter()
            ->unique()
            ->take(20)
            ->values()
            ->all();

        $avoidTopicsBlock = $recentTopics ? ("- " . implode("\n- ", $recentTopics)) : "(niciuna încă)";
        $avoidOpenersBlock = $recentOpeners ? ("- " . implode("\n- ", $recentOpeners)) : "(niciuna încă)";

        // Force angle diversity: pick a random angle TYPE so the AI is
        // structurally pushed away from "scenariu pierdut client" every time.
        $angleTypes = [
            'statistic surprinzător cu cifră concretă (procent sau ore/săptămână) urmat de implicație practică',
            'mit demontat — "Nu, AI-ul nu face X. Adevărul e Y."',
            'comparație înainte/după pentru o sarcină repetitivă concretă',
            'mini-poveste cu un client real (anonimizat) și rezultatul măsurabil',
            'pas cu pas — cum rezolvi o problemă concretă în 3 pași simpli',
            'întrebare directă către cititor despre o frustrare zilnică (FĂRĂ scenariul "client care sună noaptea")',
            'lucru pe care îl faci manual și nu ar trebui — listă scurtă',
            'demo verbal: descrie ce VEDE clientul când folosește funcția',
            'analogie cu ceva familiar din viața de zi cu zi (NU telefonie/call center)',
            'check-list rapid — semne că ai nevoie de această funcție',
            'erori comune când implementezi singur și cum le eviți',
            'ce întreabă cel mai des clienții despre această funcție',
        ];
        $angle = $angleTypes[array_rand($angleTypes)];

        $prompt = "Ești copywriter pentru Sambla, platformă românească de agenți AI pentru afaceri mici și mijlocii. Audiența: antreprenori și manageri români care nu sunt tehnici. Tonul: prietenos, cald, direct, fără jargon corporate.\n\n"
            . "FUNCȚIONALITATEA pe care TREBUIE să te axezi (nu devia spre alta):\n- {$seed}\n\n"
            . "UNGHIUL OBLIGATORIU al acestei postări: {$angle}\n\n"
            . "INTERZIS — aceste deschideri sunt SUPRA-FOLOSITE. NU începe postarea cu vreuna dintre ele și NU varia pe ele:\n"
            . "- «Vineri seara. Client pe site-ul tău...» (sau orice variantă cu vineri seara)\n"
            . "- «Cât (te) costă un client care sună la 22:00 / noaptea / nu primește răspuns»\n"
            . "- «Cei mai mulți clienți pierduți nu se plâng»\n"
            . "- «Știai că antreprenorii pierd X ore pe săptămână...»\n"
            . "- Orice scenariu cu telefon care sună noaptea fără răspuns\n\n"
            . "INTERZIS ABSOLUT — DOMENII DESPRE CARE NU AVEM VOIE SĂ POSTĂM (nu oferim aceste servicii):\n"
            . "- Credite, împrumuturi, finanțări, leasing, IFN-uri, refinanțări\n"
            . "- Servicii bancare, conturi, carduri\n"
            . "- Asigurări (NU vindem polițe)\n"
            . "- Investiții, broker, trading\n"
            . "Sambla NU este o platformă financiară. Orice idee care sugerează că oferim credite, împrumuturi sau servicii financiare e GREȘITĂ și trebuie respinsă.\n\n"
            . "REGULĂ PENTRU VERTICALE (categoria 'verticale'): dacă seed-ul menționează o profesie (contabil, avocat, medic, mecanic, etc.), Sambla este DOAR unealta AI care îi ajută pe acei profesioniști să gestioneze conversațiile cu clienții lor. Sambla NU oferă serviciile profesiei respective. Postarea TREBUIE să fie clară: «AI care AJUTĂ contabilul», NU «AI care îți face contabilitatea». «AI care preia programări la cabinetul stomatologic», NU «AI care dă sfaturi medicale». Este un instrument B2B pentru profesioniștii respectivi, nu un substitut al lor.\n\n"
            . "EVITĂ și aceste unghiuri deja folosite recent:\n{$avoidTopicsBlock}\n\n"
            . "EVITĂ și aceste deschideri deja folosite recent:\n{$avoidOpenersBlock}\n\n"
            . "Generează o IDEE COMPLET DIFERITĂ de toate cele de mai sus. Concretă, nu generică. Pune accent pe BENEFICIUL pentru proprietarul afacerii (timp câștigat, clienți câștigați, bani economisiți, liniște), nu pe descrieri tehnice.\n\n"
            . "REGULĂ DE CONCRETEȚE: 'topic' TREBUIE să menționeze EXPLICIT funcționalitatea de mai sus (cuvintele cheie din ea — ex: «analiza de sentiment», «agent vocal», «RAG», «hybrid search», «WooCommerce», «pgvector», «hosting în România», «programări automate»). INTERZIS topic vag de tipul «vrei o afacere mai eficientă?», «vrei mai mulți clienți?», «visezi la mai mult timp?». Topicul trebuie să spună CONCRET ce face Sambla, nu o promisiune generală.\n\n"
            . "REGULĂ CRITICĂ: 'image_concept' TREBUIE să fie o vizualizare LITERALĂ a 'topic'. Scena descrisă în image_concept TREBUIE să arate exact ceea ce spune topicul — același obiect, aceeași acțiune, aceeași emoție. Dacă topicul vorbește despre un agent AI care preia apeluri, scena conține telefon și apel; dacă topicul e despre RAG/căutare în documente, scena conține documente/laptop/căutare; dacă topicul e despre ecommerce, scena conține un magazin/coș/mobil. NICIODATĂ scenă generică care nu reflectă topicul.\n\n"
            . "Returnează DOAR JSON valid, exact în acest format:\n"
            . '{"topic":"o propoziție-două care descriu unghiul postării — concret, nu generic","cta":"un îndemn scurt în română (2-4 cuvinte)","visual_text":"1-3 cuvinte SCURTE și UZUALE în română (fără cratimă, fără cifre lungi, fără termeni tehnici); ceva ce orice om înțelege instant — ex: «mai mulți clienți», «zero stres», «răspunde mereu»","image_concept":"o scenă vizuală bogată (în engleză, pentru generatorul de imagini) care VIZUALIZEAZĂ LITERAL topicul de mai sus: mediu real, mockup de device, diorama 3D sau ilustrație flat — descrie mediul, lumina, obiectele specifice topicului. NICIODATĂ «simple icon on white background», «minimal flat icon», «clean white with one icon». Vrem scene cu profunzime și caracter, ANCORATE pe topic."}';

        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Generezi idei de postări social media pentru un brand românesc de AI conversațional. Răspunzi exclusiv în JSON valid, în limba română pentru text și engleză pentru image_concept.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 500,
                'temperature' => 0.95,
                'response_format' => ['type' => 'json_object'],
            ]);

            $raw = $response->choices[0]->message->content ?? '';
            $parsed = json_decode($raw, true) ?: [];

            if (empty($parsed['topic']) || empty($parsed['cta'])) {
                return null;
            }

            // Hard cap visual_text to 3 short words — graphics fail with
            // longer strings and we want catchy, not paragraphs.
            $visualText = trim((string) ($parsed['visual_text'] ?? ''));
            $words = preg_split('/\s+/', $visualText) ?: [];
            if (count($words) > 3) {
                $visualText = implode(' ', array_slice($words, 0, 3));
            }

            return [
                'topic' => $parsed['topic'],
                'cta' => $parsed['cta'],
                'visual_text' => $visualText ?: 'mai mulți clienți',
                'image_concept' => $parsed['image_concept'] ?? 'A warm cinematic editorial photo of a Romanian small business owner happy at their desk, soft window light, depth of field.',
                'seed' => $seed,
                'category' => $category,
            ];
        } catch (\Throwable $e) {
            $this->warn("  Topic idea generation failed: {$e->getMessage()}");
            return null;
        }
    }

    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $untilTime = $this->option('until');
        $platformOption = $this->option('platform');
        $dryRun = $this->option('dry-run');
        $draftsOnly = (bool) $this->option('drafts');

        $fbAccount = SocialAccount::where('platform', 'facebook')->where('is_active', true)->first();
        $igAccount = SocialAccount::where('platform', 'instagram')->where('is_active', true)->first();

        if ($platformOption === 'both' && (!$fbAccount || !$igAccount)) {
            $this->warn('Not all accounts configured. Using available ones.');
        }

        $dateOpt = $this->option('date');
        $fromTime = $this->option('from');
        $targetDate = $dateOpt ? Carbon::parse($dateOpt)->startOfDay() : Carbon::today();
        $endTime = $targetDate->copy()->setTimeFromTimeString($untilTime);
        $startTime = $targetDate->copy()->setTimeFromTimeString($fromTime);
        $now = Carbon::now();

        // For today, never schedule in the past
        if ($targetDate->isToday() && $startTime->lt($now)) {
            $startTime = $now;
        }

        // For draft buffer generation we don't actually schedule anything,
        // so the time window is irrelevant — just give it a sane fake span
        // so the rest of the loop doesn't divide by zero. This unblocks
        // generation in the evening / night when the default 09:00-20:00
        // window is already in the past.
        if ($draftsOnly && $endTime->lte($startTime)) {
            $endTime = $startTime->copy()->addMinutes(max(60, $count * 5));
        }

        if ($endTime->lte($startTime)) {
            $this->error("End time {$untilTime} is before start time {$startTime->format('H:i')} on {$targetDate->toDateString()}.");
            return self::FAILURE;
        }

        $minutesAvailable = $startTime->diffInMinutes($endTime);
        $interval = (int) floor($minutesAvailable / max(1, $count));

        $this->info("Generating {$count} posts on {$targetDate->toDateString()}, every ~{$interval} min from {$startTime->format('H:i')} to {$endTime->format('H:i')}");
        $this->newLine();

        $gemini = app(GeminiContentService::class);

        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $topicData = $this->generateTopicIdea();
            if (!$topicData) {
                $this->warn("Skipping post " . ($i + 1) . ": topic idea generation failed");
                continue;
            }

            $scheduledAt = $startTime->copy()->addMinutes($interval * ($i + 1));
            $this->components->task(
                "Post " . ($i + 1) . "/{$count} @ {$scheduledAt->format('H:i')} — {$topicData['visual_text']}",
                function () use ($i, $topicData, $scheduledAt, $gemini, $fbAccount, $igAccount, $platformOption, $dryRun, $draftsOnly, &$created) {
                    // Generate text content
                    $textResult = $this->generateText($gemini, $topicData);
                    if (!$textResult) return false;

                    // Generate CTA-focused image with minimal text
                    $image = $this->generateCtaImage($gemini, $topicData);

                    if ($dryRun) {
                        $this->line("    Text: " . mb_substr($textResult['content'], 0, 80) . "...");
                        if ($image) $this->line("    Image: {$image['url']}");
                        return true;
                    }

                    $postStatus = $draftsOnly ? 'draft' : 'scheduled';
                    $fbScheduled = $draftsOnly ? null : $scheduledAt;
                    $igScheduled = $draftsOnly ? null : $scheduledAt->copy()->addMinutes(5);

                    // Every 3rd post idea also gets a Story (9:16) child.
                    // Uses a simple modulo on the iteration index for deterministic cadence.
                    $includeStory = ($i % 3 === 0);
                    $storyImage = null;
                    if ($includeStory) {
                        $storyImage = $this->generateStoryImage($gemini, $topicData);
                    }

                    // Create the group that binds all children together.
                    $group = SocialPostGroup::create([
                        'topic' => mb_substr($topicData['topic'], 0, 240),
                        'cta' => $topicData['cta'] ?? null,
                        'status' => $postStatus === 'scheduled' ? 'scheduled' : 'draft',
                        'has_story' => $includeStory && $storyImage,
                        'metadata' => [
                            'visual_text' => $topicData['visual_text'] ?? null,
                            'image_concept' => $topicData['image_concept'] ?? null,
                        ],
                    ]);

                    // Create Facebook post
                    if (in_array($platformOption, ['both', 'facebook']) && $fbAccount) {
                        SocialPost::create([
                            'group_id' => $group->id,
                            'social_account_id' => $fbAccount->id,
                            'platform' => 'facebook',
                            'status' => $postStatus,
                            'post_type' => 'post',
                            'content' => $textResult['content'],
                            'hashtags' => $textResult['hashtags'] ?? [],
                            'image_url' => $image['url'] ?? null,
                            'image_prompt' => $topicData['image_concept'],
                            'metadata' => ['topic' => $topicData['topic'], 'cta' => $topicData['cta'], 'category' => $topicData['category'] ?? null, 'seed' => $topicData['seed'] ?? null],
                            'scheduled_at' => $fbScheduled,
                            'ai_tokens_used' => $textResult['tokens_used'] ?? 0,
                        ]);
                        $created++;
                    }

                    // Create Instagram post (only if we have an image)
                    if (in_array($platformOption, ['both', 'instagram']) && $igAccount && $image) {
                        SocialPost::create([
                            'group_id' => $group->id,
                            'social_account_id' => $igAccount->id,
                            'platform' => 'instagram',
                            'status' => $postStatus,
                            'post_type' => 'post',
                            'content' => $textResult['content'],
                            'hashtags' => $textResult['hashtags'] ?? [],
                            'image_url' => $image['url'] ?? null,
                            'image_prompt' => $topicData['image_concept'],
                            'metadata' => ['topic' => $topicData['topic'], 'cta' => $topicData['cta'], 'category' => $topicData['category'] ?? null, 'seed' => $topicData['seed'] ?? null],
                            'scheduled_at' => $igScheduled,
                            'ai_tokens_used' => 0,
                        ]);
                        $created++;
                    }

                    // Story child (IG only, 9:16). Scheduled slightly later to
                    // avoid hammering Meta's API; draft if we're buffering.
                    if ($includeStory && $storyImage && $igAccount && in_array($platformOption, ['both', 'instagram'])) {
                        $storyScheduled = $draftsOnly ? null : $scheduledAt->copy()->addMinutes(10);
                        SocialPost::create([
                            'group_id' => $group->id,
                            'social_account_id' => $igAccount->id,
                            'platform' => 'instagram',
                            'status' => $postStatus,
                            'post_type' => 'story',
                            'content' => $textResult['content'],
                            'hashtags' => [],
                            'image_url' => $storyImage['url'] ?? null,
                            'image_prompt' => $this->storyPrompt($topicData),
                            'metadata' => ['topic' => $topicData['topic'], 'cta' => $topicData['cta'], 'story' => true, 'category' => $topicData['category'] ?? null, 'seed' => $topicData['seed'] ?? null],
                            'scheduled_at' => $storyScheduled,
                            'ai_tokens_used' => 0,
                        ]);
                        $created++;
                    }

                    return true;
                }
            );
        }

        $this->newLine();
        $this->info("Created {$created} posts total.");

        return self::SUCCESS;
    }

    /**
     * Five distinct hook patterns. Picked randomly per post so the feed
     * doesn't feel like the same template on repeat. Each pattern is
     * spelled out so GPT stays on rails instead of defaulting to vague
     * "punchy opening" prose.
     */
    private array $hookPatterns = [
        'question' => "Începe cu o întrebare directă care lovește o frustrare reală a owner-ului/managerului (ex: 'Cât te costă un client care sună la 22:00 și nu răspunde nimeni?'). Nu întrebări retorice generice.",
        'stat' => "Începe cu o cifră surprinzătoare sau contrainutitivă (procent, sumă în lei/euro, timp). Cifra trebuie să fie plauzibilă, nu inventată grotesc.",
        'story' => "Începe cu un micro-scenariu concret, 1-2 propoziții: 'Vineri seara. Client pe site-ul tău de 4 minute. Niciun răspuns. Pleacă.' Ton cinematic, prezent.",
        'contrarian' => "Începe cu o afirmație care contrazice un clișeu popular ('Nu, AI-ul NU îți va înlocui echipa de suport. Dar...'). Construiește tensiune, apoi reașază.",
        'insight' => "Începe cu un adevăr nespus pe care doar cineva care a trăit problema îl știe ('Cei mai mulți clienți pierduți nu se plâng niciodată — pleacă în tăcere.'). Empatic, observațional.",
    ];

    private function generateText(GeminiContentService $gemini, array $topicData): ?array
    {
        $avoidance = \App\Models\SocialRejection::buildAvoidancePrompt('facebook');
        $hookKey = array_rand($this->hookPatterns);
        $hookInstruction = $this->hookPatterns[$hookKey];

        $seedLine = !empty($topicData['seed']) ? "FUNCȚIONALITATEA SAMBLA pe care TREBUIE să o evidențiezi (nu devia spre alta): {$topicData['seed']}\n\n" : '';

        // Educational mode: pentru categorii tehnice scriem postări explicative,
        // fără vânzare. Goal-ul e să arătăm CE facem, nu să-l convingem pe cititor
        // să cumpere acum. CTA-ul devine soft, opțional, în coadă.
        $isEducational = in_array($topicData['category'] ?? null, ['tehnologie', 'tehnologie_explicativ'], true);

        if ($isEducational) {
            $modeBlock = "MOD: EDUCAȚIONAL / EXPLICATIV. Aceasta NU e o postare de vânzare. Goal-ul e să prezinte tehnologia/arhitectura noastră într-un mod accesibil, ca un developer care explică prietenilor cum gândește produsul. Cititorul învață ceva nou, NU e împins să cumpere.\n\n"
                . "TON: curios, didactic, prietenos, încrezător dar fără să se laude. Ca un articol scurt de tipul «cum funcționează». Fără superlative, fără «cel mai bun», fără «revoluționar».\n\n"
                . "STRUCTURĂ EDUCAȚIONALĂ (urmează această structură, NU cea de vânzare):\n"
                . "1. Hook curios (1-2 rânduri): pune o întrebare interesantă SAU enunță un fapt tehnic surprinzător.\n"
                . "2. Explicația principală (3-5 rânduri): cum funcționează concret, cu un exemplu palpabil. Fără jargon — dacă folosești un termen tehnic (ex: «vector search», «embedding», «RAG»), explică-l în paranteză în câteva cuvinte.\n"
                . "3. De ce contează în practică (1-2 rânduri): impact real, nu marketing.\n"
                . "4. CTA SOFT, opțional, în coadă: «vezi cum facem la noi → sambla.ro» SAU «citește mai mult → sambla.ro» SAU «curios? scrie-ne». NU «cumpără acum», NU «începe astăzi», NU «transformă-ți afacerea».\n\n"
                . "IMPORTANT pentru educațional:\n"
                . "- NU începe cu «Te-ai săturat să...», «Imaginează-ți că...», «Vrei să...», «Stresat de...» — astea sunt hook-uri de vânzare.\n"
                . "- Începe cu un fapt, o întrebare conceptuală sau un termen tehnic explicat.\n"
                . "- Vorbește despre Sambla la persoana întâi plural («la noi», «cum facem», «am ales să»).\n"
                . "- Evidențiază DECIZIILE de design și DE CE le-am luat, nu doar CE oferim.\n\n";
        } else {
            $modeBlock = "MOD: COMERCIAL. Vorbește direct despre beneficiul pentru proprietarul afacerii.\n\n";
        }

        $prompt = ($avoidance ? $avoidance . "\n\n" : '')
            . "Scrii un post de social media pentru Sambla, pe Facebook/Instagram. Publicul: antreprenori și manageri români din IMM, e-commerce, servicii — oameni ocupați, NU tehnici. Vorbesc românește zilnic.\n\n"
            . $modeBlock
            . $seedLine
            . "SUBIECT (TREBUIE să fie centrul postării — nu devia, nu generaliza, nu schimba subiectul): {$topicData['topic']}\n"
            . "REGULĂ DE ANCORARE: postarea TREBUIE să vorbească EXPLICIT despre funcționalitatea de mai sus. Dacă topicul menționează «analiza de sentiment», postarea trebuie să fie despre analiza de sentiment, nu despre «clienți care sună noaptea». Dacă topicul e despre «RAG / căutare în documente», postarea e despre asta. NU înlocui niciodată subiectul cu un șablon generic despre call-uri ratate.\n\n"
            . "CALL TO ACTION: {$topicData['cta']}\n\n"
            . "PATTERN DE HOOK ({$hookKey}): {$hookInstruction}\n\n"
            . "STRUCTURĂ (fiecare element pe RÂNDUL LUI, cu LINIE GOALĂ între blocuri ca să respire pe mobil):\n"
            . "1. Hook scurt (1-2 rânduri).\n"
            . "2. Problema reală: un exemplu palpabil din viața unui business românesc, în 2-3 rânduri scurte.\n"
            . "3. Cum ajută Sambla — DOAR în limbaj prietenos, NU tehnic. Spune CE CÂȘTIGĂ omul (timp, clienți, liniște, bani), nu CUM funcționează tehnologia. Maxim 2 rânduri.\n"
            . "4. (Opțional) O listă scurtă de 2-3 beneficii concrete, fiecare pe rândul lui, cu un emoji la început (ex: ✅ răspunde și noaptea / 🧘 echipa ta respiră / 💰 lead-uri captate singur).\n"
            . "5. CTA prietenos: {$topicData['cta']} → sambla.ro\n\n"
            . "TON:\n"
            . "- Cald, prietenos, conversațional. Ca și cum i-ai povesti unui prieten antreprenor.\n"
            . "- Propoziții scurte. Cuvinte UZUALE în română — orice cuvânt rar sau tehnic îl înlocuiești cu unul simplu.\n"
            . "- Pune BENEFICIUL în prim-plan, nu funcționalitatea.\n"
            . "- INTERZIS: «revoluționar», «inovator», «game-changer», «soluție completă», «scalabil», «next-level», «transformă modul în care», «puterea AI-ului», «empowering», «insights», «engagement», «leverage», «seamless», anglicisme gratuite, jargon corporate.\n"
            . "- INTERZIS ABSOLUT (deschideri supra-folosite): «Vineri seara...», «Cât (te) costă un client care sună la 22:00 / noaptea / nu primește răspuns», «Cei mai mulți clienți pierduți nu se plâng», «Știai că antreprenorii pierd X ore pe săptămână», orice scenariu cu telefon care sună noaptea fără răspuns. Folosește alt hook complet.\n"
            . "- INTERZIS CLIȘEE GENERICE: NU folosi expresiile: «timp prețios», «economisești timp», «non-stop», «fiecare secundă/minut contează», «într-o lume aglomerată/agitată», «Imaginează-ți că/cum». Acestea sunt supra-folosite. Fii CONCRET și SPECIFIC — spune exact CE face funcționalitatea, nu promisiuni vagi.\n"
            . "- INTERZIS DOMENII: NU pomeni credite, împrumuturi, finanțări, leasing, IFN, asigurări, investiții, banking. Sambla NU oferă servicii financiare. Dacă subiectul te împinge într-acolo, ești pe drum greșit.\n"
            . "- REGULĂ VERTICALE: dacă subiectul vorbește despre o profesie (contabil, avocat, medic, mecanic, salon, restaurant, etc.), Sambla este DOAR unealta AI a profesionistului — NU îi facem treaba. Spune clar «AI care AJUTĂ X», nu «AI care e X». Niciodată Sambla nu oferă consultanță fiscală, juridică, medicală sau financiară.\n"
            . "- Subpromite, nu suprapromite. Plauzibil.\n\n"
            . "FORMAT VIZUAL (foarte important — postul trebuie să arate ‘airy’ pe mobil):\n"
            . "- Max 110 cuvinte total.\n"
            . "- Emoji: 4-7 emoji-uri, distribuite natural prin text — NU înghesuite la final. Folosește emoji care au sens (📞 ☎️ 💬 💼 🛒 ⏰ 🌙 ✅ 🧠 🤝 🚀 🎯 ⚡ 💡 🇷🇴). Nu decorativ pur.\n"
            . "- Paragrafe foarte scurte (1-3 rânduri), separate prin LINIE GOALĂ (\\n\\n).\n"
            . "- Lista de beneficii cu emoji la început, fiecare pe linie nouă.\n"
            . "- FĂRĂ hashtag-uri (zero, nici măcar la final).\n"
            . "- FĂRĂ link-uri brute, doar mențiunea «sambla.ro» în CTA.\n\n"
            . "BRAND SAMBLA (folosește, nu cita textual):\n"
            . "- Platformă românească (hosting în România, GDPR, echipă RO) de agenți AI.\n"
            . "- Funcționalități reale pe care le poți menționa NETEHNIC: răspunde 24/7, învață din documentele tale (PDF, contracte, FAQ-uri), preia apeluri telefonice cu voce naturală, captează lead-uri, programări automate, integrare cu magazinul WooCommerce, alertă când un client e supărat, escaladare la om când e cazul.\n"
            . "- Vorbește ca un fondator prietenos, nu ca un agent de vânzări.\n\n"
            . 'Returnează DOAR JSON: {"content": "textul postării cu \\n\\n între paragrafe"}';

        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Ești expert în social media marketing pentru branduri tech/SaaS. Generezi conținut concis, orientat spre conversii. Răspunzi în JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 500,
                'temperature' => 0.85,
                'response_format' => ['type' => 'json_object'],
            ]);

            $text = $response->choices[0]->message->content ?? '';
            $parsed = json_decode($text, true) ?: [];
            $tokens = ($response->usage->promptTokens ?? 0) + ($response->usage->completionTokens ?? 0);

            return [
                'content' => $parsed['content'] ?? $text,
                'hashtags' => [],
                'tokens_used' => $tokens,
            ];
        } catch (\Throwable $e) {
            $this->error("  Text generation failed: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Modern SaaS visual styles — loaded from config so both the batch
     * command and the service share the same presets.
     */
    private function getVisualStyles(): array
    {
        return config('social-image-styles');
    }

    private function generateCtaImage(GeminiContentService $gemini, array $topicData): ?array
    {
        $imageRejections = \App\Models\SocialRejection::query()
            ->whereIn('reason_category', ['image', 'visual', 'design'])
            ->latest()->limit(10)->pluck('feedback')->filter()->unique()->take(5)->implode(' | ');
        $avoidLine = $imageRejections ? "CRITICAL — AVOID what user rejected before: {$imageRejections}. " : '';

        $styles = $this->getVisualStyles();
        $styleKey = array_rand($styles);
        $preset = $styles[$styleKey];

        $topicAnchor = isset($topicData['topic']) ? "SUBIECTUL POSTĂRII (imaginea TREBUIE să fie vizual relevantă): {$topicData['topic']} " : '';

        $isVerticale = ($topicData['category'] ?? null) === 'verticale';

        if ($isVerticale) {
            // Extract the niche name from the seed (first word before ":")
            $niche = 'business';
            if (!empty($topicData['seed']) && preg_match('/^([A-ZĂÂÎȘȚ\s\/]+):/u', $topicData['seed'], $m)) {
                $niche = mb_strtolower(trim($m[1]));
            }

            $nicheVisuals = [
                'contabilitate' => 'calculatoare, grafice financiare, dosare colorate, cifre floating, pixeli de spreadsheet',
                'cabinet avocatură' => 'balanța justiției stilizată, cărți de drept, documente sigilate, elemente juridice',
                'cabinet medical / stomatologic' => 'stetoscop stilizat, instrumente medicale moderne, ecrane de monitorizare, elemente de sănătate',
                'service auto' => 'chei mecanice stilizate, piese auto, dashboard digital, elemente de atelier modern',
                'salon beauty / frizerie' => 'foarfece stilizate, pensule de machiaj, palete de culori, elemente glamour',
                'agenție imobiliară' => 'chei de casă, planuri de apartament, clădiri stilizate, pin-uri de locație',
                'restaurant / delivery' => 'farfurii elegante, ingrediente colorate, cutii de livrare, elemente culinare',
                'cabinet psihologie / psihoterapie' => 'forme abstracte calmante, plante, spații serene, elemente de wellbeing',
                'școală de limbi / cursuri' => 'cărți colorate, steaguri de țări, litere floating, elemente educaționale',
                'agenție de turism' => 'valize colorate, bilete de avion, globuri, repere turistice stilizate',
                'birou notarial' => 'sigilii, ștampile, documente oficiale stilizate, elemente de autentificare',
                'firmă de curățenie / servicii la domiciliu' => 'spray-uri colorate, bule de săpun, case strălucitoare, elemente fresh',
                'clinică veterinară' => 'lăbuțe colorate, stetoscop veterinar, animale stilizate (pisici, câini), elemente de îngrijire',
                'pensiune / hotel mic' => 'chei de cameră, peisaje montane/rurale, pat confortabil, elemente de ospitalitate',
                'optică medicală' => 'ochelari stilizați, lentile, teste de vedere, elemente optice colorate',
            ];

            $nicheElements = $nicheVisuals[$niche] ?? 'elemente specifice domeniului, obiecte profesionale stilizate';

            $prompt = $avoidLine
                . "Creează o imagine 3:4 VIBRANTĂ și COLORATĂ pentru social media Sambla — platformă românească de agenți AI. "
                . "NIȘĂ: {$niche} — imaginea TREBUIE să conțină elemente vizuale din acest domeniu: {$nicheElements}. "
                . "STIL: Ilustrație modernă, colorată, dinamică — culori vii și saturate (nu palide/corporate). Gândește: poster de festival tech meets infographic Dribbble. Fundalul poate fi gradient bold sau scenă colorată. Elementele din nișă sunt stilizate, isometrice sau flat-design, nu realiste. "
                . $topicAnchor
                . "CONCEPT: {$topicData['image_concept']}. Îmbină elementele din nișa ({$niche}) cu elemente tech/AI (chat bubbles, unde sonore, dashboard-uri) pentru a arăta cum Sambla ajută acest domeniu. "
                . "HEADLINE: dacă pui text, să fie despre cum Sambla ajută domeniul {$niche}. "
                . "ENERGIE: Vibrant, optimist, profesional dar accesibil. Culorile trebuie să POP pe feed. "
                . "COMPOZIȚIE: Dinamică, cu mai multe elemente vizuale distribuite armonios. Punct focal central clar. "
                . "SAFE ZONES: minim 10% margine liberă pe toate laturile — Instagram cropuiește marginile. Centrează textul și elementele cheie. "
                . "ASPECT: 3:4 portrait.";
        } else {
            $prompt = $avoidLine
                . "Creează o imagine 3:4 pentru social media Sambla — platformă românească de agenți AI. "
                . "STIL VIZUAL ({$preset['name']}): {$preset['prompt']} "
                . $topicAnchor
                . "CONCEPT: {$topicData['image_concept']}. Transformă în vizual modern, tech, premium — ca hero image de pe site-ul unui startup top. "
                . "HEADLINE: dacă pui text, să fie relevant cu subiectul postării — ceva ce un antreprenor înțelege instant. "
                . "DISPOZIȚIE: Smart, prietenos, profesional. Imaginea trebuie să facă un antreprenor să gândească 'vreau să folosesc acest tool'. "
                . "COMPOZIȚIE: Curată, bold, designată. Punct focal puternic. Gradienți sau iluminare moale. Oprește scroll-ul pe Instagram/Facebook. "
                . "SAFE ZONES: minim 10% margine liberă pe toate laturile — Instagram cropuiește marginile. Centrează textul și elementele cheie. "
                . "ASPECT: 3:4 portrait.";
        }

        return $gemini->generateImage($prompt, '4:5');
    }

    private function storyPrompt(array $topicData): string
    {
        $styles = $this->getVisualStyles();
        $styleKey = array_rand($styles);
        $preset = $styles[$styleKey];

        $topicAnchor = isset($topicData['topic']) ? "SUBIECTUL POSTĂRII: {$topicData['topic']} " : '';

        $isVerticale = ($topicData['category'] ?? null) === 'verticale';

        if ($isVerticale) {
            $niche = 'business';
            if (!empty($topicData['seed']) && preg_match('/^([A-ZĂÂÎȘȚ\s\/]+):/u', $topicData['seed'], $m)) {
                $niche = mb_strtolower(trim($m[1]));
            }

            return "Creează o imagine 9:16 verticală VIBRANTĂ pentru Instagram Story Sambla — platformă românească de agenți AI. "
                . "NIȘĂ: {$niche} — include elemente vizuale specifice acestui domeniu, stilizate și colorate. "
                . $topicAnchor
                . "CONCEPT: {$topicData['image_concept']}. Îmbină elemente din nișa ({$niche}) cu elemente tech/AI. "
                . "STIL: Ilustrație colorată, dinamică, culori vii saturate. Poster de festival tech meets Dribbble. Elementele din nișă stilizate isometric sau flat-design. "
                . "HEADLINE: dacă pui text, în treimea din mijloc (safe zone Instagram). "
                . "Compoziție verticală full-bleed, element central bold, culori care POP. "
                . "ASPECT: 9:16 portrait.";
        }

        return "Creează o imagine 9:16 verticală pentru Instagram Story Sambla — platformă românească de agenți AI. "
            . "STIL VIZUAL ({$preset['name']}): {$preset['prompt']} "
            . $topicAnchor
            . "CONCEPT: {$topicData['image_concept']}. Vizual modern, premium, tech. "
            . "HEADLINE: dacă pui text, în treimea din mijloc (safe zone Instagram sus/jos). "
            . "Compoziție verticală full-bleed, element central bold, spațiu generos sus/jos. "
            . "ASPECT: 9:16 portrait.";
    }

    private function generateStoryImage(GeminiContentService $gemini, array $topicData): ?array
    {
        return $gemini->generateImage($this->storyPrompt($topicData), '9:16');
    }
}
