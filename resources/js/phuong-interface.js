export function setupPhuongComposerBridge(Modal, pageDocument = document, pageWindow = window) {
    pageDocument.addEventListener('click', event => {
        const compose = event.target.closest('[data-compose="new"]');
        if (!compose) return;
        event.preventDefault();
        if (typeof pageWindow.checkAuth === 'function' && !pageWindow.checkAuth('tạo bài viết mới')) return;
        const composer = pageDocument.getElementById('newThreadModal');
        if (composer) Modal.getOrCreateInstance(composer).show();
    });
}
