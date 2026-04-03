# Tijd Project - Agent Instructies

Dit bestand wordt automatisch gelezen aan het begin van elke sessie. Update dit na elke sessie met nieuwe inzichten.

---

## DEEL 1: ALGEMENE DEVELOPMENT PRINCIPES

*Deze regels gelden voor elk project en voorkomen veelvoorkomende fouten.*

### 1. Reference Code Gebruik

```
Reference code = inspiratie voor FORMULES en CONCEPTEN
Reference code ≠ implementatie-template
```

- Begrijp de *waarom* voordat je *hoe* kopieert
- Pas aan aan jouw architectuur, kopieer niet blind
- Referenties tonen vaak andere tech stacks (CLI vs FFI, etc.)

**Red flag:** Direct kopiëren van code uit reference bestanden zonder aanpassing.

### 2. Bestaande Architectuur Volgen

```
Bestaande wrapper gebruiken > Directe low-level API aanroepen
```

- Zoek eerst naar bestaande classes/methoden die doen wat je nodig hebt
- Bestaande code is getest en werkt
- Nieuwe code moet consistent zijn met bestaande patterns

**Red flag:** Een methode aanroepen die misschien niet bestaat - verifieer eerst!

### 3. Werkende Code Niet Aanpassen

```
ALS IETS WERKT → AANPASSEN IS EEN RISICO
```

- Pas werkende code alleen aan als het echt nodig is
- Bij nieuwe feature: gebruik bestaande methoden, dupliceer logica niet
- Wijzigingen aan core/generieke code vereisen extra voorzichtigheid

**Red flag:** Een entity of helper aanpassen die door meerdere features wordt gebruikt.

### 4. Consistentie met Werkende Code

```
Zelfde probleem = zelfde oplossing
```

- Als werkende code een probleem al oplost, gebruik dezelfde aanpak
- Niet "beter weten" en nieuwe logica introduceren
- Dit geldt voor: UTC conversie, datum formatting, string parsing, validatie, etc.

**Red flag:** Nieuwe logica introduceren terwijl bestaande code het al doet.

### 5. Incrementeel Testen

```
Test bij logische checkpoints, niet pas aan het einde
```

Checkpoints:
1. Syntax check (`php -l` of equivalent)
2. Unit test (indien mogelijk)
3. Integration test
4. Browser/UI test

**Red flag:** 200 regels code schrijven zonder tussentijds te testen.

### 6. Float/Integer Edge Cases

```
Floats in string-context = onvoorspelbaar
```

- DateTime::modify() met float → kan onverwachte resultaten geven
- Splits floats in integer + fractie
- Test edge cases (negatief, nul, groot, decimaal)

### 7. Methode Bestaan Verifiëren

```
Aanroepen = verifiëren dat het bestaat
```

- Check class interface voordat je een methode aanroept
- Bij twijfel: `grep` of `glob` naar de methode definitie

**Red flag:** Een methode aanroepen op basis van aannemen dat die bestaat.

### 8. Na Elke Sessie: Documenteren

```
Nieuwe inzichten → Toevoegen aan AGENTS.md
Fouten gemaakt → Preventieregel toevoegen
Architectuur duidelijker → Documenteren
```

---

## DEEL 1.5: VERPLICHTE SKILLS

**Bij ELK probleem:** Gebruik `systematic-debugging` skill
- NIET zelf fixes voorstellen
- WEL: skill aanroepen, 4 fasen volgen (Root Cause → Pattern → Hypothesis → Implementation)
- **Had ons vandaag bespaard:** ~2 uur aan verkeerde fixes

**VOOR "done" te claimen:** Gebruik `verification-before-completion` skill
- NIET zeggen "werkt!" zonder tests te runnen
- WEL: test command runnen, output lezen, DAN claimen
- **Iron Law:** "NO COMPLETION CLAIMS WITHOUT FRESH VERIFICATION EVIDENCE"

**BIJ nieuwe features:** Gebruik `brainstorming` skill
- NIET direct code schrijven
- WEL: design voorleggen, approval krijgen
- **Gouden regel:** "Geen implementatie zonder design approval"

**Hoe skills te gebruiken:**
```
skill tool → kies skill → skill wordt geladen → volg instructies
```

Skills kunnen als subagent worden ingezet voor gespecialiseerde taken.

---

## DEEL 2: PROJECT-SPECIFIEKE REGELS

*Deze sectie bevat regels en context specifiek voor het Tijd project.*

### Project Overzicht

Astrologie applicatie met:
- Horoscoop berekeningen (Swiss Ephemeris via FFI)
- Gebruikersauthenticatie
- Horoscoop opslag in database
- Lazy loading voor aspecten en progressies

### Architectuur

```
SwissEphemeris (FFI → libswe.so)
    ↓
PlanetCalculator::calculateForTimestamp($timestamp)
    ↓
HoroscopeCalculator::calculate(Horoscope $horoscope)
```

**Regel:** Gebruik altijd de wrapper classes, nooit directe FFI calls.

### Entities

```
Horoscope
├── getLocalTimestamp(): strtotime(date + time)
├── getUtcTimestamp(): localTimestamp - utcOffset
└── Andere getters voor opgeslagen data
```

**Belangrijk:** UTC conversie gaat via `strtotime() - utcOffset`, niet via DateTime met timezone.

### Session Structuur (Lazy Loading)

```php
$_SESSION['horoscope'] = [
    'input' => [
        'firstname', 'infix', 'lastname',
        'birth_date', 'birth_time',
        'location_name', 'latitude', 'longitude',
        'timezone_id', 'utc_offset'
    ],
    'core' => [
        'planets' => [...],
        'houses' => [...],
        'ascmc' => [...],
        'julian_day' => ...
    ],
    'aspects' => null,      // Lazy loaded
    'progressions' => null, // Lazy loaded
];
```

