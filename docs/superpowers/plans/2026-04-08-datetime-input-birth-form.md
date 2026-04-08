# DateTime Input - Birth Form Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace native date/time inputs with intelligent text inputs for birth form (mobile-friendly, flexible input, client-side validation)

**Architecture:** Single DateTimeInput class in app.js, hidden field strategy (display DD-MM-JJJJ, server receives YYYY-MM-DD), minimal CSS additions

**Tech Stack:** JavaScript (ES6 class), PHP (unchanged), CSS (minimal additions)

---

## File Structure

**Files to create/modify:**

| File | Responsibility | Change Type |
|------|---------------|-------------|
| `public/js/app.js` | DateTimeInput class logic | Modify (add class) |
| `public/index.php` | Birth date/time inputs | Modify (lines 1053, 1057) |
| `public/css/_form.css` | Feedback styling | Modify (add .form-valid, .form-hint-inline) |

**Integration points:**
- Display input: `type="text" inputmode="numeric" placeholder="DD-MM-JJJJ"`
- Hidden input: `type="hidden" name="date"` (server receives YYYY-MM-DD)
- Server validation: unchanged (regex checks still work)

---

## Task 1: Add DateTimeInput Class to app.js

**Files:**
- Modify: `public/js/app.js` (add after TijdApp class, line 227)

- [ ] **Step 1: Write DateTimeInput class skeleton**

Add to `public/js/app.js` after line 227 (after `TijdApp` class):

```javascript
class DateTimeInput {
    constructor(displayElementId, hiddenElementId, type = 'birth') {
        this.displayElement = document.getElementById(displayElementId);
        this.hiddenElement = document.getElementById(hiddenElementId);
        this.type = type;
        this.isDate = displayElementId.includes('date');
        this.isTime = displayElementId.includes('time');
        
        this.validRange = this.getValidRange();
        this.errorElement = null;
        this.hintElement = null;
        
        this.init();
    }
    
    getValidRange() {
        switch(this.type) {
            case 'transit': return { minYear: 1930, maxYear: 2039 };
            case 'birth': return { minYear: 1800, maxYear: 2100 };
            case 'progression': return { minYear: 1800, maxYear: 2100 };
            default: return { minYear: 1800, maxYear: 2100 };
        }
    }
    
    init() {
        this.createFeedbackElements();
        this.convertInitialValue();
        this.bindEvents();
    }
}
```

- [ ] **Step 2: Add helper methods**

Continue in `app.js`:

```javascript
    cleanNumbers(value) {
        return value.replace(/[^\d]/g, '');
    }
    
    createFeedbackElements() {
        const parent = this.displayElement.closest('.form-group');
        
        if (!parent) return;
        
        this.errorElement = parent.querySelector('.form-hint-inline--error');
        if (!this.errorElement) {
            this.errorElement = document.createElement('div');
            this.errorElement.className = 'form-hint-inline form-hint-inline--error';
            this.errorElement.style.display = 'none';
            parent.appendChild(this.errorElement);
        }
        
        this.hintElement = parent.querySelector('.form-hint-inline--hint');
        if (!this.hintElement) {
            this.hintElement = document.createElement('div');
            this.hintElement.className = 'form-hint-inline form-hint-inline--hint';
            this.hintElement.style.display = 'none';
            parent.appendChild(this.hintElement);
        }
    }
    
    convertInitialValue() {
        const value = this.hiddenElement.value;
        if (!value) return;
        
        if (this.isDate) {
            const [y, m, d] = value.split('-');
            this.displayElement.value = `${d}-${m}-${y}`;
        }
        
        if (this.isTime) {
            this.displayElement.value = value;
        }
    }
    
    bindEvents() {
        this.displayElement.addEventListener('input', this.onInput.bind(this));
        this.displayElement.addEventListener('blur', this.onBlur.bind(this));
    }
```

- [ ] **Step 3: Add input formatting logic**

Continue in `app.js`:

```javascript
    onInput(e) {
        let value = e.target.value;
        
        value = this.cleanNumbers(value);
        
        if (this.isDate && value.length > 8) value = value.slice(0, 8);
        if (this.isTime && value.length > 6) value = value.slice(0, 6);
        
        let formatted;
        
        if (this.isDate) {
            let d = value.slice(0, 2);
            let m = value.slice(2, 4);
            let y = value.slice(4, 8);
            
            formatted = d;
            if (m) formatted += '-' + m;
            if (y) formatted += '-' + y;
        }
        
        if (this.isTime) {
            let h = value.slice(0, 2);
            let m = value.slice(2, 4);
            let s = value.slice(4, 6);
            
            formatted = h;
            if (m) formatted += ':' + m;
            if (s) formatted += ':' + s;
        }
        
        e.target.value = formatted;
        
        this.hideFeedback();
        this.showHint(value);
    }
```

