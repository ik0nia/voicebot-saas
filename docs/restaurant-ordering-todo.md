# Restaurant ordering — ce a rămas de făcut

Stare la **2026-08-25, seara**, după primele două comenzi reale preluate
telefonic de botul 79 (Urban Doner) — comenzile `00015` și `00016`, apelurile
139 și 140 — și după sesiunea de reparații care a urmat.

Tot ce e mai jos e derivat din datele acelor apeluri sau din măsurători făcute
în aceeași zi, nu din presupuneri. Unde ceva n-a fost verificat, scrie explicit.

---

## 1. Ce mai blochează un local real

### 1.1 Nimic din blocantele de comandă — sunt reparate

Cele cinci blocante din versiunea de dimineață a acestui document (nume
inventat, linii care colapsează, comentariul modelului pe bon, coș care nu
unește liniile, plasare fără confirmare) sunt rezolvate și verificate cap-coadă
pe botul 79. Detaliile sunt în secțiunea „Reparat" de la final.

### 1.2 Bridge-ul vocal are trei fixuri scrise, dar nedeployate

`services/media-stream/src/openaiBridge.js` e modificat pe disc și **diferit de
imaginea care rulează** (`md5sum` host vs `/app/src/` în container). Containerul
e imagine construită, fără bind-mount, deci până la un redeploy Coolify apelurile
merg pe codul vechi:

- suprimarea întreruperii cât timp se rostește salutul (3.1);
- timeout 12 s + o reîncercare la cererea de config, în loc de 5 s și tăcere (3.2);
- deschiderea socketului OpenAI în paralel cu cererea de config (3.3).

După redeploy, `eagerness` se poate întoarce de pe `low` pe `medium`/`auto` pe
botul 79 — plasturele nu mai e necesar când bridge-ul ignoră zgomotul pe salut.

### 1.3 Dashboard-ul tot n-a fost deschis într-un browser

Cele 7 pagini randează și promptul se asamblează corect (verificate programatic),
dar niciun clic real n-a fost dat și nicio comandă n-a fost modificată prin
interfață.

### 1.4 Tenantul 95 n-are niciun cont de utilizator

Localul nu se poate autentifica; doar un super-admin ajunge la comenzi. Decis
conștient pe 2026-08-25 să rămână așa cât timp e bot de test.

---

## 2. Calea vocală — ce rămâne după redeploy

### 2.1 Turele cu tool-uri sunt costul real

Apelul 139 a făcut **8 apeluri de tool**, fiecare cu un drum dus-întors
bridge → Laravel → OpenAI, cu pauze de 1,5–2,5 s. Dintre ele, un `search_menu`
cu argumente goale la început și un `review_order` apelat de două ori.

Primul e eliminat: preparatele recomandate sunt setate acum (Doner Kebab,
Shaorma mare la lipie, Burger pui), deci botul răspunde la „ce aveți?" fără
tool. Restul cere reducerea numărului de tool-uri per comandă.

Confirmarea numărului de telefon a fost pliată pe întrebarea despre nume tocmai
ca să nu adauge o tură în plus.

### 2.2 Ce am măsurat și NU ajută

- **`reasoning: {effort: "none"}`** e acceptat de API, dar pe o tură simplă nu
  schimbă nimic: 615 ms cu `low` vs 660 ms cu `none`, mediană din 3 rulări.
  **Netestat pe turele cu tool-uri**, unde am văzut până la 156 de tokeni de
  raționament; acolo s-ar putea să conteze.
- **`gpt-realtime-2.1`** nu e mai rapid decât `gpt-realtime-2` (611 ms vs
  615 ms). Rămâne interesant ca model mai nou, nu ca optimizare de viteză. Se
  schimbă din variabila de mediu Coolify + restart, fără rebuild.

---

## 3. Datorii mai mici

- **Numerotarea comenzilor e per local acum**, dar restul referințelor din
  platformă (rezervări, lead-uri) rămân pe id global.
- **Adresele se potrivesc pe zonă pe cuvânt întreg**, nu pe substring. O adresă
  fără oraș tot nu se potrivește cu nicio zonă — la un local care livrează doar
  în Gherla asta înseamnă refuz, deci botul trebuie să ceară orașul, nu să
  presupună. De urmărit pe apeluri reale dacă refuză prea des.
- **Filtrul de halucinații taie sub 3 caractere**, cu excepția „da", „nu" și
  „ok", adăugate pentru că sunt exact răspunsul la „confirmați comanda?".
