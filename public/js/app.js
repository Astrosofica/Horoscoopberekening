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
            const url = new URL(window.location);
            url.hash = tabId;
            window.location.href = url.toString();
            return;
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
            const url = new URL(window.location);
            url.hash = tabId;
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