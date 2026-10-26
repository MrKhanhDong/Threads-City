export function setupSidebarFeeds() {
    const controls = [...document.querySelectorAll('[data-feed-visibility]')];
    if (!controls.length) return;
    let hiddenFeeds = [];
    try {
        const saved = JSON.parse(localStorage.getItem('threads-hidden-feeds') || '[]');
        if (Array.isArray(saved)) hiddenFeeds = saved.filter(key => typeof key === 'string');
    } catch {}
    const update = () => {
        controls.forEach(control => { control.checked = !hiddenFeeds.includes(control.dataset.feedVisibility); });
        document.querySelectorAll('[data-feed-key]').forEach(link => {
            link.hidden = hiddenFeeds.includes(link.dataset.feedKey) && !link.classList.contains('is-active');
        });
    };
    controls.forEach(control => control.addEventListener('change', () => {
        const key = control.dataset.feedVisibility;
        hiddenFeeds = hiddenFeeds.filter(item => item !== key);
        if (!control.checked) hiddenFeeds.push(key);
        try { localStorage.setItem('threads-hidden-feeds', JSON.stringify(hiddenFeeds)); } catch {}
        update();
    }));
    update();
}
