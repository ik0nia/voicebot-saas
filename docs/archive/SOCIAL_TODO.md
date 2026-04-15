# Social Admin — Status & TODO

## ✅ Etapa 1 — DONE (deployat live)

- [x] Migration `social_rejections` creată și rulată
- [x] Model `App\Models\SocialRejection` cu `buildAvoidancePrompt()`
- [x] Controller: metode noi `show`, `regenerateImage`, `regenerateText`, `reject`
- [x] Rute noi sub `admin.social.*`
- [x] `index.blade.php` rescris: thumbnails, click pe rând → modal full-screen
- [x] Modal: imagine mare, text, hashtag-uri, status, link extern (dacă există)
- [x] Butoane în modal (doar pe draft/scheduled): regenerare imagine, regenerare text, refuz cu chip-uri + textarea
- [x] `GenerateDailyBatch` injectează `SocialRejection::buildAvoidancePrompt()` în prompt-urile text + image
- [x] Filtre rapide pe stat cards (click → filtrează după status)
- [x] Caches clear

**Test manual de făcut în browser:**
1. Deschide https://sambla.ro/admin/social — verifică că vezi thumbnail-uri
2. Click pe o postare scheduled — verifică modalul, imaginea mare, textul
3. Click "Imagine nouă" — verifică că se generează alta
4. Click "Text nou" — verifică că se schimbă
5. Click "Refuză", alege un chip, scrie ceva, "Confirmă" — verifică că se șterge
6. Generează un nou batch (`php artisan social:generate-batch 2 --dry-run`) — verifică în logs că prompt-ul include "AVOID" cu feedback-ul

## ✅ Etapa 2 — DONE (PWA)

- [x] `public/manifest.json` (start_url=/admin/social, display=standalone, theme #dc2626)
- [x] Icon: reutilizat `public/images/logo-icon.png` (512x512) pentru 192/512/maskable + apple-touch-icon
- [x] `layouts/admin.blade.php` — meta tags PWA (manifest, theme-color, apple-mobile-web-app-*)
- [x] `public/sw.js` — cache-first pentru `/build/`, `/images/`, `/icons/`, favicon, manifest; network-first cu fallback pentru navigări `/admin/social`
- [x] Înregistrare SW la final de `layouts/admin.blade.php`
- [x] `public/offline.html` — pagină offline simplă cu buton reload

**De făcut după deploy:**
1. `php artisan view:clear` (în container app) ca Blade-ul actualizat să fie servit
2. Pe mobil: deschide https://sambla.ro/admin/social → meniu Safari/Chrome → "Add to Home Screen"
3. Verifică că iconița apare corect și că app-ul pornește în standalone (fără bara browser-ului)
4. Pune telefonul în mod avion → deschide app-ul → verifică pagina offline

## 📝 Idei viitoare (nu acum)

- [ ] Push notifications când postare eșuează
- [ ] Sortare/căutare în listă
- [ ] Bulk actions (selectează mai multe → publică / șterge)
- [ ] Drag-and-drop pentru reprogramare
- [ ] Dashboard cu rejection insights ("ultimele 10 motive de refuz")

## 🔧 Cum reiei conversația

```bash
# 1. Pornește Claude Code în /root
cd /root
claude

# 2. Spune-i:
# "continua de la SOCIAL_TODO.md din /var/www/voicebot-saas, începe Etapa 2 (PWA)"
```

Sau dacă vrei să testez doar Etapa 1 împreună cu tine:
```
"verifică /admin/social pe sambla.ro și ajută-mă să testez modal-ul"
```

## 📂 Fișiere atinse în Etapa 1

- `database/migrations/2026_04_06_080000_create_social_rejections_table.php` (nou)
- `app/Models/SocialRejection.php` (nou)
- `app/Http/Controllers/Admin/AdminSocialController.php` (modificat)
- `app/Console/Commands/GenerateDailyBatch.php` (modificat)
- `routes/web.php` (rute noi)
- `resources/views/admin/social/index.blade.php` (rescris)