### Server Configuratie

- **Timezone:** Europe/Berlin (CET)
- **PHP:** Via FFI naar Swiss Ephemeris library
- **Database:** MySQL/SQLite

### Belangrijke Classes & Methoden

| Class | Methode | Gebruik |
|-------|---------|---------|
| Horoscope | `getUtcTimestamp()` | UTC timestamp voor berekeningen |
| SwissEphemeris | `calculateAllPlanets($jd, $iflag)` | Hoofdmethod voor planeten |
| SwissEphemeris | `julianDayFromTimestamp($ts)` | Timestamp → Julian Day |
| SwissEphemeris | `timestampFromJulianDay($jd)` | Julian Day → timestamp |
| PlanetCalculator | `calculateForTimestamp($ts)` | **HOOFDMETHOD** planeetberekeningen |
| HouseCalculator | `calculateByTimestamp(...)` | Huizen berekenen |
| ProgressionCalculator | `calculateSecondaryProgressions(...)` | Progressie berekeningen |

### FFI Specifieke Regels

```php
// String conversie
❌ self::$ffi->string($serr)
✅ \FFI::string($serr)
```

### Timezone/UTC Handling

```php
// Lokale tijd → UTC
$localTimestamp = strtotime($birthDate . ' ' . $birthTime);
$utcTimestamp = $localTimestamp - $utcOffset;

// Niet doen
❌ new DateTime($time, new DateTimeZone('UTC'))  // interpreteert input als UTC
❌ strtotime() op server timezone vertrouwen zonder offset
```

### Bekende Valkuilen

1. **DateTime met UTC zone** - interpreteert input als UTC, niet als lokale tijd
2. **Float in DateTime::modify()** - splitsen in integer + seconden
3. **FFI::string()** - moet met `\FFI::`, niet `self::$ffi->`
4. **Progressie leeftijd** - bereken van actual age (now - birth), niet progression datetime

### Directory Structuur

```
/var/www/html/tijd/
├── public/
│   ├── index.php          # Hoofdpagina met form + resultaten
│   ├── horoscope/
│   │   └── save.php       # Horoscoop opslaan
│   └── includes/
│       └── sidebar.php    # Navigatie tabs
├── src/
│   ├── Calculation/
│   │   ├── PlanetCalculator.php
│   │   ├── HouseCalculator.php
│   │   ├── AspectCalculator.php
│   │   ├── ProgressionCalculator.php
│   │   └── HoroscopeCalculator.php
│   ├── Ephemeris/
│   │   └── SwissEphemeris.php
│   ├── Entity/
│   │   └── Horoscope.php
│   └── Time/
│       └── AstroTime.php
├── config/
│   └── bootstrap.php
└── var/log/
    └── error.log
```

### Workflow Best Practices

1. **Nieuwe berekening?** Check eerst of HoroscopeCalculator of PlanetCalculator al iets biedt
2. **Lazy loading?** Volg het aspecten pattern met session caching
3. **UTC conversie?** Gebruik `strtotime() - utcOffset` zoals Horoscope entity
4. **FFI nodig?** Via SwissEphemeris class, nooit direct
5. **Debuggen bij problemen?** Gebruik systematische debugging:
   - **VERPLICHT:** Gebruik `systematic-debugging` skill (zie DEEL 1.5)
   - Maak een debug script dat session data, request info, en variabele waarden toont
   - Check de code flow stap voor stap (waar worden variabelen gezet/overschreven?)
   - Log op cruciale plekken wat er gebeurt
   - **Pas dit eerder toe** - niet uren puzzelen, maar direct debuggen

---

## DEEL 3: LESSON LEARNED - PROGRESSION EVENTS MODULE (2026-04-01)

*Deze sectie documenteert specifieke problemen en oplossingen van vandaag om herhaling te voorkomen.*

### Het Probleem

Bij het implementeren van de Progression Events module ontstonden meerdere bugs die pas na uitgebreide debugging werden opgelost. Het patroon: **aannames doen zonder te debuggen → verkeerde fixes → meer problemen**.

### Wat Er Mis Ging

**1. Te Snel Aannames Doen**
- Ik nam aan dat de tab HTML correct was → bleek statische `hidden` klasse te hebben
- Ik nam aan dat de session data correct werd doorgegeven → marker werd te vroeg verwijderd
- Ik nam aan dat de browser als GET herlaadde → bleef in POST modus
- **Tijd verloren:** ~2 uur aan verkeerde fixes

**2. Meerdere Fixes Tegelijk**
- Ik paste meerdere dingen tegelijk aan zonder te weten welke het probleem was
- Hierdoor was het onduidelijk welke fix werkte en welke niet
- **Les:** Één probleem per keer, direct testen na elke fix

**3. Code Flow Niet Volledig Gevolgd**
- Ik begreep niet dat viewHoroscope blok de session overschreef
- Ik zag niet dat tab switch logic na viewHoroscope kwam (te laat voor marker)
- **Les:** Teken de code flow uit of gebruik debug logging op elke cruciale stap

**4. Browser Gedrag Genegeerd**
- Ik realiseerde me niet dat browsers POST data onthouden bij refresh
- POST-Redirect-GET pattern was niet toegepast
- **Les:** Bij form handling altijd POST-Redirect-GET gebruiken

### Wat Uiteindelijk Werkte

**Systematische Debugging Aanpak:**