- [ ] **Step 4: Add validation logic**

Continue in `app.js`:

```javascript
    isValidDate(str) {
        const parts = str.split('-');
        if (parts.length !== 3) return false;
        
        let [d, m, y] = parts.map(Number);
        
        if (!d || !m || !y) return false;
        if (y < this.validRange.minYear || y > this.validRange.maxYear) {
            return false;
        }
        
        const date = new Date(y, m - 1, d);
        
        return date.getFullYear() === y &&
               date.getMonth() === m - 1 &&
               date.getDate() === d;
    }
    
    isValidTime(str) {
        const parts = str.split(':');
        if (parts.length < 2) return false;
        
        let [h, m, s = 0] = parts.map(Number);
        
        return h >= 0 && h <= 23 &&
               m >= 0 && m <= 59 &&
               s >= 0 && s <= 59;
    }
```

- [ ] **Step 5: Add blur handler**

Continue in `app.js`:

```javascript
    onBlur(e) {
        const value = e.target.value;
        
        let isValid;
        let errorMessage;
        
        if (this.isDate) {
            isValid = this.isValidDate(value);
            
            if (!isValid) {
                if (value.length < 8) {
                    errorMessage = "Voer 8 cijfers in (DD-MM-JJJJ)";
                } else {
                    const parts = value.split('-');
                    if (parts.length !== 3) {
                        errorMessage = "Ongeldige datum format";
                    } else {
                        let [d, m, y] = parts.map(Number);
                        
                        if (y < this.validRange.minYear || y > this.validRange.maxYear) {
                            errorMessage = `Jaar moet tussen ${this.validRange.minYear}-${this.validRange.maxYear} liggen`;
                        } else if (m < 1 || m > 12) {
                            errorMessage = "Maand moet 1-12 zijn";
                        } else if (d < 1 || d > 31) {
                            errorMessage = "Dag moet 1-31 zijn";
                        } else {
                            errorMessage = "Ongeldige datum (bijv. 31 februari)";
                        }
                    }
                }
            }
            
            if (isValid) {
                this.normalizeDate(value);
            }
        }
        
        if (this.isTime) {
            isValid = this.isValidTime(value);
            
            if (!isValid) {
                if (value.length < 4) {
                    errorMessage = "Voer 4-6 cijfers in (HH:MM of HH:MM:SS)";
                } else {
                    errorMessage = "Ongeldige tijd (uur 0-23, min 0-59)";
                }
            }
            
            if (isValid) {
                this.normalizeTime(value);
            }
        }
        
        if (isValid) {
            this.showValid();
            this.hideHint();
        } else {
            this.showError(errorMessage);
        }
    }
```

- [ ] **Step 6: Add normalization methods**

Continue in `app.js`:

```javascript
    normalizeDate(str) {
        let [d, m, y] = str.split('-');
        
        d = d.padStart(2, '0');
        m = m.padStart(2, '0');
        
        const serverFormat = `${y}-${m}-${d}`;
        
        this.hiddenElement.value = serverFormat;
        
        const displayFormat = `${d}-${m}-${y}`;
        this.displayElement.value = displayFormat;
    }
    
    normalizeTime(str) {
        let parts = str.split(':');
        let [h, m, s = '00'] = parts;
        
        h = h.padStart(2, '0');
        m = m.padStart(2, '0');
        s = s.padStart(2, '0');
        
        const normalized = `${h}:${m}:${s}`;
        
        this.hiddenElement.value = normalized;
        this.displayElement.value = normalized;
    }
```

- [ ] **Step 7: Add feedback methods**

Continue in `app.js`:

