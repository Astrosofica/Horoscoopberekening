# DateTime Input Enhancement - Design Spec

**Date:** 2026-04-08  
**Goal:** Replace native date/time inputs with intelligent text inputs for better mobile UX  
**Context:** Mobile users struggle with native date pickers for old birth dates (scrolling 30-50 years)

---

## Problem Statement

**Current situation:**
- Birth date: `<input type="date">` (browser native, YYYY-MM-DD format)
- Birth time: `<input type="time" step="1">` (browser native, HH:MM:SS format)
- Mobile UX: Users must scroll through years in date picker (impossible for 1980s birth dates)
- Desktop UX: Works fine, but inconsistent formatting

**Desired outcome:**
- Flexible input: Users can type `23031980` → auto-formatted to `23-03-1980`
- Fool-proof: Client-side validation catches errors before submit
- Mobile-friendly: `inputmode="numeric"` shows numeric keyboard
- Herbruikbaar: Single JavaScript class for all date/time inputs (birth, transit, progression)

---

## Architecture

### Component: DateTimeInput Class

**Location:** `/var/www/html/tijd/public/js/app.js`

```javascript
class DateTimeInput {
    constructor(elementId, type = 'birth') {
        // type: 'birth', 'transit', 'progression'
        // Determines validation range
    }
}
```

**Validatie ranges per type:**
- `birth`: 1800-2100 (Swiss Ephemeris range)
- `transit`: 1930-2039 (custom ephemeride database limit)
- `progression`: 1800-2100 (Swiss Ephemeris)

### Integration Points

**HTML changes:**
1. Replace `type="date"` → `type="text" inputmode="numeric"`
2. Replace `type="time"` → `type="text" inputmode="numeric"`
3. Add `placeholder` attributes ("DD-MM-JJJJ", "HH:MM:SS")
4. Add feedback elements (error/valid messages)

**Forms affected:**
1. Birth date/time (index.php lines 1053, 1057)
2. Progression dates (index.php lines 1330, 1331)
3. Transit dates (index.php lines 1763, 1766)

---

## Components

### Auto-Formatting Logic

**Datum:**
- User types: `23031980`
- After each keystroke: format with dashes → `23-03-1980`
- Incomplete: `23-03-1` → placeholder hint

**Tijd:**
- User types: `0255`
- After each keystroke: format with colons → `02:55:00`
- Incomplete: `02:5` → placeholder hint

**Input flexibility:**
- Users can use any separators: `-`, `/`, ` `, `.` → auto-stripped and re-formatted
- Example: `23/03/1980` → becomes `23-03-1980`

### Validation Logic

**Datum validation:**
```javascript
isValidDate(str) {
    const parts = str.split('-');
    if (parts.length !== 3) return false;
    
    let [d, m, y] = parts.map(Number);
    
    // Year range check (context-specific)
    if (y < this.validRange.minYear || y > this.validRange.maxYear) return false;
    
    // Month range
    if (m < 1 || m > 12) return false;
    
    // Day range (with month length check)
    const date = new Date(y, m - 1, d);
    return date.getFullYear() === y &&
           date.getMonth() === m - 1 &&
           date.getDate() === d;
}
```

**Tijd validation:**
```javascript
isValidTime(str) {
    const parts = str.split(':');
    if (parts.length < 2) return false;
    
    let [h, m, s = 0] = parts.map(Number);
    
    return h >= 0 && h <= 23 &&
           m >= 0 && m <= 59 &&
           s >= 0 && s <= 59;
}
```

### Feedback Elements

**Client-side:**
- Error messages: Inline under input field (`.form-error`)
- Valid messages: Optional indicator (`.form-valid`)
- Integration: Use existing form structure, minimal CSS changes

**Server-side:**
- Existing validation unchanged (safety net)
- Existing error block (top of form) unchanged

---

## Data Flow

### Input Flow (Client-side)

```
User types "23031980"
    ↓
Input event → cleanNumbers() → "23031980"
    ↓
Format with dashes → "23-03-1980"
    ↓
Update input value → display "23-03-1980"
    ↓
Placeholder hint (if incomplete)
```

### Validation Flow (Client-side)

```
User leaves field (blur)
    ↓
Validate input "23-03-1980"
    ↓
Check: year range, month 1-12, day valid
    ↓
Result:
    - Valid → normalize format, update hidden field
    - Invalid → show error, keep focus
```

### Submission Flow (Server-side)

```
User submits form
    ↓
Hidden fields contain YYYY-MM-DD format
    ↓
Server receives: date="1980-03-23", time="02:55:00"
    ↓
Server validation (existing regex checks)
    ↓
If valid → proceed with calculation
If invalid → show error (existing handling)
```

**Format conversion:**
- Client display: `DD-MM-JJJJ` (23-03-1980)
- Server expects: `YYYY-MM-DD` (1980-03-23)
- Hidden field strategy: Display input + hidden field for server

### Hidden Fields Strategy

**Implementation:**
```html
<!-- User-facing input (DD-MM-JJJJ) -->
<input type="text" id="date_display" inputmode="numeric" placeholder="DD-MM-JJJJ">

<!-- Hidden field for server (YYYY-MM-DD) -->
<input type="hidden" id="date" name="date" value="">
```