```
1. Debug script gemaakt → session data inspecteren
2. Request info tonen → REQUEST_METHOD, GET, POST
3. Variabelen tonen → $currentTab, $hasResult, $viewHoroscope
4. Code flow volgen → waar wordt wat gezet/overschreven?
5. Één fix per keer → direct testen
```

**Debug Output Die Hielp:**
```
currentTab: progressions-list      ← Tab werd correct gezet
hasResult: FALSE                   ← Maar resultaat was niet berekend!
REQUEST_METHOD: POST               ← Browser bleef in POST!
viewHoroscope: SET                 ← Horoscoop was geladen
progression_events: YES            ← Data bestond in session
just_submitted: YES                ← Marker stond
```

Deze output toonde precies waar het misging: `$hasResult = FALSE` ondats alle andere data correct was.

### Specifieke Fixes Die Nodig Waren

| Probleem | Oorzaak | Oplossing |
|----------|---------|-----------|
| Tab altijd verborgen | Statische `tab-content--hidden` klasse | Dynamische klasse: `<?= $currentTab !== 'progressions-list' ? 'hidden' : '' ?>` |
| Session data verloren | Marker verwijderd vóór tab switch | Tab switch logic vóór viewHoroscope blok plaatsen |
| `$hasResult = false` | `!isset($_POST['...'])` check faalde bij browser resubmit | `$_SERVER['REQUEST_METHOD'] === 'GET'` gebruiken |
| Browser herhaalde POST | Geen redirect na submit | POST-Redirect-GET pattern: `header('Location: ...'); exit;` |
| Results table leeg | `$progEventsResult` niet geladen na redirect | Uit session halen: `$_SESSION['horoscope']['progression_events']['results']` |

### Regels Voor Toekomstige Ontwikkeling

**Bij Nieuwe Features:**
```
✅ Tab HTML altijd dynamisch maken (zoals bestaande tabs)
✅ Session markers gebruiken voor state tussen requests
✅ POST-Redirect-GET bij form submissions
✅ Results uit session laden na redirect
✅ Debug logging toevoegen tijdens ontwikkeling
```

**Bij Problemen:**
```
✅ Eerst debuggen, dan fixen (niet andersom!)
✅ Debug script maken dat ALLE relevante variabelen toont
✅ Code flow stap-voor-stap volgen
✅ Één fix per keer, direct testen
✅ User betrekken bij debugging (debug output delen)
```

**Code Review Checklist:**
```
□ Heeft de tab een dynamische visibility klasse?
□ Worden session markers op het juiste moment gezet/verwijderd?
□ Is er een POST-Redirect-GET bij form handling?
□ Worden resultaten uit session geladen na redirect?
□ Staat tab switch logic vóór blocks die session overschrijven?
```

### Samenvatting

**Gouden Regel:** *"Debug first, fix second"*

Wanneer iets niet werkt:
1. **Stop** met code aanpassen
2. **Maak** een debug script
3. **Inspecteer** session, request, en variabele waarden
4. **Volg** de code flow stap voor stap
5. **Fix** één probleem per keer
6. **Test** direct na elke fix

Deze aanpak had ons ~2 uur kunnen besparen. Gebruik dit bij elk toekomstig probleem.

---

## DEEL 4: SAMENWERKING EN COMMUNICATIE PATRONEN

*Lessen over hoe agent en gebruiker effectief kunnen samenwerken.*

### Communicatie Tijdens Debugging

**Wat Werkte Goed:**
- **User deelde concrete debug output** → agent kon precies zien wat er misging
- **User stelde gerichte vragen** → "Kun je de debug gegevens delen?"
- **User gaf context** → URL, browser gedrag, exacte symptomen
- **Agent vroeg om specifieke info** → niet "werkt het?", maar "wat is REQUEST_METHOD?"

**Wat Kan Beter:**
- **Agent moet EERDER om debug info vragen** → niet pas na 5 verkeerde fixes
- **Agent moet aangeven wat hij denkt** → "Ik denk dat het probleem X is, laten we Y checken"
- **User moet EERDER debuggen voorstellen** → "Kunnen we een debug script maken?"

### Samenwerking Patterns

**Bij Nieuwe Features:**
```
1. User: beschrijft wat er gebouwd moet worden
2. Agent: analyseert referentie bestanden EN bestaande architectuur
3. Agent: stelt design voor met 2-3 opties
4. User: kiest optie en geeft feedback
5. Agent: implementeert in kleine steps
6. User: test tussentijds
```

**Bij Problemen:**
```
1. User: beschrijft symptoom (niet oplossing)
2. Agent: stelt debug plan voor (niet fix!)
3. User: voert debug uit, deelt output
4. Agent: analyseert output, stelt hypothese op
5. User: bevestigt of ontkracht hypothese
6. Agent: maakt gerichte fix
7. User: test fix
```

**Red Flags (Stop en Heroriënteer):**
- Agent past >3 dingen tegelijk aan
- User zegt "het werkt nog steeds niet" >2 keer
- Agent maakt aannames zonder verificatie
- Debug output wordt niet gedeeld
- Er wordt >30 minuten gepuzzeld zonder voortgang

**Green Flags (Goed Gaande):**
- Debug output wordt gedeeld binnen 5 minuten na probleem
- Agent stelt gerichte vragen ("wat is REQUEST_METHOD?")
- User deelt concrete symptomen (niet "het werkt niet")
- Elke fix wordt direct getest
- Fouten worden snel erkend en gecorrigeerd

### Specifiek Voor Dit Project

**Tijd Project Kenmerken:**
- Session-based state management (kritiek!)
- Lazy loading voor zware berekeningen
- POST-Redirect-GET required voor form handling
- Code flow volgorde is essentieel (tab switch → viewHoroscope → result render)