```javascript
    showError(message) {
        if (this.errorElement) {
            this.errorElement.textContent = message;
            this.errorElement.style.display = 'block';
        }
        if (this.hintElement) {
            this.hintElement.style.display = 'none';
        }
    }
    
    showValid() {
        if (this.errorElement) {
            this.errorElement.style.display = 'none';
        }
    }
    
    showHint(value) {
        if (!this.hintElement) return;
        
        const length = value.length;
        
        if (this.isDate) {
            if (length < 2) {
                this.hintElement.textContent = "Voer dag in (2 cijfers)";
                this.hintElement.style.display = 'block';
            } else if (length < 4) {
                this.hintElement.textContent = "Voer maand in (2 cijfers)";
                this.hintElement.style.display = 'block';
            } else if (length < 8) {
                this.hintElement.textContent = "Voer jaar in (4 cijfers)";
                this.hintElement.style.display = 'block';
            } else {
                this.hintElement.style.display = 'none';
            }
        }
        
        if (this.isTime) {
            if (length < 2) {
                this.hintElement.textContent = "Voer uur in (2 cijfers)";
                this.hintElement.style.display = 'block';
            } else if (length < 4) {
                this.hintElement.textContent = "Voer minuten in (2 cijfers)";
                this.hintElement.style.display = 'block';
            } else {
                this.hintElement.style.display = 'none';
            }
        }
    }
    
    hideHint() {
        if (this.hintElement) {
            this.hintElement.style.display = 'none';
        }
    }
    
    hideFeedback() {
        if (this.errorElement) {
            this.errorElement.style.display = 'none';
        }
    }
}
```

- [ ] **Step 8: Verify syntax**

Run: `node --check public/js/app.js`
Expected: No errors (silent output)

---

## Task 2: Add CSS for Feedback Elements

**Files:**
- Modify: `public/css/_form.css` (add after line 93)

- [ ] **Step 1: Add .form-hint-inline styles**

Add to `public/css/_form.css` after line 93 (after `.form-hint`):

```css
.form-hint-inline {
    font-size: var(--font-size-sm);
    margin-top: 6px;
    line-height: 1.4;
}

.form-hint-inline--error {
    color: var(--color-danger);
    display: none;
}

.form-hint-inline--hint {
    color: var(--color-text-hint);
    display: none;
}

.form-hint-inline--valid {
    color: var(--color-success);
    display: none;
}
```

- [ ] **Step 2: Verify CSS syntax**

Run: `cat public/css/_form.css | grep -A 20 "form-hint-inline"`
Expected: Shows the new CSS block

---

## Task 3: Replace Date Input in index.php

**Files:**
- Modify: `public/index.php` (lines 1051-1054)

- [ ] **Step 1: Replace date input HTML**

Replace lines 1051-1054 in `public/index.php`:

**Old:**
```php
<div class="form-group">
    <label for="date">Datum</label>
    <input type="date" id="date" name="date" value="<?= htmlspecialchars($formValues['date']) ?>" required<?= $formDisabled ? ' disabled' : '' ?>>
</div>
```

**New:**
```php
<div class="form-group">
    <label for="date_display">Datum</label>
    <input type="text" id="date_display" inputmode="numeric" placeholder="DD-MM-JJJJ" required<?= $formDisabled ? ' disabled' : '' ?>>
    <input type="hidden" id="date" name="date" value="<?= htmlspecialchars($formValues['date']) ?>">
</div>
```

- [ ] **Step 2: Verify PHP syntax**

Run: `php -l public/index.php`
Expected: `No syntax errors detected in public/index.php`

---

## Task 4: Replace Time Input in index.php

**Files:**
- Modify: `public/index.php` (lines 1055-1058)

- [ ] **Step 1: Replace time input HTML**

Replace lines 1055-1058 in `public/index.php`:

**Old:**
```php
<div class="form-group">
    <label for="time">Tijd (lokaal)</label>
    <input type="time" id="time" name="time" value="<?= htmlspecialchars($formValues['time']) ?>" step="1" required<?= $formDisabled ? ' disabled' : '' ?>>
</div>
```

**New:**
```php
<div class="form-group">
    <label for="time_display">Tijd (lokaal)</label>
    <input type="text" id="time_display" inputmode="numeric" placeholder="HH:MM:SS" required<?= $formDisabled ? ' disabled' : '' ?>>
    <input type="hidden" id="time" name="time" value="<?= htmlspecialchars($formValues['time']) ?>">
</div>
```

- [ ] **Step 2: Verify PHP syntax**

Run: `php -l public/index.php`
Expected: `No syntax errors detected in public/index.php`

---

## Task 5: Instantiate DateTimeInput in app.js

**Files:**
- Modify: `public/js/app.js` (add after TijdApp initialization, line 231)

- [ ] **Step 1: Add initialization code**

