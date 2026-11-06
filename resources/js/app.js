const revealElements = [...document.querySelectorAll('[data-reveal]')];
const reducedMotionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');

document.querySelectorAll('[data-reveal-stagger]').forEach((group) => {
    group.querySelectorAll('[data-reveal]').forEach((element, index) => {
        element.style.setProperty('--reveal-delay', `${Math.min(index * 75, 375)}ms`);
    });
});

if (reducedMotionPreference.matches || !('IntersectionObserver' in window)) {
    revealElements.forEach((element) => element.classList.add('is-visible'));
} else {
    document.documentElement.classList.add('reveal-enabled');

    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -36px 0px',
    });

    revealElements.forEach((element) => revealObserver.observe(element));
}

const siteDrawer = document.querySelector('[data-site-drawer]');
const siteDrawerToggles = document.querySelectorAll('[data-site-drawer-toggle]');
const siteDrawerCloses = document.querySelectorAll('[data-site-drawer-close]');

const openSiteDrawer = () => {
    if (!siteDrawer) {
        return;
    }

    siteDrawer.classList.add('is-open');
    document.body.classList.add('has-drawer-open');
};

const closeSiteDrawer = () => {
    if (!siteDrawer) {
        return;
    }

    siteDrawer.classList.remove('is-open');
    document.body.classList.remove('has-drawer-open');
};

siteDrawerToggles.forEach((toggle) => toggle.addEventListener('click', () => {
    if (!siteDrawer) {
        return;
    }

    if (siteDrawer.classList.contains('is-open')) {
        closeSiteDrawer();
        return;
    }

    openSiteDrawer();
}));

siteDrawerCloses.forEach((close) => close.addEventListener('click', closeSiteDrawer));

if (siteDrawer) {
    siteDrawer.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeSiteDrawer));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSiteDrawer();
            closeSearchOverlay();
        }
    });
}

const searchOverlay = document.querySelector('[data-search-overlay]');
const searchOverlayToggles = document.querySelectorAll('[data-search-overlay-toggle]');
const searchOverlayCloses = document.querySelectorAll('[data-search-overlay-close]');
let lastFocusedElement = null;

const openSearchOverlay = () => {
    if (!searchOverlay) {
        return;
    }

    lastFocusedElement = document.activeElement;
    searchOverlay.classList.add('is-open');
    document.body.classList.add('has-overlay-open');
    const input = searchOverlay.querySelector('input[name="q"]');
    window.setTimeout(() => input?.focus(), 80);
};

const closeSearchOverlay = () => {
    if (!searchOverlay) {
        return;
    }

    searchOverlay.classList.remove('is-open');
    document.body.classList.remove('has-overlay-open');
    if (lastFocusedElement instanceof HTMLElement) {
        lastFocusedElement.focus({ preventScroll: true });
    }
};

searchOverlayToggles.forEach((toggle) => toggle.addEventListener('click', openSearchOverlay));
searchOverlayCloses.forEach((close) => close.addEventListener('click', closeSearchOverlay));

if (searchOverlay) {
    searchOverlay.addEventListener('keydown', (event) => {
        if (event.key !== 'Tab') {
            return;
        }

        const focusable = [...searchOverlay.querySelectorAll('input, button')].filter((el) => !el.hasAttribute('disabled'));
        if (focusable.length === 0) {
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
            return;
        }

        if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
}

const filterToggle = document.querySelector('[data-filter-toggle]');
const filterPanel = document.querySelector('[data-filter-panel]');

if (filterToggle && filterPanel) {
    filterToggle.addEventListener('click', () => {
        const isOpen = filterPanel.classList.contains('is-open');
        filterToggle.setAttribute('aria-expanded', String(!isOpen));
        filterPanel.classList.toggle('is-open', !isOpen);
    });
}

const adminDrawer = document.querySelector('[data-admin-drawer]');
const adminDrawerToggles = document.querySelectorAll('[data-admin-drawer-toggle]');
const adminDrawerCloses = document.querySelectorAll('[data-admin-drawer-close]');

const openAdminDrawer = () => {
    if (!adminDrawer) {
        return;
    }

    adminDrawer.classList.add('is-open');
    document.body.classList.add('has-admin-drawer-open');
};

const closeAdminDrawer = () => {
    if (!adminDrawer) {
        return;
    }

    adminDrawer.classList.remove('is-open');
    document.body.classList.remove('has-admin-drawer-open');
};

adminDrawerToggles.forEach((toggle) => toggle.addEventListener('click', () => {
    if (!adminDrawer) {
        return;
    }

    if (adminDrawer.classList.contains('is-open')) {
        closeAdminDrawer();
        return;
    }

    openAdminDrawer();
}));

adminDrawerCloses.forEach((close) => close.addEventListener('click', closeAdminDrawer));

if (adminDrawer) {
    adminDrawer.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeAdminDrawer));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAdminDrawer();
        }
    });
}

window.addEventListener('pageshow', () => {
    closeSiteDrawer();
    closeSearchOverlay();
    closeAdminDrawer();
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = button.closest('.login-input-wrap')?.querySelector('input');

        if (!input) {
            return;
        }

        const shouldShow = input.type === 'password';
        input.type = shouldShow ? 'text' : 'password';
        button.classList.toggle('is-visible', shouldShow);
        button.setAttribute('aria-pressed', String(shouldShow));
        button.setAttribute('aria-label', shouldShow ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    });
});