**Wanneer Iets Niet Werkt:**
1. Check session data: `print_r($_SESSION['horoscope'])`
2. Check REQUEST_METHOD: `$_SERVER['REQUEST_METHOD']`
3. Check variabele waarden: `$currentTab`, `$hasResult`, `$viewHoroscope`
4. Traceer code flow: waar wordt wat gezet/overschreven?
5. Gebruik inline debug: `echo` statements in index.php

**Vermijd:**
- Aannames over browser gedrag (test!)
- Aannames over session state (inspecteer!)
- Meerdere fixes tegelijk (één per keer!)
- Lang puzzelen zonder debug (maak debug script!)

### Skills Reference

| Skill | Wanneer | Wat Het Doet |
|-------|---------|--------------|
| `systematic-debugging` | ELKE bug/fout | 4-fasen debug proces, voorkomt verkeerde fixes |
| `verification-before-completion` | VOOR "done" claimen | Run tests, lees output, DAN claimen |
| `brainstorming` | Nieuwe features | Design voorleggen før implementatie |
| `writing-plans` | Na design approval | Gedetailleerd implementation plan |
| `test-driven-development` | Nieuwe classes | Test-first development |
| `subagent-driven-development` | Grote taken | Fresh subagent per taak |

---

## Laatst Bijgewerkt

2026-04-02 - Na progression events UI optimalisaties (compact form, lazy loading fix, glyphs, toggle voor dominante aspecten, responsive CSS fixes)

---

## DEEL 5: LESSON LEARNED - PROGRESSIONS UI OPTIMALISATIES (2026-04-02)

*Deze sectie documenteert UI/UX lessen van de progression events module voor toekomstige modules (transits, midpunten, etc.).*

### Overzicht

Na de werkende implementatie (2026-04-01) volgde een UI optimalisatie sessie met de volgende verbeteringen:
1. Lazy loading fix voor progressies tab
2. Formulier compacter gemaakt (4 kolommen naast elkaar)
3. Quick date knoppen i.p.v. checkboxes
4. Glyphs voor sign/house ingress (♈, ♉, H1, H2)
5. Toggle voor dominante aspecten
6. Responsive CSS fixes

### Wat Ging Goed ✅

#### 1. Systematische Probleemanalyse
- **Patroon herkend:** Lazy loading issue identiek aan aspecten tab
- **Fix:** Zelfde patroon gekopieerd → werkte direct

**Les voor Transits:**
> Gebruik dit lazy loading pattern in `app.js`:
> ```javascript
> if (tabId === 'transits' && pushState) {
>     const url = new URL(window.location.href);
>     const currentTab = url.searchParams.get('tab');
>     if (currentTab !== 'transits') {
>         url.searchParams.set('tab', tabId);
>         window.location.href = url.toString();
>         return;
>     }
> }
> ```

#### 2. Iteratieve Verbeteringen
- **Kleine stapjes:** Eén optimalisatie per keer
- **Direct testen:** Na elke wijziging getest
- **User feedback:** Regelmatig gevraagd of resultaat voldeed

**Les voor Transits:**
> - Breek grote taken in kleine, testbare eenheden
> - Test na elke wijziging voordat je verder gaat
> - Vraag om feedback tussendoor, niet pas aan het einde

#### 3. Bestaande Architectuur Gevolgd
- **Glyph system:** Hergebruikt (`SymbolGlyph::getGlyphForTarget()`)
- **CSS patronen:** Bestaande class naming gevolgd
- **Session structuur:** Lazy loading pattern gevolgd

**Les voor Transits:**
> - Zoek eerst naar bestaande classes/methods
> - Volg bestaande naming conventions (`.transits-*`)
> - Kijk naar session caching voor progressies → zelfde pattern

---

### Problemen en Oplossingen 🔧

#### Probleem 1: Formulier Past Niet Naast Elkaar
**Symptoom:** 4 kolommen wrapten naar 4×1 i.p.v. 2×2 bij 768px

**Root Causes (meerdere issues!):**
1. **Gap mismatch:** `calc(50% - 0.75rem)` i.p.v. `calc(50% - 0.25rem)`
2. **Card padding conflict:** `.card--large` (2rem) vs `.card--progression-events` (1.25rem)
3. **CSS specificiteit:** `.card--large` won van `.card--progression-events`

**Fixes:**
```css
/* 1. Gap formule gecorrigeerd */
@media (max-width: 768px) {
    .progression-column {
        flex: 1 1 calc(50% - 0.25rem); /* Niet 0.75rem! */
    }
}

/* 2. Specificiteit verhoogd */
.card.card--large.card--progression-events {
    padding: 1.25rem !important;
}
```

**Les voor Transits:**
> ⚠️ **CSS Valkuilen:**
> - Gap formules: Gebruik `calc(50% - (gap / 2))` niet `calc(50% - gap)`
> - Card padding: Check of meerdere classes conflicteren
> - Specificiteit: Gebruik `.card.card--large.card--transits`
> - Test responsive bij meerdere breakpoints (900px, 768px, 600px)

---

#### Probleem 2: Toggle Verkeerde Sectie
**Symptoom:** Toggle verscheen bij planeten/huizen i.p.v. aspecten

**Root Cause:** Edit ging over meerdere secties heen - verkeerde closing tags

**Les voor Transits:**
> ⚠️ **HTML Editing Risico's:**
> - Lees volledige sectie voordat je edit
> - Check opening/closing tags van omringende elementen
> - Gebruik unieke class names voor containers
> - Test na edit of andere secties nog intact zijn

---

#### Probleem 3: Huizen Tabel Verdwijnt
**Symptoom:** `card--houses` volledig verwijderd uit template

**Root Cause:** Te grote edit in één keer