Add to `public/js/app.js` after line 231 (after `window.tijdApp = new TijdApp();`):

```javascript
// Initialize DateTimeInput for birth form
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('date_display') && document.getElementById('date')) {
        new DateTimeInput('date_display', 'date', 'birth');
    }
    
    if (document.getElementById('time_display') && document.getElementById('time')) {
        new DateTimeInput('time_display', 'time', 'birth');
    }
});
```

- [ ] **Step 2: Verify syntax**

Run: `node --check public/js/app.js`
Expected: No errors (silent output)

---

## Task 6: Manual Testing

**No automated tests - manual browser testing required**

- [ ] **Step 1: Test basic formatting**

Open browser: `http://localhost/tijd/public/index.php`

Test datum:
- Type `23031980` → should show `23-03-1980`
- Type `23/03/1980` → should show `23-03-1980`
- Type `23-03` → should show hint "Voer jaar in (4 cijfers)"

Test tijd:
- Type `0255` → should show `02:55`
- Type `025530` → should show `02:55:30`
- Type `02.55` → should show `02:55`

Expected: All auto-formatting works correctly

- [ ] **Step 2: Test validation**

Test datum errors:
- Type `23-03-1700` → blur → error "Jaar moet tussen 1800-2100 liggen"
- Type `31-02-1980` → blur → error "Ongeldige datum (bijv. 31 februari)"
- Type `23-03` → blur → error "Voer 8 cijfers in (DD-MM-JJJJ)"

Test tijd errors:
- Type `25:00` → blur → error "Ongeldige tijd (uur 0-23, min 0-59)"
- Type `02` → blur → error "Voer 4-6 cijfers in (HH:MM of HH:MM:SS)"

Expected: All validation errors show correctly

- [ ] **Step 3: Test normalization**

Test datum:
- Type `23031980` → blur → should update hidden field to `1980-03-23`
- Type `1-1-2000` → blur → should normalize to `01-01-2000` display, `2000-01-01` hidden

Test tijd:
- Type `0255` → blur → should normalize to `02:55:00` (both display and hidden)
- Type `2:5` → blur → should normalize to `02:05:00`

Expected: Normalization works correctly

- [ ] **Step 4: Test form submission**

Fill form with valid data:
- Date: `23-03-1980`
- Time: `02:55`
- Name, location

Click submit → check server receives:
- `$_POST['date']` = `1980-03-23`
- `$_POST['time']` = `02:55:00`

Expected: Form submission works, horoscope calculated

- [ ] **Step 5: Test mobile keyboard**

Open on mobile device (or Chrome DevTools mobile mode):

Check `inputmode="numeric"` shows:
- Numeric keyboard (not full keyboard)
- No date picker popup

Expected: Mobile UX works correctly

---

## Task 7: Git Commit

**Files to commit:**
- `public/js/app.js`
- `public/css/_form.css`
- `public/index.php`

- [ ] **Step 1: Add modified files**

Run: `git add public/js/app.js public/css/_form.css public/index.php`

- [ ] **Step 2: Commit changes**

Run: `git commit -m "feat: replace birth date/time inputs with intelligent text inputs

- Add DateTimeInput class to app.js (auto-formatting, validation)
- Replace type=date/time with type=text inputmode=numeric
- Add hidden fields for server (YYYY-MM-DD, HH:MM:SS)
- Add CSS for inline feedback elements
- Mobile-friendly: numeric keyboard, no date picker

Phase 1: Birth form only (progression/transit forms later)"`

Expected: Commit created successfully

---

## Success Criteria Verification

After implementation:

1. ✅ Mobile users can type birth dates easily (23031980 → 23-03-1980)
2. ✅ Client-side validation catches errors before submit
3. ✅ Server receives YYYY-MM-DD format (existing validation works)
4. ✅ Existing workflow unchanged (form submission works)
5. ✅ Live hints show while typing ("Voer dag in", "Voer maand in")
6. ✅ Fool-proof error messages (year range, invalid date, etc.)

---

## Next Phase (After Testing)

After this phase is tested and confirmed working:

**Phase 2:** Apply to progression dates (lines 1330, 1331)
**Phase 3:** Apply to transit dates (lines 1763, 1766)

Each phase will:
1. Replace input HTML (add display + hidden fields)
2. Instantiate DateTimeInput with appropriate type ('progression' or 'transit')
3. Test thoroughly
4. Git commit separately