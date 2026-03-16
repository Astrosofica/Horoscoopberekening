# TODO - Astrologische Tijd Calculator

## Voltooid

- [x] **functions.php verplaatsen naar src/Helpers/**
  - Verplaatst naar `src/Helpers/Formatter.php` als class
  - Alle `require_once` paths geüpdatet
  - Namespace `Tijd\Helpers` toegepast

- [x] **Glyphs correct tonen**
  - `SymbolGlyph::getSignGlyph()` gebruikt nu longitude ipv index
  - Alle tabellen tonen correcte astrologische glyphs

- [x] **Weergave planetenlijst**
  - Alleen glyph tonen (niet naam + glyph)
  - Retrograde status tonen met glyph (v) of D (direct)

- [x] **Weergave aspecten**
  - Planetennamen vervangen door glyphs

- [x] **Weergave huizen**
  - Cusp 1 t/m Cusp 12 benaming

- [x] **Wheel integratie** (2024-03)
  - `src/Wheel/` map aangemaakt met wheel.php, configuration.php, functions.php
  - `HousePlanetMatcher` class voor planeet-huis bepaling
  - Session-based data doorgeven via session_id in URL
  - Wheel toont nu dynamisch berekende planeten en huizen

- [x] **Formulier uitgebreid** (2024-03)
  - Naam veld toegevoegd (eerste veld, verplicht)
  - Nieuwe indeling: Naam (100%), Datum+Tijd (50%/50%), Plaats (100%)
  - Button tekst: "Horoscoop berekenen"
  - Moderne card styling

- [x] **Vormgeving herzien** (2024-03)
  - CSS in apart bestand `public/css/astro.css`
  - Alle blokken in moderne cards met subtiele tint en border
  - Geboortegegevens: naam, moment, locatie, UTC referentie
  - Nieuwe volgorde: Geboortegegevens → Huizen → Planeten → Radix → Aspecten

---

## Weergave & UI

- [ ] **Glyphs uitbreiden**
  - [ ] Asc en MC glyphs toevoegen aan huizentabel
  - [ ] Noordknoop glyph toevoegen
  - [ ] Optioneel: Pars Fortuna en Chiron glyphs

- [ ] **Formulier uitbreiden**
  - [ ] Huizensysteem selector (meerdere opties?)
  - [ ] Orb instellingen per aspect
  - [ ] Datum/tijd picker met kalender
  - [ ] Locatie autocomplete (Google Places)

- [ ] **Vormgeving verfijnen**
  - [ ] Responsive design voor mobiel
  - [ ] Print-vriendelijke stylesheet (`@media print`)
  - [ ] No-print instellen voor bepaalde delen (formulier, knoppen)
  - [ ] Loading indicator tijdens API calls
  - [ ] Foutmeldingen styled als alerts

---

## Data & Berekeningen

- [ ] **Weergegeven data heroverwegen**
  - [ ] Welke planeten tonen? (alle 10 of selectie)
  - [ ] Extra punten toevoegen:
    - [ ] Zuidknoop
    - [ ] Lilith
    - [ ] Vertex
    - [ ] Arabische punten (behalve Pars Fortuna)
  - [ ] Huistabel uitbreiden met huisheren

- [ ] **Aspecten uitbreiden**
  - [ ] Minor aspects:
    - [ ] Quincunx (150°)
    - [ ] Semisextile (30°)
    - [ ] Quintile (72°)
    - [ ] Biquintile (144°)
    - [ ] Septile (51.43°)
    - [ ] Novile (40°)
  - [ ] Aspectpatronen detecteren:
    - [ ] Groot Driehoek
    - [ ] T-Vierkant
    - [ ] Groot Kruis
    - [ ] Yod
    - [ ] Stellium

- [ ] **Berekeningen controleren**
  - [ ] Huisheren berekenen
  - [ ] Element balans (vuur, aarde, lucht, water)
  - [ ] Kwaliteiten balans (cardinaal, vast, veranderlijk)

---

## Structuur & Organisatie

- [ ] **Composer autoloading instellen**
  - `composer init` voor PSR-4 autoloading
  - Verwijder handmatige `require_once` statements
  - Gebruik `use Tijd\...` statements overal

---

## Techniek

- [ ] **API calls optimaliseren**
  - [ ] Google API caching (voorkom dubbele calls)
  - [ ] Ephemeris data cachen
  - [ ] Rate limiting voor API calls

- [ ] **Error handling verbeteren**
  - [ ] Try-catch blocks rond API calls
  - [ ] Graceful fallbacks
  - [ ] Logging van fouten

- [ ] **Testbaarheid**
  - [ ] Unit tests voor berekeningen
  - [ ] Test data voor bekende geboortes
  - [ ] Validatie van input

---

## Documentatie

- [ ] **Code documentatie**
  - [ ] PHPDoc blocks toevoegen
  - [ ] README.md met installatie instructies
  - [ ] Voorbeelden van gebruik

- [ ] **Font mapping uitbreiden**
  - [ ] `symbols.md` compleet maken met alle glyphs
  - [ ] Visuele referentie kaart maken

---

## Productie

- [ ] **Klaarmaken voor publieke test**
  - [ ] Test op productie server
  - [ ] Performance optimalisatie
  - [ ] Beveiliging checken
  - [ ] Backup strategie

---

## Prioriteit

### Hoog
1. Glyphs uitbreiden (Asc, MC, Noordknoop)
2. Responsive design voor mobiel

### Medium
3. Print-vriendelijke stylesheet
4. Composer autoloading

### Laag
5. Minor aspects
6. Aspectpatronen
7. Element/kwaliteiten balans