**Les voor Transits:**
> ⚠️ **Template Editing:**
> - Maak kleine, gerichte edits (maximaal 20-30 regels)
> - Check na edit of alle secties nog bestaan
> - Gebruik `git diff` om per ongeluk verwijderde code te zien
> - Backup belangrijke secties voordat je grote refactors doet

---

#### Probleem 4: CSS Background Kleur Werkt Niet
**Symptoom:** Toggle veranderde hover kleur, maar niet row achtergrond

**Root Cause:**
```css
/* Werkt niet goed in browsers - background op <tr> wordt genegeerd */
.table--dominant-highlight .row--dominant {
    background-color: #ffebee;
}
```

**Fix:**
```css
/* Moet op <td> elementen */
.card--aspects .table--dominant-highlight .row--dominant td {
    background-color: #ffcdd2 !important;
}
```

**Les voor Transits:**
> ⚠️ **CSS Browser Quirks:**
> - `background-color` op `<tr>` werkt niet consistent
> - Altijd op `<td>` elementen toepassen
> - Verhoog specificiteit met `.card--* .table--* .row--* td`
> - Gebruik `!important` als laatste redmiddel (wel documenteren!)

---

### Patterns Voor Toekomstige Modules 📝

#### 1. Session Structuur Template

```php
// POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_module'])) {
    // ... validatie ...
    
    $calculator = new ModuleCalculator();
    $results = $calculator->calculate(/* ... */);
    
    $_SESSION['horoscope']['module'] = [
        'input' => [ /* ... */ ],
        'results' => $results,
    ];
    
    // Redirect om browser resubmit te voorkomen
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
```

#### 2. Lazy Loading Template

```php
// Tab switch logic
case 'module':
    if (isset($_SESSION['horoscope']['core'])) {
        if (!isset($_SESSION['horoscope']['module'])) {
            // Bereken voor vandaag
            $_SESSION['horoscope']['module'] = $calculator->calculateForToday();
        }
        $result['module'] = $_SESSION['horoscope']['module'];
        $currentTab = 'module';
    }
    break;
```

#### 3. UI Component Template

```php
<!-- Formulier met compacte kolommen -->
<div class="module-form">
    <div class="module-column module-column--tijdvak">
        <h4>Tijdvak</h4>
        <!-- ... -->
    </div>
    <!-- Meer kolommen -->
    <button type="submit" class="module-submit">Bereken</button>
</div>

<!-- Resultaten tabel met glyphs -->
<?php if (isset($results) && count($results) > 0): ?>
<table>
    <?php foreach ($results as $event): ?>
    <tr>
        <td><span class="astro-glyph"><?= SymbolGlyph::getGlyphForTarget($event['index']) ?></span></td>
        <!-- ... -->
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
```

---

### Checklist Voor Transits ✅

#### Voor Implementatie
- [ ] Referentie code verzamelen (Svelte + PHP)
- [ ] TransitCalculator class aanmaken (of bestaat die al?)
- [ ] Session structuur definiëren (input + results)
- [ ] UI design schetsen (welke kolommen? welke filters?)

#### Tijdens Implementatie
- [ ] POST handler met redirect pattern
- [ ] Lazy loading in `app.js` en `index.php`
- [ ] Session caching voor results
- [ ] Formulier met compacte kolommen (4 naast elkaar)
- [ ] Resultaten tabel met glyphs (niet lange tekst)
- [ ] Toggle voor belangrijke transits (optioneel)

#### Na Implementatie
- [ ] Test bij verschillende viewport breedtes (>900px, 768px, 600px)
- [ ] Test page reload (blijft state behouden?)
- [ ] Test browser back/forward navigation
- [ ] Test met grote datasets (100+ transits)
- [ ] PHP syntax check (`php -l`)
- [ ] Git commit met duidelijke beschrijving

---

### Valkuilen Om Te Vermijden ⚠️

| Valkuil | Oplossing |
|---------|-----------|
| **Te grote edits in één keer** | Maximaal 20-30 regels per edit, direct testen |
| **CSS specificiteit onderschatten** | Gebruik `.card.card--large.card--transits` patroon |
| **Gap formules verkeerd** | `calc(50% - (gap / 2))` testen in browser |
| **Background op `<tr>`** | Altijd op `<td>` toepassen |
| **Verkeerde sectie editten** | Check opening/closing tags |
| **Geen responsive test** | Test bij 3 breakpoints voordat je "done" claimt |
| **Session state niet persistent** | Gebruik POST-Redirect-GET + session caching |

---

### Golden Rules 🏆

1. **Test na elke wijziging** - Niet 5 edits achter elkaar zonder test
2. **Volg bestaande patronen** - Copy-paste van progressies is beter dan "nieuw en beter"
3. **Kleine stapjes** - 10 kleine commits is beter dan 1 grote
4. **Documenteer while you go** - Schrijf lessons live op
5. **User feedback vroeg** - Vraag "past dit?" voordat je verder optimaliseert
6. **CSS eerst in browser** - Gebruik dev tools om kleuren/selectors te testen
7. **Git commit bij milestones** - Elke werkende feature = commit

---

### Verwachtingen Voor Transits Module

**Wat hetzelfde is als progressions:**
- Session structuur (input + results)
- Lazy loading pattern
- Formulier met 4 kolommen
- Glyphs voor tekens/huizen
- Responsive CSS breakpoints

**Wat anders kan zijn:**
- Complexere berekeningen (meer planeten/aspecten?)
- Meer data → performance overwegingen
- Andere tijdvakken (transits kunnen jaren beslaan)

**Grootste risico's:**
1. Te complexe berekeningen in één keer
2. Performance issues bij grote datasets
3. Verkeerde verwachtingen over welke transits getoond moeten worden

