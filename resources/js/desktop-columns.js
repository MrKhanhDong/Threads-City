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
    const secondary = workspace.querySelector('[data-column-slot="secondary"]');
    const primary = workspace.querySelector('[data-column-slot="primary"]');
    const frame = workspace.querySelector('[data-desktop-column-frame]');
    const choices = [...workspace.querySelectorAll('[data-column-choice]')];
    const select = workspace.querySelector('[data-column-select]');
    const title = workspace.querySelector('[data-column-title]');
    const status = workspace.querySelector('[data-column-status]');
    const announcement = workspace.querySelector('[data-column-announcement]');
    const addButton = workspace.querySelector('.desktop-column-add');
    const desktop = pageWindow.matchMedia('(min-width: 1101px)');
    const storageKey = 'threads-desktop-columns';
    let selected = null;
    let reversed = false;
    let dragged = null;

    const save = () => {
        try {
            if (selected) pageWindow.sessionStorage.setItem(storageKey, JSON.stringify({ selected, reversed }));
            else pageWindow.sessionStorage.removeItem(storageKey);
        } catch {}
    };
    const announce = text => { announcement.textContent = text; };
    const setOrder = value => {
        reversed = value;
        workspace.classList.toggle('is-reversed', reversed);
        save();
    };
    const endDrag = () => {
        dragged = null;
        workspace.classList.remove('is-reordering');
        workspace.querySelectorAll('[data-column-slot]').forEach(column => column.classList.remove('is-dragging', 'is-drop-target'));
    };
    const open = key => {
        if (!desktop.matches) return;
        const choice = choices.find(item => item.dataset.columnChoice === key);
        if (!choice) return;
        const url = new URL(choice.dataset.columnUrl, pageWindow.location.href);
        if (url.origin !== pageWindow.location.origin) return;
        const previousScroll = selected ? primary.scrollTop : pageWindow.scrollY;
        selected = key;
        secondary.hidden = false;
        secondary.setAttribute('aria-busy', 'true');
        workspace.classList.add('is-split');
        pageDocument.body.classList.add('journal-split-active');
        primary.scrollTop = previousScroll;
        select.value = key;
        title.textContent = choice.textContent.trim();
        frame.title = `Cột ${title.textContent}`;
        status.textContent = 'Đang tải nội dung…';
        status.hidden = false;
        frame.src = url.href;
        save();
        announce(`Đã mở cột ${title.textContent}.`);
    };
    const close = (returnFocus = true) => {
        if (!selected) return;
        const previousScroll = primary.scrollTop;
        selected = null;
        secondary.hidden = true;
        secondary.removeAttribute('aria-busy');
        frame.src = 'about:blank';
        workspace.classList.remove('is-split');
        pageDocument.body.classList.remove('journal-split-active');
        endDrag();
        setOrder(false);
        if (returnFocus) addButton.focus();
        pageWindow.requestAnimationFrame(() => pageWindow.scrollTo({ top: previousScroll, behavior: 'instant' }));
        announce('Đã gỡ cột bổ sung.');
    };

    choices.forEach(choice => choice.addEventListener('click', () => {
        open(choice.dataset.columnChoice);
        if (!secondary.hidden) {
            Dropdown?.getInstance(addButton)?.hide();
            select.focus();
        }
    }));
    workspace.querySelector('[data-column-feed-group]')?.addEventListener('toggle', () => Dropdown?.getInstance(addButton)?.update());
    select.addEventListener('change', () => open(select.value));
    workspace.querySelector('[data-column-close]').addEventListener('click', () => close());
    frame.addEventListener('load', () => {
        if (!selected) return;
        try {
            if (frame.contentWindow.location.href === 'about:blank') return;
            const columnDocument = frame.contentDocument;
            const heading = columnDocument.querySelector('.journal-topbar > span')?.textContent.trim() || columnDocument.title.replace(/\s+—\s+Threads$/, '');
            if (heading) { title.textContent = heading; frame.title = `Cột ${heading}`; }
            status.hidden = true;
        } catch {
            status.textContent = 'Chưa mở được nội dung. Hãy chọn lại tab để thử lại.';
            status.hidden = false;
        }
        secondary.removeAttribute('aria-busy');
    });
    desktop.addEventListener('change', event => { if (!event.matches) close(false); });

    workspace.querySelectorAll('[data-column-drag]').forEach(handle => {
        handle.addEventListener('click', () => {
            if (!selected || !desktop.matches) return;
            setOrder(!reversed);
            announce('Đã đổi vị trí hai cột.');
        });
        handle.addEventListener('dragstart', event => {
            if (!selected || !desktop.matches) { event.preventDefault(); return; }
            dragged = handle.dataset.columnDrag;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dragged);
            workspace.classList.add('is-reordering');
            workspace.querySelector(`[data-column-slot="${dragged}"]`).classList.add('is-dragging');
        });
        handle.addEventListener('dragend', endDrag);
    });
    workspace.querySelectorAll('[data-column-slot]').forEach(column => {
        column.addEventListener('dragover', event => {
            if (!dragged || dragged === column.dataset.columnSlot) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            column.classList.add('is-drop-target');
        });
        column.addEventListener('dragleave', event => {
            if (!column.contains(event.relatedTarget)) column.classList.remove('is-drop-target');
        });
        column.addEventListener('drop', event => {
            if (!dragged) return;
            event.preventDefault();
            if (dragged !== column.dataset.columnSlot) {
                setOrder(!reversed);
                announce('Đã đổi vị trí hai cột.');
            }
            endDrag();
        });
    });
    if (desktop.matches) {
        try {
            const saved = JSON.parse(pageWindow.sessionStorage.getItem(storageKey) || 'null');
            if (saved && typeof saved.selected === 'string') { open(saved.selected); if (selected) setOrder(saved.reversed === true); }
        } catch {}
    }
}
