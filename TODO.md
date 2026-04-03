# TODO - Astrologische Tijd Calculator

## Foundation

- [x] **Composer autoloading** (2026-03)
  - PSR-4 autoloading ingesteld
  - `require_once` statements verwijderd uit alle public files
  - `vendor/autoload.php` in gebruik

- [x] **Bootstrap consolidation** (2026-03)
  - `config/bootstrap.php` aangemaakt
  - Session + security headers + env loading in één bestand
  - ~130 regels duplicatie verwijderd

- [x] **Security audit** (2026-03)
  - CSRF protection op alle forms
  - Rate limiting op auth endpoints
  - Security headers (X-Frame-Options, etc.)
  - Session fixation vulnerability verwijderd

---

## UI / Weergave

- [ ] **Responsive design**
  - Mobiel-vriendelijke layout
  - Formulier aanpassen voor kleine schermen

- [ ] **Print stylesheet**
  - `@media print` regels
  - Formulier en knoppen verbergen

---

## Code Quality

- [ ] **Error handling**
  - Try-catch rond API calls
  - Graceful fallbacks

- [ ] **Input validation**
  - Date/time format validatie
  - Name length limits

---

## Toekomst

- Astrologische functies uitbreiden (minor aspects, aspect patterns, etc.)
- Documentatie (README.md, PHPDoc)
- Unit tests

---

## Wheel Afbeelding Caching

**Module:** Spiegelpunten (Antiscia) + Alle andere tabs met wheel  
**Prioriteit:** Medium  
**Status:** ⏳ Uitgesteld (performance acceptabel voor low-traffic)  
**Impact:** Zichtbare laadtijd bij eerste load (~1-2 seconden)

### Probleem
- Wheel wordt elke keer gegenereerd (GD library, 475 regels code)
- Geen caching → CPU usage bij elke page load
- User ziet afbeelding laden

### Oplossing (Fase 1)
Browser caching met data-hash query parameter:
1. Hash genereren op basis van geboorte data (niet slug!)
   ```php
   $wheelHash = substr(md5(json_encode([
       $result['birth_date'],
       $result['birth_time'],
       $result['latitude'],
       $result['longitude'],
   ])), 0, 12);
   ```
2. URL: `<img src="./Wheel/wheel.php?hash=<?= $wheelHash ?>">`
3. Headers in wheel.php:
   ```php
   header('Cache-Control: public, max-age=604800, immutable');
   header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');
   ```

### Wanneer Implementeren?
- [ ] Users klagen over trage laadtijd
- [ ] CPU usage >50% op piekmomenten
- [ ] >100 gelijktijdige users per dag
- [ ] OF: gewoon voor optimalisatie (betere UX)

### Geschatte Impact
- **Eerste load:** 1x GD berekening (huidig: elke load)
- **Daarna:** Browser cache (7 dagen)
- **CPU reductie:** ~95% minder GD calls
- **Storage:** 0 bytes (geen file cache nodig)

### Waarom Data-Hash I.P.V. Slug?
- Slug verandert bij edit/replace flow
- Data-hash blijft gelijk bij zelfde geboortegegevens
- Zelfde horoscoop = zelfde hash = cache hit ✅

### Fase 2 (Later, Optioneel)
File caching voor server-side caching:
- Cache files opslaan in `/Wheel/cache/`
- Cleanup cron job (verwijder files >30 dagen)
- Alleen nodig bij >1000 users/dag

### Referenties
- AGENTS.md DEEL 6 - Wheel Caching Overwegingen
- `/var/www/html/tijd/public/Wheel/wheel.php` (regel 38-40: expire headers)