**Mitigatie:**
- Begin met simpele implementatie (alleen conjuncties)
- Test met realistische datasets (1000+ events)
- Vraag vroeg om feedback over welke transits relevant zijn

---

## DEEL 6: LESSON LEARNED - SPIEGELPUNTEN (ANTISCIA) MODULE (2026-04-02)

*Deze sectie documenteert lessen van de spiegelpunten module implementatie.*

### Wat Ging Goed ✅

#### 1. Systematic Debugging Skill Ingezet
**Probleem:** Antiscia container brak uit card bounds, horizontale scroll bar.

**Aanpak:** `systematic-debugging` skill gebruikt na meerdere gefaalde fixes.

**Resultaat:** Root cause gevonden in 15 minuten (na 30+ minuten gefaald proberen).

**Les:** 
> ✅ **Bij CSS layout problemen:** Gebruik systematic-debugging skill EERDER
> ✅ **Niet door gaan met "quick fixes"** na 2-3 mislukkingen
> ✅ **Debug script maken** dat CSS regels en berekeningen toont

---

#### 2. Root Cause Gevonden: Nested Card Padding
**Probleem:**
```html
<!-- FOUT: dubbele padding! -->
<div class="card card--large">                    <!-- 2rem padding -->
    <div class="card card--antiscia-points">     <!-- 0.5rem padding -->
        <table>...</table>
    </div>
</div>
```

**Oplossing:**
```html
<!-- CORRECT: geen nested cards -->
<div class="card card--large card--antiscia">    <!-- 1rem padding -->
    <div class="antiscia-column--points">        <!-- 0.5rem padding -->
        <table>...</table>
    </div>
</div>
```

**Les:**
> ⚠️ **HTML structuur check:** Geen nested `.card` elementen tenzij expliciet bedoeld
> ⚠️ **CSS specificiteit:** `.card.card--large.card--antiscia` voor overrides
> ⚠️ **Padding stacking:** Meet totale padding (buiten + binnen) voordat je nested cards gebruikt

---

### Problemen en Oplossingen 🔧

#### Probleem 1: Pars Fortuna Glyph Toont "?"
**Symptoom:** Pars Fortuna rij toont "?" i.p.v. "|" glyph

**Root Cause:**
```php
// getPlanetGlyphByIndex() had geen mapping voor index 14
$mapping = [
    0 => self::PLANET_SUN,
    // ...
    12 => self::PLANET_MC,
    // 14 => self::PLANET_PARS_FORTUNA, ← ONTBRAK!
];
```

**Fix:**
```php
$mapping = [
    // ...
    12 => self::PLANET_MC,
    14 => self::PLANET_PARS_FORTUNA,  ← TOEGEVOEGD
];
```

**Les:**
> ✅ **Glyph mapping compleet:** Check alle indices die je gebruikt
> ✅ **Test met alle data:** Pars Fortuna (index 14) wordt vergeten in tests
> ✅ **SymbolGlyph class:** Heeft speciale handling voor index 14 nodig

---

#### Probleem 2: Aspect Tabel Kolommen Te Breed
**Symptoom:** Orb kolom nam ~50% van tabelbreedte, paste niet in card

**Gefaalde Fixes:**
1. `width: 1%` trick → werkte niet
2. `table-layout: fixed` → maakte het erger
3. Kolom breedtes verkleinen → hielp beetje

**Root Cause:**
- `text-right` class op `<td>` element
- Gecombineerd met `width: 100%` op tabel
- Browser rekt laatste kolom op

**Fix:**
```css
/* Verwijder text-right class van HTML */
<td class="text-right"> → <td>

/* Gebruik nth-child voor specifieke kolommen */
.antiscia-column--aspects td:nth-child(4) {
    text-align: right;
    white-space: nowrap;
    width: 1%; /* Forceer minimale breedte */
}
```

**Les:**
> ⚠️ **`text-right` class:** Veroorzaakt breedte issues in tabellen
> ⚠️ **`width: 1%` trick:** Werkt alleen met `white-space: nowrap`
> ⚠️ **CSS specificiteit:** Gebruik `td:nth-child(4)` i.p.v. algemene class

---

#### Probleem 3: Even Row Background Terug
**Symptoom:** Na CSS refactor hadden rijen weer even/oneven achtergronden

**Root Cause:**
- CSS bestand had duplicate regels
- `tr:nth-child(even)` stond er 2× in
- Edit verwijderde maar 1 instantie

**Fix:**
```bash
# CSS bestand volledig herschrijven i.p.v. edits
cat > _antiscia.css << 'EOF'
/* Complete nieuwe versie */
EOF
```

**Les:**
> ⚠️ **CSS duplicate regels:** Gebruik `grep` om duplicates te vinden
> ⚠️ **Grote refactors:** Herschrijf hele bestand i.p.v. multiple edits
> ⚠️ **Test na elke edit:** Check of andere regels niet verdwijnen

---

### Patterns Voor Toekomstige Modules 📝

#### 1. Progressies Leeftijd Formatter
**Nieuwe formatter methode:**
```php
// Formatter::formatProgressAge(62.71)
// → "62 jaar, 8 maanden, 21 dagen (62.71)"

public static function formatProgressAge(float $progressDays): string
{
    $years = (int) floor($progressDays);
    $months = (int) floor(($progressDays - $years) * 12);
    $days = (int) round((($progressDays - $years) * 12 - $months) * 30);
    
    return sprintf("%d jaar, %d maanden, %d dagen", $years, $months, $days);
}
```

