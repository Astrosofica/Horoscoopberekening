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