**JavaScript:**
```javascript
onBlur(e) {
    if (this.isValidDate(this.element.value)) {
        const normalized = this.normalizeDate(this.element.value);
        document.getElementById('date').value = normalized;
    }
}

normalizeDate(str) {
    let [d, m, y] = str.split('-');
    d = d.padStart(2, '0');
    m = m.padStart(2, '0');
    return `${y}-${m}-${d}`;
}
```

---

## Error Handling

### Client-side Validation Errors

**Error messages:**

| Error | Message | Condition |
|-------|---------|-----------|
| **Incomplete input** | "Voer 8 cijfers in (DD-MM-JJJJ)" | Input length < 8 |
| **Invalid format** | "Ongeldige datum format" | Not DD-MM-JJJJ pattern |
| **Invalid year** | "Jaar moet tussen 1800-2100 liggen" | Year outside range |
| **Invalid month** | "Maand moet 1-12 zijn" | Month < 1 or > 12 |
| **Invalid day** | "Ongeldige dag voor deze maand" | Day > month length |
| **Invalid date** | "Datum bestaat niet" | Date object validation fails |
| **Transit year** | "Transit datum moet tussen 1930-2039 liggen (efemeride limiet)" | Year outside transit range |

### Live Hints Strategy

**Placeholder hints (while typing):**
- Static: `placeholder="DD-MM-JJJJ"` (datum), `placeholder="HH:MM:SS"` (tijd)
- Dynamic (optional): Show step-by-step hints ("Voer dag in", "Voer maand in")

**Implementation:**
```javascript
updatePlaceholderHint() {
    const value = cleanNumbers(this.element.value);
    
    if (this.isDate) {
        if (value.length < 2) {
            // Hint: "Voer dag in"
        } else if (value.length < 4) {
            // Hint: "Voer maand in"
        } else if (value.length < 8) {
            // Hint: "Voer jaar in"
        }
    }
}
```

### Server-side Validation (Unchanged)

**Existing validation (index.php):**
```php
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
    $error = "Ongeldige datum";
}

if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
    $error = "Ongeldige tijd";
}
```

**Integration:**
- Client-side catches most errors (better UX)
- Server-side is safety net (never removed)
- Existing error handling unchanged

---

## Testing

### Manual Testing Checklist

**Basic tests:**
- Datum: `23031980` → `23-03-1980` ✓
- Datum separators: `23/03/1980` → `23-03-1980` ✓
- Datum incomplete: `23-03-1` → hint ✓
- Tijd: `0255` → `02:55:00` ✓
- Tijd separators: `02.55` → `02:55:00` ✓

**Validation tests:**
- Invalid year: `23-03-1700` → error ✓
- Transit year: `23-03-2040` → error (transit range) ✓
- Invalid day: `31-02-1980` → error ✓
- Invalid time: `25:00` → error ✓

### Mobile Testing

**Devices:**
- iOS Safari (iPhone)
- Android Chrome (Samsung/Pixel)

**Key tests:**
- ✅ `inputmode="numeric"` shows numeric keyboard
- ✅ Auto-formatting works
- ✅ No scrolling date picker
- ✅ Touch-friendly

### Edge Cases

**Edge cases:**
- Leap year: `29-02-2000` → valid ✓
- Non-leap year: `29-02-1900` → invalid ✗
- Future date: `01-01-2030` → valid (if in range) ✓
- Past date: `01-01-1800` → valid (if in range) ✓
- Empty input: "" → no error (until submit) ✓
- Whitespace: `  23 03 1980  ` → stripped ✓
- Extra digits: `23031980123456` → truncated ✓

### Integration Testing

**Workflow tests:**
1. Fill form → submit → horoscope calculated ✓
2. Edit horoscope → inputs work ✓
3. Progressions/transits → inputs work ✓
4. Server validation still catches errors ✓

---

## Implementation Notes

### Naadloze Integratie

**Principles:**
- Client-side is enhancement, not replacement
- Server-side validation remains intact
- No changes to form submission flow
- Work with existing CSS/classes
- Progressive enhancement (mobile users benefit most)

### Code Footprint

**JavaScript:**
- ~150 lines in app.js (DateTimeInput class)
- ~5-10 lines per form field (instantiation)

**HTML:**
- Replace input types (minimal change)
- Add placeholder attributes
- Add feedback elements (optional)

**CSS:**
- Minimal changes (use existing `.form-error`)
- Optional `.form-valid` styling

---

## References

**Reference code:**
- `reference/preview.html` - Flexible date/time input prototype
- Existing patterns: `app.js` (TijdApp class structure)

**Ephemeride limits:**
- Transit: 1930-2039 (custom database)
- Swiss Ephemeris: 1800-2100 (high accuracy)
- Moshier fallback: outside 1800-2100 (lower accuracy)

---

## Success Criteria

1. ✅ Mobile users can easily input old birth dates (no scrolling)
2. ✅ Client-side validation catches errors before submit
3. ✅ Herbruikbaar for all date/time inputs (birth, transit, progression)
4. ✅ Server-side validation unchanged (safety net)
5. ✅ Existing workflow unchanged (naadloos integratie)
6. ✅ Fool-proof with live hints and clear error messages