**Les:**
> ✅ **Menselijke format:** Decimale dagen → jaren/maanden/dagen
> ✅ **Subtiele hint:** Toon decimale waarde tussen haakjes
> ✅ **Herbruikbaar:** Deze formatter werkt ook voor transits!

---

#### 2. Wheel Caching Overwegingen
**Probleem:**
- Wheel wordt elke keer gegenereerd (GD library)
- Zichtbare laadtijd
- Slug verandert bij edit → cache nutteloos

**Oplossing (voor later):**
```php
// Data-hash i.p.v. slug
$wheelHash = substr(md5(json_encode([
    $horoscope->getBirthDate(),
    $horoscope->getBirthTime(),
    $horoscope->getLatitude(),
    $horoscope->getLongitude(),
])), 0, 12);

// URL: <img src="./Wheel/wheel.php?hash=<?= $wheelHash ?>">
// Browser cached 7 dagen met: Cache-Control: max-age=604800, immutable
```

**Les:**
> ⚠️ **Slug ≠ cache key:** Slug verandert bij edit, data-hash niet
> ⚠️ **Browser caching:** Gebruik query parameter + immutable header
> ⚠️ **File caching (optioneel):** Kan later toegevoegd worden

---

### Checklist Voor Volgende Module (Transits?) ✅

#### Voor Implementatie
- [ ] Glyph mappings checken (alle indices ondersteund?)
- [ ] Formatter methodes hergebruiken (formatProgressAge, formatOrb)
- [ ] HTML structuur: géén nested cards!
- [ ] CSS bestand: compleet herschrijven i.p.v. multiple edits

#### Tijdens Implementatie
- [ ] Systematic-debugging skill bij eerste CSS probleem
- [ ] Debug script voor layout issues (padding, width berekeningen)
- [ ] Test met alle data types (ook edge cases zoals Pars Fortuna)
- [ ] Tabel kolommen: `nth-child()` selectors i.p.v. `text-right` class

#### Na Implementatie
- [ ] Test responsive bij 3 breakpoints (>900px, 768px, 600px)
- [ ] Test page reload (blijft layout behouden?)
- [ ] PHP syntax check (`php -l`)
- [ ] Git commit met duidelijke beschrijving

---

### Valkuilen Om Te Vermijden ⚠️

| Valkuil | Oplossing |
|---------|-----------|
| **Nested cards** | Gebruik `.antiscia-column` i.p.v. `.card` binnen cards |
| **`text-right` class op `<td>`** | Gebruik `td:nth-child(4) { text-align: right }` |
| **CSS duplicate regels** | Herschrijf hele bestand, niet multiple edits |
| **Glyph mapping vergeten** | Test met ALLE planeten/punten (ook Pars Fortuna!) |
| **Table width: 100% + flex** | Gebruik `table-layout: auto` voor naturale column widths |
| **Te vroeg optimaliseren** | Start met browser caching, file caching kan later |

---

### Golden Rules 🏆

1. **Geen nested cards** - Tenzij expliciet bedoeld, veroorzaakt padding stacking
2. **Systematic debugging bij CSS** - Niet gissen, root cause vinden
3. **Test glyph mappings** - Alle indices (0-14) moeten werken
4. **CSS herschrijven > edits** - Voorkomt duplicates en conflicts
5. **Browser caching eerst** - File caching kan later als het moet
6. **Data-hash i.p.v. slug** - Voor cache persistence bij edits

---

## Laatst Bijgewerkt

2026-04-03 - Na midpunten module (3 tabs: per planeet, per teken, boompjes), sidebar reorganisatie met submenu groups

---

## DEEL 7: LESSON LEARNED - MIDPUNTEN MODULE (2026-04-03)

*Deze sectie documenteert lessen van de midpunten module implementatie.*

### Het 360-0 Probleem (Aries/Pisces Boundary)

**Probleem:** Midpuntberekening tussen Ram/Vissen overgang gaf foute waarden in originele reference code.

**Root Cause:** Reference code (Svelte bestanden) had foutieve logica:
```javascript
// FOUTIEF - reference code
if (graad > 180) {
    graad = graad / 2 + pos1;  // ← FOUT: telt op bij verkeerde positie
}
```

**Correcte formule (PHP):**
```php
private function calculateMidpoint(float $pos1, float $pos2): float
{
    $pos1 = fmod($pos1, 360);
    $pos2 = fmod($pos2, 360);
    if ($pos1 < 0) $pos1 += 360;
    if ($pos2 < 0) $pos2 += 360;
    
    $diff = abs($pos1 - $pos2);
    
    if ($diff > 180) {
        return fmod(($pos1 + $pos2) / 2 + 180, 360);  // Midpunt aan overkant
    }
    
    return ($pos1 + $pos2) / 2;
}
```

**Test case:** Ram 5° / Vissen 355° → moet Ram 0° zijn

**Les:** 
> ✅ **Reference code kritisch lezen** - Formules begrijpen, niet blind kopiëren
> ✅ **Edge cases identificeren** - 360-0 overgangen expliciet testen
> ✅ **User's historische data gebruiken** - Zij hadden de bug eerder gevonden

---

### Noordknoop Placeholder Bug

**Probleem:** Noordknoop op 0.0 gezet als placeholder → foute aspecten in boompjes (0° Ram = foute conjuncties).

**Root Cause:**
```php
// FOUT: placeholder waarde
$positions[10] = 0.0;  // ← Noordknoop heeft échte positie!
```

**Fix:**
```php
// CORRECT: uit radix data halen
$positions[10] = isset($radixData['planets']['NorthNode'])
    ? (float) $radixData['planets']['NorthNode']['longitude']
    : 0.0;
```

