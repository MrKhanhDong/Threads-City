export function setupSidebarDrawer(Offcanvas, pageDocument = document, pageWindow = window) {
    const drawer = pageDocument.querySelector('[data-sidebar-drawer]');
    const trigger = pageDocument.querySelector('[data-bs-target="#journal-sidebar-drawer"]');
    if (!drawer || !trigger) return;

    drawer.addEventListener('show.bs.offcanvas', () => trigger.setAttribute('aria-expanded', 'true'));
    drawer.addEventListener('hide.bs.offcanvas', () => trigger.setAttribute('aria-expanded', 'false'));
    drawer.addEventListener('click', event => {
        const compose = event.target.closest('[data-compose]');
        if (!compose || !drawer.classList.contains('show')) return;
        event.preventDefault();
        event.stopPropagation();
        drawer.addEventListener('hidden.bs.offcanvas', () => compose.click(), { once: true });
        Offcanvas.getOrCreateInstance(drawer).hide();
    });
    pageWindow.matchMedia('(min-width: 1101px)').addEventListener('change', event => {
        if (event.matches) Offcanvas.getInstance(drawer)?.hide();
    });
}
