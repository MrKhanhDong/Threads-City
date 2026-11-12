import { threadRequest } from './thread-api.js';

export function applicationServerKey(value) {
    const binary = atob(value.replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from(binary, character => character.charCodeAt(0));
}

export function setupPwa(doc = document, win = window) {
    const config = doc.querySelector('[data-pwa-config]');
    if (!config || !win.isSecureContext || !('serviceWorker' in win.navigator) || win.top !== win) return;
    const csrf = doc.querySelector('meta[name="csrf-token"]')?.content || '';
    const button = doc.querySelector('[data-push-toggle]');
    const status = doc.querySelector('[data-push-status]');
    const install = doc.querySelectorAll('[data-pwa-install]');
    const ownerKey = 'threads-push-owner';
    const owner = () => { try { return win.localStorage.getItem(ownerKey); } catch { return null; } };
    const setOwner = value => { try { if (value) win.localStorage.setItem(ownerKey, value); else win.localStorage.removeItem(ownerKey); } catch {} };
    let registration, subscription, deferred, busy = false;
    const request = (url, options) => threadRequest(url, { ...options, csrf });
    const show = message => { if (status) status.textContent = message; };
    const refresh = () => {
        if (button) { button.disabled = busy || !registration; button.textContent = subscription ? 'Tắt thông báo trên thiết bị này' : 'Bật thông báo trên thiết bị này'; }
    };
    win.addEventListener('beforeinstallprompt', event => { event.preventDefault(); deferred = event; install.forEach(control => { control.hidden = false; }); });
    install.forEach(control => control.addEventListener('click', async () => {
        if (!deferred) return;
        control.disabled = true;
        try { await deferred.prompt(); await deferred.userChoice; }
        finally { deferred = null; install.forEach(item => { item.hidden = true; item.disabled = false; }); }
    }));
    win.addEventListener('appinstalled', () => { deferred = null; install.forEach(control => { control.hidden = true; }); });
    const revoke = async () => {
        if (!subscription) return;
        await request(config.dataset.pushUrl, { method: 'DELETE', body: { endpoint: subscription.endpoint } });
        await subscription.unsubscribe(); subscription = null; setOwner(null);
    };
    const reconcile = async () => {
        if (!registration || busy) return;
        busy = true; refresh();
        try {
            subscription = await registration.pushManager?.getSubscription();
            if (subscription && config.dataset.publicKey && subscription.options?.applicationServerKey) {
                const currentKey = new Uint8Array(subscription.options.applicationServerKey);
                const expectedKey = applicationServerKey(config.dataset.publicKey);
                if (currentKey.length !== expectedKey.length || currentKey.some((byte, index) => byte !== expectedKey[index])) {
                    await revoke();
                }
            }
            if (subscription && owner() !== config.dataset.userId) {
                await subscription.unsubscribe(); subscription = null; setOwner(null);
            }
            if (subscription && config.dataset.userId && config.dataset.publicKey) {
                await request(config.dataset.pushUrl, { method: 'POST', body: { ...subscription.toJSON(), account_id: Number(config.dataset.userId) } });
            }
            show(subscription ? 'Thông báo đang bật trên thiết bị này.' : 'Bạn có thể bật thông báo khi muốn.');
        } catch { show('Chưa đồng bộ được đăng ký thông báo. Hãy thử lại khi có mạng.'); }
        finally { busy = false; refresh(); }
    };
    win.navigator.serviceWorker.register(config.dataset.workerUrl, { updateViaCache: 'none' })
        .then(async value => {
            registration = value;
            await win.navigator.serviceWorker.ready;
            if (!('PushManager' in win) || !('Notification' in win)) { show('Trình duyệt chưa hỗ trợ Push. Trên iPhone/iPad, hãy thêm Threads vào màn hình chính và mở từ biểu tượng đó.'); return; }
            if (config.dataset.userId && config.dataset.publicKey) { if (button) button.hidden = false; await reconcile(); }
            else show(config.dataset.userId ? 'Máy chủ chưa cấu hình Web Push.' : 'Đăng nhập để bật thông báo.');
        }).catch(() => show('Chưa khởi tạo được ứng dụng. Hãy tải lại trang khi có mạng.'));
    button?.addEventListener('click', async () => {
        if (busy || !registration) return;
        busy = true; refresh();
        try {
            if (subscription) { await revoke(); show('Đã tắt thông báo trên thiết bị này.'); }
            else {
                const permission = await win.Notification.requestPermission();
                if (permission !== 'granted') { show('Chưa có quyền thông báo. Bạn có thể thay đổi trong cài đặt trình duyệt.'); return; }
                subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: applicationServerKey(config.dataset.publicKey) });
                try { await request(config.dataset.pushUrl, { method: 'POST', body: { ...subscription.toJSON(), account_id: Number(config.dataset.userId) } }); }
                catch (error) { await subscription.unsubscribe(); subscription = null; throw error; }
                setOwner(config.dataset.userId); show('Đã bật thông báo trên thiết bị này.');
            }
        } catch (error) { show(error.message || 'Chưa thay đổi được cài đặt thông báo.'); }
        finally { busy = false; refresh(); }
    });
    doc.addEventListener('submit', event => {
        const form = event.target.closest('form');
        if (!form || form.action !== config.dataset.logoutUrl || form.dataset.pushRevoked || !subscription) return;
        event.preventDefault();
        if (form.dataset.pushRevoking) return;
        form.dataset.pushRevoking = 'true';
        void (async () => {
            try { await revoke(); } catch { setOwner(null); }
            finally { form.dataset.pushRevoked = 'true'; form.requestSubmit(); }
        })();
    });
    win.addEventListener('storage', event => { if (event.key === ownerKey && registration && event.newValue !== config.dataset.userId) { subscription = null; refresh(); show('Tài khoản trên thiết bị đã thay đổi. Hãy tải lại trang trước khi bật thông báo.'); if (button) button.disabled = true; } });
}