**Les:**
> ⚠️ **Geen placeholders voor bestaande data** - Altijd uit session/radix halen
> ⚠️ **Debug met echte data** - Noordknoop positie checken in debug output

---

### Boompjes Sortering (Iteratief Proces)

**Drie iteraties nodig:**

1. **Eerst:** Sorteren op absolute orb (kleinste boven) → `**` bij eerste
2. **Dan:** Sorteren van negatief naar positief → `**` bij kleinste absolute
3. **Uiteindelijk:** Sorteren van positief naar negatief (zoals reference)

**Reference code analyse:**
```javascript
// Apart sorteren: signed orb én absolute orb
mp_o1.sort((a, b) => a - b);  // Signed: -1, -0.5, +0.5, +1
mp_o2.sort((a, b) => a - b);  // Absolute: 0.5, 0.5, 1, 1

// Tonen van grootste naar kleinste (meest exact onder)
for (valloop = element; valloop >= 0; valloop--)
```

**Definitieve PHP implementatie:**
```php
// Sorteer van positief naar negatief
usort($foundAspects, function($a, $b) {
    return $b['orb'] <=> $a['orb'];
});

// Vind kleinste absolute orb voor ** markering
$exactIndex = array_search(min(array_map(fn($a) => abs($a['orb']), $foundAspects)), ...);
```

**Les:**
> ✅ **Reference code volledig lezen** - Complexe sorteerlogica met 2 arrays
> ✅ **Iteratief verfijnen** - Eerste versie was niet fout, maar kon beter
> ✅ **User feedback** - Zij herkende de reference output beter

---

### Sidebar Submenu Pattern

**Nieuw pattern voor gegroepeerde tabs:**

```php
// 1. Groep definiëren
$midpointsGroup = [
    'midpoints-planet' => 'per planeet',
    'midpoints-sign' => 'per teken',
    'midpoints-tree' => 'boompjes',
];

// 2. Template met header
<div class="sidebar__section">
    <div class="sidebar__section-title">Midpunten</div>
    <?php foreach ($midpointsGroup as $id => $label): ?>
        <a class="sidebar__item sidebar__item--indent">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

// 3. CSS voor inspringen + prefix
.sidebar__item--indent {
    font-size: 0.9em;
}
.sidebar__item--indent::before {
    content: "—";
    opacity: 0.6;
    margin-right: 0.25rem;
}
```

**Les:**
> ✅ **Visuele hiërarchie** - Headers + inspringen voor sub-items
> ✅ **CSS ::before pseudo-element** - Voor em-dash prefix zonder HTML clutter
> ✅ **Herbruikbaar pattern** - Ook gebruikt voor Progressies groep

---

### Session Data Debugging

**Probleem tijdens development:** Midpoints tab toonde "Geen data" ondats horoscoop geladen.

**Debug aanpak:**
```php
// Debug script gemaakt om session te inspecteren
echo "core exists: " . (isset($_SESSION['horoscope']['core']) ? 'YES' : 'NO');
echo "midpoints exists: " . (isset($_SESSION['horoscope']['midpoints']) ? 'YES' : 'NO');

// Composer autoloader vergeten!
require_once __DIR__ . '/../vendor/autoload.php';
```

**Root cause:** 
1. Composer autoloader niet meegenomen in debug script
2. Index 10 (NorthNode) niet gevuld in `extractPlanetPositions()`
3. Loop ging tot `TOTAL_POINTS` (13) maar array had alleen 0-9, 11-12

**Les:**
> ✅ **Debug scripts met bootstrap** - Altijd autoloader includen
> ✅ **Array indices compleet** - Check alle indices (0-12) worden gevuld
> ✅ **Composer dump-autoload** - Nieuwe classes registreren

---

### Valkuilen Om Te Vermijden ⚠️

| Valkuil | Oplossing |
|---------|-----------|
| **Reference code blind kopiëren** | Formules begrijpen, 360-0 edge case testen |
| **Placeholder waarden gebruiken** | Altijd uit session/radix data halen |
| **Sorteervolgorde aannemen** | Reference output vergelijken, iteratief verfijnen |
| **Composer autoloader vergeten** | `composer dump-autoload` na nieuwe classes |
| **Array indices incompleet** | Check alle indices (0-12) worden gevuld |

---

### Golden Rules 🏆

1. **Reference code ≠ implementatie** - Begrijp formules, pas aan aan architectuur
2. **Test 360-0 edge cases** - Ram/Vissen overgang expliciet checken
3. **Geen placeholder waarden** - Bestaande data uit session halen
4. **Sortering iteratief verfijnen** - Eerste versie is zelden perfect
5. **Debug met bootstrap** - Altijd autoloader + session meenemen
6. **Composer autoload vergeten?** → `composer dump-autoload`
7. **Sidebar groups** - Herbruikbaar pattern voor submenu's

---

### Checklist Voor Volgende Module (Transits?) ✅

#### Voor Implementatie
- [ ] Referentie code volledig lezen (formules + sortering)
- [ ] 360-0 edge cases identificeren
- [ ] Session structuur definiëren (input + results)
- [ ] Sidebar group pattern overwegen (meerdere tabs?)

#### Tijdens Implementatie
- [ ] Composer autoload checken (`composer dump-autoload`)
- [ ] Array indices compleet (0-12 voor alle planeten)
- [ ] Sortering vergelijken met reference output
- [ ] Lazy loading pattern volgen (zoals midpunten)

#### Na Implementatie
- [ ] Test met edge case horoscopen (Ram/Vissen overgangen)
- [ ] Test alle planeten (ook NorthNode, Chiron, Pars Fortuna)
- [ ] PHP syntax check (`php -l`)
- [ ] Git commit met duidelijke beschrijving

---