# Progression Events Module Design

Date: 2026-03-31
Status: Approved

## Overview

Nieuwe module voor berekening van progressieve events over een tijdsperiode:
- Aspecten tussen progressieve planeten en radix targets
- Huis-ingress (progressief planeet enters radix huis)
- Teken-ingress (progressief planeet enters teken)
- Retrograde/Direct transitie momenten

## Architecture

### Components

1. **ProgressionEventCalculator** (src/Calculation/)
   - Input: radix data, user selections, date range
   - Output: chronological array of events
   - Uses existing wrappers (PlanetCalculator, HouseCalculator)

2. **Tab in index.php**
   - New "progressions-list" tab with form
   - POST handler for form submission
   - Session caching for results

3. **Frontend**
   - Checkbox grid: progressive planets (0-9), radix targets (0-12, 20-31, 40-51), aspects
   - Date inputs with quick-select options
   - Ingress checkboxes
   - Results table: date, direction, glyphs, positions

### Data Flow

User form → POST to index.php → ProgressionEventCalculator → Session → Tab display

## Class Design

### ProgressionEventCalculator

```php
public function calculateEvents(
    array $radixData,              // planets, houses, ascmc from session
    array $progressivePlanetIndices, // [0,1,2,...] Sun,Moon,Mercury...
    array $radixTargetIndices,     // [0-12 planets, 20-31 signs, 40-51 houses]
    array $aspectDegrees,          // [0,45,60,90,120,135,150,180]
    int $startTimestamp,
    int $endTimestamp,
    bool $includeHouseIngress,
    bool $includeSignIngress,
    float $latitude,
    float $longitude,
    int $birthUtcTimestamp,
    int $utcOffset
): array;
```

**Return structure:**
```php
[
    [
        'timestamp' => 1234567890,
        'date' => '2025-03-15',
        'progressive_planet' => 'Sun',
        'progressive_index' => 0,
        'direction' => 'D',  // D=direct, R=retrograde, S=stationary
        'aspect' => 90,
        'radix_target' => 'Mars',
        'radix_index' => 4,
        'radix_position' => 123.45,
        'progressive_position' => 213.45,
        'event_type' => 'aspect',  // aspect, house_ingress, sign_ingress, rd_transition
    ],
    // ... sorted chronologically
]
```

### Calculation Algorithm

**For each progressive planet:**
1. Calculate start and end position at progression dates
2. Determine daily speed in progression time
3. For each radix target: find aspect moments via interpolation + refinement
4. Check house cusps for ingress events
5. Check sign boundaries (0°, 30°, 60°, ...) for sign ingress
6. Check speed sign flip for RD transition

**Refinement loop (max 5 iterations):**
- Threshold: 0.5 seconds progression time (~3 minutes real time)
- Newton-Raphson: position error / speed → time correction
- Stop when |error| < threshold

## Frontend Design

### Form Layout

- **Time period**: start/end date inputs + quick-select (calendar year, two years)
- **Ingress options**: house ingress, sign ingress checkboxes
- **Planet selection**: 3-column grid (progressive, aspects, radix)
- **Submit button**: "Calculate progression events"

### Validation

- Start date required
- End date required, must be after start
- At least 1 progressive planet OR 1 radix target OR 1 aspect

### Results Table

Columns: Date | Dir | Progressive | Aspect | Radix | Prog Pos | Radix Pos

Event type formatting:
- `aspect` → Normal aspect display
- `rd_transition` → Radix column shows "Gaat Direct/Retrograde"
- `house_ingress` → Aspect = 0 (conjunctie), Radix = house name
- `sign_ingress` → Aspect = 0, Radix = sign name

## Session Structure

```php
$_SESSION['horoscope']['progression_events'] = [
    'input' => [
        'progressive_planets' => [0,1,2],
        'radix_targets' => [0,1,4],
        'aspects' => [0,90,180],
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'include_house_ingress' => true,
        'include_sign_ingress' => true,
    ],
    'results' => [...],  // Cached calculation results
];
```

## Implementation Files

**New:**
- src/Calculation/ProgressionEventCalculator.php
- UI section in public/index.php (tab + form + results)
- CSS updates in public/css/style.css

**Existing (reused):**
- PlanetCalculator, HouseCalculator, SwissEphemeris
- SymbolGlyph, Formatter

## Threshold Decision

0.5 seconds progression time (~3 minutes real time) - ensures date display is always correct at day boundaries, matching reference implementation performance (0.1s total).