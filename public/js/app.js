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
        if (!this.hasResult && tabId !== 'calculate') {
            return;
        }
        
        // Lazy loading: reload bij eerste bezoek aan aspecten tab
        // zodat server de data kan berekenen
        if (tabId === 'aspects' && pushState) {
            // Check of we al een page reload nodig hebben
            const url = new URL(window.location.href);
            const currentTab = url.searchParams.get('tab');
            
            // Alleen reload als we niet al op de aspecten tab zijn
            if (currentTab !== 'aspects') {
                url.searchParams.set('tab', tabId);
                window.location.href = url.toString();
                return;
            }
        }
        
        // Lazy loading: reload bij eerste bezoek aan progressies tab
        if (tabId === 'progressions' && pushState) {
            const url = new URL(window.location.href);
            const currentTab = url.searchParams.get('tab');
            
            if (currentTab !== 'progressions') {
                url.searchParams.set('tab', tabId);
                window.location.href = url.toString();
                return;
            }
        }
        
        // Lazy loading: reload bij eerste bezoek aan antiscia tab
        if (tabId === 'antiscia' && pushState) {
            const url = new URL(window.location.href);
            const currentTab = url.searchParams.get('tab');
            
            if (currentTab !== 'antiscia') {
                url.searchParams.set('tab', tabId);
                window.location.href = url.toString();
                return;
            }
        }
        
        // Lazy loading: reload bij eerste bezoek aan midpoints-planet tab
        if (tabId === 'midpoints-planet' && pushState) {
            const url = new URL(window.location.href);
            const currentTab = url.searchParams.get('tab');
            
            if (currentTab !== 'midpoints-planet') {
                url.searchParams.set('tab', tabId);
                window.location.href = url.toString();
                return;
            }
        }
        
        // Lazy loading: reload bij eerste bezoek aan midpoints-sign tab
        if (tabId === 'midpoints-sign' && pushState) {
            const url = new URL(window.location.href);
            const currentTab = url.searchParams.get('tab');
            
            if (currentTab !== 'midpoints-sign') {
                url.searchParams.set('tab', tabId);
                window.location.href = url.toString();
                return;
            }
        }
        
        // Lazy loading: reload bij eerste bezoek aan midpoints-tree tab
        if (tabId === 'midpoints-tree' && pushState) {
            const url = new URL(window.location.href);
            const currentTab = url.searchParams.get('tab');
            
            if (currentTab !== 'midpoints-tree') {
                url.searchParams.set('tab', tabId);
                window.location.href = url.toString();
                return;
            }
        }
        
        // Lazy loading: reload bij eerste bezoek aan transits tab
        if (tabId === 'transits' && pushState) {
            const url = new URL(window.location.href);
            const currentTab = url.searchParams.get('tab');
            
            if (currentTab !== 'transits') {
                url.searchParams.set('tab', tabId);
                window.location.href = url.toString();
                return;
            }
        }
        
        // Lazy loading: reload bij eerste bezoek aan transits-list tab
        if (tabId === 'transits-list' && pushState) {
            const url = new URL(window.location.href);
            const currentTab = url.searchParams.get('tab');
            
            if (currentTab !== 'transits-list') {
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

document.addEventListener('DOMContentLoaded', () => {
    window.tijdApp = new TijdApp();
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

function quickCalendarYear() {
    const year = new Date().getFullYear();
    document.getElementById('prog_start_date').value = `${year}-01-01`;
    document.getElementById('prog_end_date').value = `${year}-12-31`;
}

function quickTwoYears() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    
    const startDate = new Date(now);
    startDate.setFullYear(startDate.getFullYear() - 1);
    
    const endDate = new Date(now);
    endDate.setFullYear(endDate.getFullYear() + 1);
    
    document.getElementById('prog_start_date').value = 
        `${startDate.getFullYear()}-${pad(startDate.getMonth() + 1)}-${pad(startDate.getDate())}`;
    document.getElementById('prog_end_date').value = 
        `${endDate.getFullYear()}-${pad(endDate.getMonth() + 1)}-${pad(endDate.getDate())}`;
}

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

function quickTwoYears() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    
    const startDate = new Date(now);
    startDate.setFullYear(startDate.getFullYear() - 1);
    
    const endDate = new Date(now);
    endDate.setFullYear(endDate.getFullYear() + 1);
    
    document.getElementById('prog_start_date').value = 
        `${startDate.getFullYear()}-${pad(startDate.getMonth() + 1)}-${pad(startDate.getDate())}`;
    document.getElementById('prog_end_date').value = 
        `${endDate.getFullYear()}-${pad(endDate.getMonth() + 1)}-${pad(endDate.getDate())}`;
    
    document.getElementById('quick-calyear').checked = false;
}

function quickTransitCalendarYear() {
    const year = new Date().getFullYear();
    document.querySelector('[name="transit_start_date"]').value = year + '-01-01';
    document.querySelector('[name="transit_end_date"]').value = year + '-12-31';
}

function quickTransitTwoYears() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    
    const startDate = new Date(now);
    startDate.setFullYear(startDate.getFullYear() - 1);
    
    const endDate = new Date(now);
    endDate.setFullYear(endDate.getFullYear() + 1);
    
    document.querySelector('[name="transit_start_date"]').value =
        `${startDate.getFullYear()}-${pad(startDate.getMonth() + 1)}-${pad(startDate.getDate())}`;
    document.querySelector('[name="transit_end_date"]').value =
        `${endDate.getFullYear()}-${pad(endDate.getMonth() + 1)}-${pad(endDate.getDate())}`;
}