- **Nu există infrastructură de plată** nicăieri în platformă — plata e cash
  sau card la livrare, doar înregistrată.
- **Nu există UI de wizard** pentru activarea comenzilor; se face din
  `restaurant:configure-ordering`.

---

## Reparat în sesiunea din 2026-08-25

Pentru context, ca să nu fie reinvestigate.

**Dimineață — calea vocală și dashboard-ul:**

- Apel complet mut → cererea de config expira la 5 s pe OPcache rece.
- `<Say>` robotic dinaintea `<Connect>` scos din `TwilioService` (~3 s timp mort,
  voce non-neurală, „Bună ziua" dublat).
- Transcriere mutată de pe `gpt-4o-mini-transcribe` pe `gpt-4o-transcribe`.
  Măsurat pe română trecută prin 8 kHz μ-law: mini a returnat „Ach my vra un ke
  daou burger depui ha tri cola" pentru „Aș mai vrea încă doi burgeri de pui și
  trei cola"; modelul complet a returnat propoziția exact.
- Inbox-ul dădea 500 pentru orice cont cu apeluri și fără conversații —
  `Eloquent\Collection::map()` nu coboară la colecție de bază când e goală, iar
  `merge()` cerea apoi `getKey()` de la `stdClass`. Reparat în `InboxController`
  cu `->toBase()`, în ambele metode.
- Dashboard de comenzi, meniu și livrare, plus intrarea „Comenzi" în meniul
  principal.

**Seara — blocantele de comandă:**

- **Numele clientului.** `customer_name` scos din `required`-ul schemei și
  validat în handler: numele inventate („Client", „client nou", „anonim", „N/A")
  sunt respinse, iar tool-ul întoarce în câmpul `ask` exact întrebarea de pus.
  Un câmp obligatoriu pe care modelul nu-l are îl împinge să-l fabrice; unul pe
  care handler-ul îl cere îl împinge să întrebe.
- **Telefonul.** Venea deja automat din caller ID — ambele comenzi reale îl au.
  Nou: numărul e citit înapoi clientului spre confirmare, în aceeași replică în
  care se cere numele, iar dacă el dictează alt număr acela câștigă. Numărul de
  pe care a sunat rămâne în `metadata.caller_id`, împreună cu
  `phone_confirmed` și `phone_source`.
- **Linii separate pe variante.** Aceleași preparat + opțiuni + notă se unesc pe
  o linie; note diferite dau linii diferite. O notă care descrie două bucăți
  („una cu usturoi, a doua cu tzatziki") pe o linie cu cantitate > 1 e refuzată,
  cu instrucțiunea de a trimite un rând per variantă.
- **Bonul de bucătărie.** `notes` trece printr-un filtru care scoate comentariul
  modelului despre sine („Observație: ingredientele sunt preluate din solicitarea
  clientului; meniu confirmat doar pentru lipie") și păstrează doar instrucțiunile
  pentru bucătărie.
- **Confirmarea.** `place_order` refuză cu `review_required` dacă coșul s-a
  modificat de la ultimul `review_order` — `metadata.cart_version` vs
  `reviewed_version`. Promptul cerea confirmarea și modelul a sărit peste ea la
  apelul 140; acum nu mai are cum.
- **Numărul comenzii e per local.** Coloană `order_number` cu index unic parțial
  pe `(bot_id, order_number)`, atribuit la plasare, backfill făcut: primele două
  comenzi Urban Doner sunt acum `0001` și `0002`, nu `00015`/`00016`.
- **Blocul de comandă merge pe ambele canale.** `OrderingPromptContext` e injectat
  și în `ChatPromptAssembler`, nu doar în `RealtimeSession`. Mai important:
  regulile de comandă au fost mutate din `niches.prompt_addon` în blocul injectat,
  fiindcă addon-ul se copiază în `bots.system_prompt` la creare și **niciodată
  după** — botul 79 n-avea nici măcar regula „nu calcula sume".
- **Promptul de transcriere halucinat înapoi** ca replică a clientului e detectat
  (`WhisperHallucinationFilter::isPromptEcho`) și aruncat înainte să ajungă în
  transcript, rezumate sau scoring.
- **Potrivirea zonei de livrare** se face pe cuvânt întreg, cu cel mai lung nume
  câștigător; înainte un fragment de două litere („la") se potrivea în „Gherla".

Verificat pe botul 79 printr-o comandă de probă rulată cap-coadă (adăugare,
unire, refuz de linie mixtă, poartă de confirmare, nume respins, plasare) și
ștearsă după.
