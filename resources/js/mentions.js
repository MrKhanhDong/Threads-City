export function mentionAtCaret(value, position) {
    const before = value.slice(0, position);
    const match = /(?:^|[\s(])@([\p{L}\p{N}_-]{0,60})$/u.exec(before);
    return match ? { query: match[1], start: position - match[1].length - 1, end: position } : null;
}

export async function fetchMentionUsers(endpoint, query, fetcher = fetch) {
    const url = new URL(endpoint);
    url.searchParams.set('q', query);
    const response = await fetcher(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
    const data = await response.json();
    if (!response.ok || !Array.isArray(data.users)) throw new Error('Chưa tải được gợi ý nhắc đến. Hãy thử lại.');
    return data.users.filter(user => {
        if (!user || !Number.isSafeInteger(user.id) || user.id <= 0 || typeof user.handle !== 'string' || typeof user.name !== 'string' || typeof user.url !== 'string') return false;
        try { const profile = new URL(user.url, endpoint); return profile.origin === url.origin && ['http:', 'https:'].includes(profile.protocol); }
        catch { return false; }
    });
}

export function setupEditorMentions(editor, endpoint) {
    if (!endpoint) return;
    let timer, pending;
    editor.ui.registry.addAutocompleter('people', {
        trigger: '@', minChars: 0, columns: 1,
        fetch(pattern) {
            clearTimeout(timer); pending?.([]);
            return new Promise(resolve => {
                pending = resolve;
                timer = setTimeout(async () => {
                    try { resolve((await fetchMentionUsers(endpoint, pattern)).map(user => ({ type: 'autocompleteitem', value: JSON.stringify(user), text: `${user.handle} · ${user.name}` }))); }
                    catch { resolve([]); }
                    finally { if (pending === resolve) pending = null; }
                }, 200);
            });
        },
        onAction(api, range, value) {
            const user = JSON.parse(value);
            const link = editor.getDoc().createElement('a');
            link.href = user.url; link.textContent = user.handle; link.title = user.name;
            editor.selection.setRng(range);
            editor.insertContent(link.outerHTML + '&nbsp;');
            editor.save(); api.hide();
        },
    });
    editor.on('remove', () => { clearTimeout(timer); pending?.([]); });
}

export function setupCommentMentions(doc = document) {
    const endpoint = doc.querySelector('meta[name="mention-suggestions"]')?.content;
    if (!endpoint) return;
    let sequence = 0;
    doc.addEventListener('focusin', event => {
        const input = event.target.closest('textarea[name="body"], textarea[data-reply-input]');
        if (!input || !input.form || input.dataset.mentionsReady) return;
        input.dataset.mentionsReady = 'true';
        const list = doc.createElement('div'); list.className = 'mention-suggestions'; list.hidden = true;
        list.id = `mention-list-${++sequence}`; list.setAttribute('role', 'listbox'); list.setAttribute('aria-label', 'Gợi ý người dùng');
        input.after(list); input.setAttribute('aria-controls', list.id); input.setAttribute('aria-expanded', 'false'); input.setAttribute('aria-autocomplete', 'list');
        let timer, version = 0, users = [], selected = 0, range;
        const close = () => { version += 1; clearTimeout(timer); list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); };
        const highlight = () => {
            [...list.children].forEach((button, index) => button.setAttribute('aria-selected', String(index === selected)));
            input.setAttribute('aria-activedescendant', `${list.id}-${selected}`);
        };
        const choose = index => {
            const user = users[index];
            if (!user || !range) return;
            input.setRangeText(`${user.handle} `, range.start, range.end, 'end');
            let hidden = [...input.form.querySelectorAll('input[name="mention_ids[]"]')].find(item => item.value === String(user.id));
            if (!hidden) { hidden = doc.createElement('input'); hidden.type = 'hidden'; hidden.name = 'mention_ids[]'; hidden.value = String(user.id); input.form.append(hidden); }
            hidden.dataset.mentionLabel = user.handle;
            close(); input.focus(); input.dispatchEvent(new Event('input', { bubbles: true }));
        };
        input.addEventListener('input', () => {
            close(); range = mentionAtCaret(input.value, input.selectionStart);
            input.form.querySelectorAll('input[name="mention_ids[]"]').forEach(hidden => {
                if (hidden.dataset.mentionLabel && !input.value.includes(hidden.dataset.mentionLabel)) hidden.remove();
            });
            if (!range) return;
            const current = version;
            timer = setTimeout(async () => {
                try {
                    const results = await fetchMentionUsers(endpoint, range.query);
                    if (current !== version || doc.activeElement !== input) return;
                    users = results; selected = 0; list.replaceChildren();
                    users.forEach((user, index) => {
                        const button = doc.createElement('button'); button.type = 'button'; button.id = `${list.id}-${index}`; button.setAttribute('role', 'option');
                        button.textContent = `${user.handle} · ${user.name}`;
                        button.addEventListener('pointerdown', event => { event.preventDefault(); choose(index); }); list.append(button);
                    });
                    list.hidden = users.length === 0; input.setAttribute('aria-expanded', String(users.length > 0)); if (users.length) highlight();
                } catch { close(); }
            }, 200);
        });
        input.addEventListener('keydown', event => {
            if (list.hidden) return;
            if (event.key === 'Escape') { event.preventDefault(); close(); }
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') { event.preventDefault(); selected = (selected + (event.key === 'ArrowDown' ? 1 : -1) + users.length) % users.length; highlight(); }
            if (event.key === 'Enter') { event.preventDefault(); choose(selected); }
        });
        input.addEventListener('blur', close);
    });
}
