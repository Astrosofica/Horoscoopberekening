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

2026-04-01 - Na progression events module implementatie en uitgebreide debugging (tab visibility, session marker volgorde, POST/GET flow)