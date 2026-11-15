export function setupAdminNavigation(Offcanvas, pageDocument = document, pageWindow = window) {
    const navigation = pageDocument.querySelector('[data-admin-navigation]');
    if (!navigation) return;

    const toggles = pageDocument.querySelectorAll('[data-admin-menu-toggle]');
    const desktop = pageWindow.matchMedia('(min-width: 992px)');
    const updateExpanded = expanded => toggles.forEach(toggle => {
        toggle.setAttribute('aria-expanded', String(expanded));
        if (toggle.hasAttribute('aria-label')) {
            toggle.setAttribute('aria-label', expanded ? 'Đóng menu quản trị' : 'Mở menu quản trị');
        }
    });

    navigation.addEventListener('show.bs.offcanvas', () => updateExpanded(true));
    navigation.addEventListener('hide.bs.offcanvas', () => updateExpanded(false));
    navigation.addEventListener('click', event => {
        if (!desktop.matches && event.target.closest('a[href]')) Offcanvas.getInstance(navigation)?.hide();
    });
    desktop.addEventListener('change', event => {
        if (event.matches) Offcanvas.getInstance(navigation)?.hide();
    });

    const mobileFilters = pageWindow.matchMedia('(max-width: 700px)');
    pageDocument.querySelectorAll('[data-admin-filter-disclosure]').forEach(disclosure => {
        disclosure.open = !mobileFilters.matches || disclosure.dataset.hasFilters === 'true';
        mobileFilters.addEventListener('change', event => {
            if (!event.matches) disclosure.open = true;
        });
    });
    pageDocument.addEventListener('invalid', event => {
        const disclosure = event.target.closest('details');
        if (disclosure) disclosure.open = true;
    }, true);
}
