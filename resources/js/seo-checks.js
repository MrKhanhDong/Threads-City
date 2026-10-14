import { requestErrorMessage } from './validation-messages.js';

export function renderSeoChecks(list, checks) {
    if (!checks || Array.isArray(checks) || typeof checks !== 'object' || !Object.keys(checks).length
        || Object.values(checks).some(value => value !== true && value !== false && value !== null)) {
        throw new Error('Kết quả kiểm tra SEO không hợp lệ. Vui lòng thử lại.');
    }
    list.replaceChildren();
    for (const [label, pass] of Object.entries(checks)) {
        const item = document.createElement('li');
        item.classList.toggle('pass', pass === true);
        const icon = document.createElement('span');
        icon.textContent = pass === null ? '–' : pass ? '✓' : '○';
        icon.setAttribute('aria-hidden', 'true');
        item.setAttribute('aria-label', label + (pass === null ? ': chưa áp dụng' : pass ? ': đạt' : ': cần bổ sung'));
        item.append(icon, document.createTextNode(label + (pass === null ? ' (chưa có ảnh)' : '')));
        list.append(item);
    }
}

export function setupSeoChecks(form, csrf, getContent) {
    const panel = form.querySelector('[data-seo-checks]');
    if (!panel) return () => {};
    const list = panel.querySelector('[data-seo-check-list]');
    const status = panel.querySelector('[data-seo-check-status]');
    let timer, controller, revision = 0;
    const update = async current => {
        controller = new AbortController();
        const values = {};
        ['title', 'slug', 'excerpt', 'seo_title', 'meta_description', 'focus_keyword', 'cover_media_id']
            .forEach(name => { values[name] = form.elements.namedItem(name)?.value || ''; });
        values.content = getContent();
        try {
            const response = await fetch(panel.dataset.url, {
                method: 'POST', signal: controller.signal, body: JSON.stringify(values),
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await response.json();
            if (current !== revision) return;
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Chưa kiểm tra được SEO.');
            renderSeoChecks(list, data.checks);
            list.hidden = false;
            status.textContent = '';
            status.hidden = true;
            panel.removeAttribute('aria-busy');
        } catch (error) {
            if (error.name === 'AbortError' || current !== revision) return;
            status.textContent = 'Chưa cập nhật: ' + requestErrorMessage(error, 'Kiểm tra kết nối rồi thử nhập lại.');
            status.hidden = false;
            list.hidden = true;
            panel.removeAttribute('aria-busy');
        }
    };
    const schedule = () => {
        revision += 1;
        const current = revision;
        clearTimeout(timer);
        controller?.abort();
        status.textContent = '';
        status.hidden = true;
        panel.setAttribute('aria-busy', 'true');
        timer = setTimeout(() => update(current), 800);
    };
    form.addEventListener('input', schedule);
    form.addEventListener('change', schedule);
    window.addEventListener('focus', schedule);
    schedule();
    return schedule;
}
