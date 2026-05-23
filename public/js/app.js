class TijdApp {
    constructor() {
        this.currentTab = 'calculate';
        this.hasResult = false;
        this.sidebarOpen = false;
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initFromUrl();
    }
    
    bindEvents() {
        document.querySelectorAll('[data-tab]').forEach(el => {
            el.addEventListener('click', (e) => {
                const href = el.getAttribute('href');
                
                // Laat absolute URLs (index.php?tab=...) ongemoeid
                if (href && href.includes('index.php')) {
                    return; // Browser navigeert normaal
                }
                
                e.preventDefault();
                const tab = el.getAttribute('data-tab');
                if (!el.classList.contains('sidebar__item--disabled')) {
                    this.switchTab(tab);
                }
            });
        });
        
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        const overlay = document.querySelector('.sidebar__overlay');
        
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => this.toggleSidebar());
        }
        
        if (overlay) {
            overlay.addEventListener('click', () => this.closeSidebar());
        }
        
        window.addEventListener('popstate', () => this.initFromUrl());
    }
    
    initFromUrl() {
        // Check query parameter voor tab (server-side lazy loading)
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab && document.getElementById('tab-' + tab)) {
            this.switchTab(tab, false);
            return;
        }
        
        // Fallback: check hash voor backwards compatibility
        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById('tab-' + hash)) {
            this.switchTab(hash, false);
        }
    }
    
    switchTab(tabId, pushState = true) {
        if (!this.hasResult && tabId !== 'calculate' && tabId !== 'about') {
            return;
        }
        
        // Lazy loading: reload bij eerste bezoek aan lazy tabs
        const lazyReloadTabs = ['aspects', 'progressions', 'antiscia',
            'midpoints-planet', 'midpoints-sign', 'midpoints-tree',
            'transits', 'transits-list', 'solaar'];

        if (lazyReloadTabs.includes(tabId) && pushState) {
            const url = new URL(window.location.href);
            if (url.searchParams.get('tab') !== tabId) {
                url.searchParams.set('tab', tabId);
                window.location.href = url.toString();
                return;
            }
        }
        
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.add('tab-content--hidden');
        });
        
        const targetTab = document.getElementById('tab-' + tabId);
        if (targetTab) {
            targetTab.classList.remove('tab-content--hidden');
        }
        
        document.querySelectorAll('.sidebar__item[data-tab]').forEach(el => {
            el.classList.remove('sidebar__item--active');
            if (el.getAttribute('data-tab') === tabId) {
                el.classList.add('sidebar__item--active');
            }
        });
        
        this.currentTab = tabId;
        
        if (pushState) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            history.pushState(null, '', url.toString());
        }
        
        this.closeSidebar();
    }
    
    enableResultTabs() {
        this.hasResult = true;
        
        document.querySelectorAll('.sidebar__item--disabled[data-tab]').forEach(el => {
            el.classList.remove('sidebar__item--disabled');
        });
    }
    
    onCalculationComplete() {
        this.enableResultTabs();
        this.switchTab('horoscope');
    }
    
    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
        this.updateSidebarState();
    }
    
    closeSidebar() {
        this.sidebarOpen = false;
        this.updateSidebarState();
    }
    
    updateSidebarState() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar__overlay');
        
        if (sidebar) {
            sidebar.classList.toggle('sidebar--open', this.sidebarOpen);
        }
        if (overlay) {
            overlay.classList.toggle('sidebar__overlay--visible', this.sidebarOpen);
        }
    }
}

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
    
    cleanNumbers(value) {
        return value.replace(/[^\d]/g, '');
    }
    
    createFeedbackElements() {
        const parent = this.displayElement.closest('.form-group') || this.displayElement.closest('label');
        
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
        // Skip auto-fill for progression/transit fields - let them stay empty with placeholder
        if (this.type === 'progression' || this.type === 'transit') {
            return;
        }
        
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
    
    onBlur(e) {
        const value = e.target.value;
        
        let isValid;
        let errorMessage;
        
        if (this.isDate) {
            isValid = this.isValidDate(value);
            
            if (!isValid) {
                if (value.replace(/-/g, '').length < 8) {
                    errorMessage = "Voer 8 cijfers in (DD-MM-JJJJ of DDMJJJJJ)";
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
                const digits = value.replace(/:/g, '').length;
                if (digits < 4) {
                    errorMessage = "Voer 4-6 cijfers in (UUMM of UU:MM:SS)";
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

document.addEventListener('DOMContentLoaded', () => {
    // Check if page has result (from PHP template)
    const hasResultElement = document.querySelector('[data-has-result]');
    const hasResult = hasResultElement ? hasResultElement.dataset.hasResult === 'true' : false;
    
    window.tijdApp = new TijdApp();
    
    // Sync hasResult state if page already has result
    if (hasResult) {
        window.tijdApp.enableResultTabs();
    }
    
    // Initialize DateTimeInput for birth form
    if (document.getElementById('date_display') && document.getElementById('time')) {
        new DateTimeInput('date_display', 'date', 'birth');
    }
    
    if (document.getElementById('time_display') && document.getElementById('time')) {
        new DateTimeInput('time_display', 'time', 'birth');
    }
    
    // Initialize DateTimeInput for progression events form
    if (document.getElementById('prog_start_date_display') && document.getElementById('prog_start_date')) {
        new DateTimeInput('prog_start_date_display', 'prog_start_date', 'progression');
    }
    
    if (document.getElementById('prog_end_date_display') && document.getElementById('prog_end_date')) {
        new DateTimeInput('prog_end_date_display', 'prog_end_date', 'progression');
    }
    
    // Initialize DateTimeInput for transit events form
    if (document.getElementById('transit_start_date_display') && document.getElementById('transit_start_date')) {
        new DateTimeInput('transit_start_date_display', 'transit_start_date', 'transit');
    }
    
    if (document.getElementById('transit_end_date_display') && document.getElementById('transit_end_date')) {
        new DateTimeInput('transit_end_date_display', 'transit_end_date', 'transit');
    }
});

// =============================================================================
// Progression Events Form Helpers
// =============================================================================

function toggleAllGroup(name, checked) {
    const checkboxes = document.querySelectorAll(`input[name="${name}"]`);
    checkboxes.forEach(cb => cb.checked = checked);
}

function checkToggleState(name, toggleId, totalCount) {
    const checkboxes = document.querySelectorAll(`input[name="${name}"]`);
    const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    document.getElementById(toggleId).checked = (checkedCount === totalCount);
}

function quickDateRange(prefix, type) {
    const year = new Date().getFullYear();
    const pad = (n) => String(n).padStart(2, '0');
    
    let startDate, endDate, startDisplay, endDisplay;
    
    if (type === 'calyear') {
        startDate = `${year}-01-01`;
        endDate = `${year}-12-31`;
        startDisplay = `01-01-${year}`;
        endDisplay = `31-12-${year}`;
    } else if (type === 'twoyears') {
        const now = new Date();
        const startDateObj = new Date(now);
        startDateObj.setFullYear(startDateObj.getFullYear() - 1);
        
        const endDateObj = new Date(now);
        endDateObj.setFullYear(endDateObj.getFullYear() + 1);
        
        startDate = `${startDateObj.getFullYear()}-${pad(startDateObj.getMonth() + 1)}-${pad(startDateObj.getDate())}`;
        endDate = `${endDateObj.getFullYear()}-${pad(endDateObj.getMonth() + 1)}-${pad(endDateObj.getDate())}`;
        startDisplay = `${pad(startDateObj.getDate())}-${pad(startDateObj.getMonth() + 1)}-${startDateObj.getFullYear()}`;
        endDisplay = `${pad(endDateObj.getDate())}-${pad(endDateObj.getMonth() + 1)}-${endDateObj.getFullYear()}`;
        
        if (prefix === 'prog') {
            document.getElementById('quick-calyear').checked = false;
        }
    }
    
    document.getElementById(`${prefix}_start_date`).value = startDate;
    document.getElementById(`${prefix}_end_date`).value = endDate;
    document.getElementById(`${prefix}_start_date_display`).value = startDisplay;
    document.getElementById(`${prefix}_end_date_display`).value = endDisplay;
}

function quickCalendarYear() { quickDateRange('prog', 'calyear'); }
function quickTwoYears() { quickDateRange('prog', 'twoyears'); }
function quickTransitCalendarYear() { quickDateRange('transit', 'calyear'); }
function quickTransitTwoYears() { quickDateRange('transit', 'twoyears'); }

function toggleDominantAspects() {
    const checkbox = document.getElementById('toggle-dominant-aspects');
    const aspectTable = document.querySelector('#tab-aspects table');
    
    if (checkbox.checked) {
        aspectTable.classList.add('table--dominant-highlight');
    } else {
        aspectTable.classList.remove('table--dominant-highlight');
    }
    
    localStorage.setItem('showDominantAspects', checkbox.checked);
}

document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('showDominantAspects');
    if (saved === 'true') {
        const checkbox = document.getElementById('toggle-dominant-aspects');
        if (checkbox) {
            checkbox.checked = true;
            toggleDominantAspects();
        }
    }
});

function toggleAdvancedSettings() {
    const content = document.getElementById('advanced-settings-content');
    const toggle = document.querySelector('.advanced-settings__toggle');
    
    if (content.classList.contains('visible')) {
        content.classList.remove('visible');
        toggle.classList.remove('active');
        localStorage.setItem('advancedSettingsOpen', 'false');
    } else {
        content.classList.add('visible');
        toggle.classList.add('active');
        localStorage.setItem('advancedSettingsOpen', 'true');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const content = document.getElementById('advanced-settings-content');
    const toggle = document.querySelector('.advanced-settings__toggle');
    
    if (!content || !toggle) return;
    
    // If PHP already set it open (has visible class), don't override
    if (content.classList.contains('visible')) {
        return;
    }
    
    // Otherwise, check localStorage
    const saved = localStorage.getItem('advancedSettingsOpen');
    if (saved === 'true') {
        content.classList.add('visible');
        toggle.classList.add('active');
    }
});
// ===== Wheel Aspect Lines Toggle =====
(function() {
    function getSlug() {
        return new URLSearchParams(window.location.search).get('h') || 'current';
    }

    function storageKey() {
        return 'wheel_aspects_' + getSlug();
    }

    function setWheelUrl(img, on) {
        const base = img.src.split('?')[0].replace(/wheel_aspects?\.php$/, 'wheel.php');
        img.src = on ? base + '?aspects=1' : base;
    }

    function applyState() {
        const img = document.getElementById('wheel-image');
        const link = document.getElementById('wheel-aspect-toggle');
        if (!img || !link) return;

        const url = new URL(img.src, window.location.href);
        const saved = localStorage.getItem(storageKey());

        if (saved === 'on' && url.searchParams.get('aspects') !== '1') {
            setWheelUrl(img, true);
            link.textContent = 'Verberg aspectlijnen';
        } else if (saved !== 'on' && url.searchParams.get('aspects') === '1') {
            setWheelUrl(img, false);
            link.textContent = 'Toon aspectlijnen';
        }
    }

    document.addEventListener('click', function(e) {
        const link = e.target.closest('#wheel-aspect-toggle');
        if (!link) return;
        e.preventDefault();

        const img = document.getElementById('wheel-image');
        if (!img) return;

        const url = new URL(img.src, window.location.href);
        const isOn = url.searchParams.get('aspects') === '1';

        setWheelUrl(img, !isOn);
        link.textContent = isOn ? 'Toon aspectlijnen' : 'Verberg aspectlijnen';
        localStorage.setItem(storageKey(), isOn ? 'off' : 'on');
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyState);
    } else {
        applyState();
    }
})();
