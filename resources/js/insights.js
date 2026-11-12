export function setupInsights(pageDocument = document, pageWindow = window) {
    const root = pageDocument.querySelector('[data-insights]');
    if (!root) return;
    const links = [...root.querySelectorAll('[data-insight-metric]')];
    const panels = [...root.querySelectorAll('[data-insight-panel]')];
    links.forEach(link => link.addEventListener('click', event => {
        if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const selected = link.dataset.insightMetric;
        if (!panels.some(panel => panel.dataset.insightPanel === selected)) return;
        event.preventDefault();
        links.forEach(item => {
            const active = item === link;
            item.classList.toggle('is-active', active);
            if (active) item.setAttribute('aria-current', 'true');
            else item.removeAttribute('aria-current');
        });
        panels.forEach(panel => { panel.hidden = panel.dataset.insightPanel !== selected; });
        root.querySelector('[data-insight-metric-input]').value = selected;
        const label = panels.find(panel => !panel.hidden).getAttribute('aria-label');
        root.querySelector('[data-insight-announcement]').textContent = `Đang xem ${label}`;
        root.querySelectorAll('.journal-pagination a').forEach(paginationLink => {
            const url = new URL(paginationLink.href);
            url.searchParams.set('metric', selected);
            paginationLink.href = url.href;
        });
        pageWindow.history.replaceState(null, '', link.href);
    }));
    root.querySelector('[data-insights-print]')?.addEventListener('click', () => pageWindow.print());
}
