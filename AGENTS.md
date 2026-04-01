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
   - Maak een debug script dat session data, request info, en variabele waarden toont
   - Check de code flow stap voor stap (waar worden variabelen gezet/overschreven?)
   - Log op cruciale plekken wat er gebeurt
   - **Pas dit eerder toe** - niet uren puzzelen, maar direct debuggen

---

## Laatst Bijgewerkt

2026-04-01 - Na progression events module implementatie en uitgebreide debugging (tab visibility, session marker volgorde, POST/GET flow)