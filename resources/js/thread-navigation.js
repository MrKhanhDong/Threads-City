export function setupThreadNavigation(doc = document, win = window) {
    const controls = 'a, button, input, textarea, select, label, summary, details, form, video, audio, [contenteditable], [role="button"], [role="link"], [data-media-gallery], [data-player], [data-poll-id], .threads-inner-quote-card';
    doc.addEventListener('click', event => {
        if (event.defaultPrevented || event.button > 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const card = event.target.closest?.('[data-thread-url]');
        if (!card || event.target.closest(controls) || event.target.closest('.modal, dialog')) return;
        if (win.getSelection?.()?.toString()) return;
        const current = doc.querySelector('[data-reply-home]')?.dataset.replyHome;
        if (current === card.dataset.threadId) return;
        win.location.assign(card.dataset.threadUrl);
    });
}
