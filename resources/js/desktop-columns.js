import { setupColumnDrag } from './column-drag.js';

export function setupDesktopColumns(pageDocument = document, pageWindow = window, Dropdown = null) {
    try {
        if (pageWindow.frameElement?.hasAttribute('data-desktop-column-frame')) {
            pageDocument.addEventListener('click', event => {
                const link = event.target.closest('a[href]');
                if (link && new URL(link.href, pageWindow.location.href).pathname.startsWith('/auth/google')) link.target = '_top';
            });
            return;
        }
    } catch { return; }

    const workspace = pageDocument.querySelector('[data-column-workspace]');
    if (!workspace) return;
    const primary = workspace.querySelector('[data-column-slot="primary"]');
    const primaryScroller = primary.querySelector?.('.journal-main') || primary;
    const panels = [...workspace.querySelectorAll('.desktop-secondary-column')].map(element => ({
        id: element.dataset.columnSlot,
        element,
        frame: element.querySelector('[data-desktop-column-frame]'),
        select: element.querySelector('[data-column-select]'),
        title: element.querySelector('[data-column-title]'),
        status: element.querySelector('[data-column-status]'),
        header: element.querySelector('[data-column-drag]'),
        selected: null,
    }));
    const choices = [...workspace.querySelectorAll('[data-column-choice]')];
    const announcement = workspace.querySelector('[data-column-announcement]');
    const addButton = workspace.querySelector('.desktop-column-add');
    const countLabel = workspace.querySelector('[data-column-count-label]');
    const limitMessage = workspace.querySelector('[data-column-limit]');
    const desktop = pageWindow.matchMedia('(min-width: 1101px)');
    const storageKey = 'threads-desktop-columns';
    const maximumColumns = 3;
    let order = ['primary'];
    let stopDrag = () => {};

    const announce = text => { announcement.textContent = text; };
    const save = () => {
        try {
            const columns = panels.filter(panel => panel.selected).map(panel => ({ id: panel.id, tab: panel.selected }));
            if (columns.length) pageWindow.sessionStorage.setItem(storageKey, JSON.stringify({ columns, order }));
            else pageWindow.sessionStorage.removeItem(storageKey);
        } catch {}
    };
    const updateLayout = () => {
        const count = order.length;
        workspace.dataset.columnCount = String(count);
        workspace.classList.toggle('is-split', count > 1);
        pageDocument.body.classList.toggle('journal-split-active', count > 1);
        workspace.querySelectorAll('[data-column-slot]').forEach(element => {
            const index = order.indexOf(element.dataset.columnSlot);
            element.style.order = String(Math.max(0, index));
        });
        choices.forEach(choice => { choice.disabled = count >= maximumColumns; });
        workspace.querySelectorAll('[data-column-drag]').forEach(header => { header.tabIndex = desktop.matches && count > 1 ? 0 : -1; });
        countLabel.textContent = `${count}/${maximumColumns}`;
        if (limitMessage) limitMessage.hidden = count < maximumColumns;
        addButton.setAttribute('aria-label', count >= maximumColumns ? 'Đa nhiệm: đã mở tối đa 3 cột' : 'Thêm cột');
        addButton.setAttribute('title', count >= maximumColumns ? 'Đã mở tối đa 3 cột' : 'Thêm cột');
    };
    const endDrag = () => stopDrag();
    const open = (key, destination = null) => {
        if (!desktop.matches) return null;
        const choice = choices.find(item => item.dataset.columnChoice === key);
        if (!choice) return null;
        const url = new URL(choice.dataset.columnUrl, pageWindow.location.href);
        if (url.origin !== pageWindow.location.origin) return null;
        const panel = destination || panels.find(item => !item.selected);
        if (!panel || (!panel.selected && order.length >= maximumColumns)) {
            announce('Đã mở tối đa 3 cột. Gỡ một cột để mở thêm.');
            return null;
        }
        const previousScroll = order.length > 1 ? primaryScroller.scrollTop : pageWindow.scrollY;
        if (!panel.selected) order.push(panel.id);
        panel.selected = key;
        panel.element.hidden = false;
        panel.element.setAttribute('aria-busy', 'true');
        panel.select.value = key;
        panel.title.textContent = choice.textContent.trim();
        panel.frame.title = `Cột ${panel.title.textContent}`;
        panel.status.textContent = 'Đang tải nội dung…';
        panel.status.hidden = false;
        updateLayout();
        primaryScroller.scrollTop = previousScroll;
        panel.frame.src = url.href;
        save();
        announce(`Đã mở cột ${panel.title.textContent}. ${order.length} trên 3 cột.`);
        return panel;
    };
    const releasePanel = panel => {
        panel.selected = null;
        panel.element.hidden = true;
        panel.element.removeAttribute('aria-busy');
        panel.frame.src = 'about:blank';
        order = order.filter(id => id !== panel.id);
    };
    const close = (panel, returnFocus = true) => {
        if (!panel.selected) return;
        const previousScroll = primaryScroller.scrollTop;
        releasePanel(panel);
        endDrag();
        updateLayout();
        save();
        if (returnFocus) addButton.focus();
        if (order.length === 1) pageWindow.requestAnimationFrame(() => pageWindow.scrollTo({ top: previousScroll, behavior: 'instant' }));
        announce(`Đã gỡ cột bổ sung. Còn ${order.length} cột.`);
    };
    choices.forEach(choice => choice.addEventListener('click', () => {
        const panel = open(choice.dataset.columnChoice);
        if (panel) { Dropdown?.getInstance(addButton)?.hide(); panel.header.focus(); }
    }));
    workspace.querySelector('[data-column-feed-group]')?.addEventListener('toggle', () => Dropdown?.getInstance(addButton)?.update());
    panels.forEach(panel => {
        panel.select.addEventListener('change', () => open(panel.select.value, panel));
        panel.element.querySelector('[data-column-close]').addEventListener('click', () => close(panel));
        panel.frame.addEventListener('load', () => {
            if (!panel.selected) return;
            try {
                if (panel.frame.contentWindow.location.href === 'about:blank') return;
                const columnDocument = panel.frame.contentDocument;
                const heading = columnDocument.querySelector('.journal-topbar > span')?.textContent.trim() || columnDocument.title.replace(/\s+—\s+Threads$/, '');
                if (heading) panel.frame.title = `Cột ${heading}`;
                panel.status.hidden = true;
            } catch {
                panel.status.textContent = 'Chưa mở được nội dung. Hãy chọn lại tab để thử lại.';
                panel.status.hidden = false;
            }
            panel.element.removeAttribute('aria-busy');
        });
    });
    desktop.addEventListener('change', event => {
        if (event.matches || order.length === 1) return;
        const previousScroll = primaryScroller.scrollTop;
        panels.filter(panel => panel.selected).forEach(releasePanel);
        endDrag(); updateLayout(); save();
        pageWindow.requestAnimationFrame(() => pageWindow.scrollTo({ top: previousScroll, behavior: 'instant' }));
    });
    stopDrag = setupColumnDrag({
        workspace, pageWindow,
        canDrag: () => desktop.matches && order.length > 1,
        getOrder: () => order,
        setOrder: next => { order = next; updateLayout(); save(); announce('Đã đổi vị trí các cột.'); },
    });

    updateLayout();
    if (desktop.matches) {
        try {
            const saved = JSON.parse(pageWindow.sessionStorage.getItem(storageKey) || 'null');
            if (Array.isArray(saved?.columns)) {
                for (const column of saved.columns.slice(0, maximumColumns - 1)) {
                    const panel = panels.find(item => item.id === column?.id && !item.selected);
                    if (panel && typeof column.tab === 'string') open(column.tab, panel);
                }
                if (Array.isArray(saved.order)) {
                    const restored = [...new Set(saved.order.filter(id => order.includes(id)))];
                    order = [...restored, ...order.filter(id => !restored.includes(id))];
                }
            } else if (typeof saved?.selected === 'string') {
                open(saved.selected);
                if (saved.reversed === true) order.reverse();
            }
            updateLayout(); save();
        } catch {}
    }
}
