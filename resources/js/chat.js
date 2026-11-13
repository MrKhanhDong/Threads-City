export function chatError(data, status) {
    return Object.values(data?.errors || {}).flat()[0]
        || (status === 419 ? 'Phiên đăng nhập đã hết hạn. Tải lại trang để tiếp tục.' : '')
        || (status === 401 ? 'Bạn cần đăng nhập lại để tiếp tục.' : '')
        || data?.message || 'Chưa thực hiện được. Vui lòng thử lại.';
}

export function recordingType(Recorder) {
    return ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/webm']
        .find(type => Recorder.isTypeSupported(type));
}

export function insertEmoji(text, emoji, start, end = start) {
    return { value: text.slice(0, start) + emoji + text.slice(end), caret: start + emoji.length };
}

export function setupChat(doc = document, win = window) {
    const csrf = doc.querySelector('meta[name="csrf-token"]')?.content;
    const root = doc.querySelector('[data-chat-app]');
    const badgeLink = doc.querySelector('[data-chat-unread-url]');
    if (!root && !badgeLink) return;
    const lifetime = new AbortController();
    doc.addEventListener('click', event => {
        if (win.matchMedia('(hover: none)').matches) {
            const selected = event.target.closest('[data-chat-message]');
            root?.querySelectorAll('[data-chat-message].is-selected').forEach(item => {
                if (item !== selected) item.classList.remove('is-selected');
            });
            selected?.classList.add('is-selected');
        }
        root?.querySelectorAll('[data-chat-actions][open]').forEach(menu => {
            if (!menu.contains(event.target)) menu.removeAttribute('open');
        });
    }, { signal: lifetime.signal });
    root?.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        root.querySelectorAll('[data-chat-actions][open]').forEach(menu => {
            menu.removeAttribute('open');
            menu.querySelector('summary').focus();
        });
    }, { signal: lifetime.signal });
    let timer, expired = false;
    const updateBadge = (count, requests = 0) => {
        const total = count + requests;
        doc.querySelectorAll('[data-chat-unread]').forEach(badge => {
            badge.hidden = !total;
            badge.textContent = total > 99 ? '99+' : String(total);
            badge.setAttribute('aria-label', `${total} tin nhắn chưa đọc hoặc đang chờ`);
        });
    };
    const request = async (url, options = {}) => {
        const response = await win.fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: lifetime.signal, ...options,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }), ...options.headers } });
        let data;
        try { data = await response.json(); } catch { throw new Error('Máy chủ chưa trả về dữ liệu hợp lệ. Vui lòng thử lại.'); }
        if (!response.ok) {
            if ([401, 419].includes(response.status)) expired = true;
            const error = new Error(chatError(data, response.status)); error.status = response.status; throw error;
        }
        return data;
    };
    const refreshBadge = async () => {
        if (badgeLink) { const data = await request(badgeLink.dataset.chatUnreadUrl); updateBadge(data.unread_count, data.request_count || 0); }
    };
    const messages = root?.querySelector('[data-chat-messages]');
    const form = root?.querySelector('[data-chat-form]');
    const text = root?.querySelector('[data-chat-text]');
    const status = root?.querySelector('[data-chat-status]');
    const errorBox = root?.querySelector('[data-chat-error]');
    const showError = error => {
        if (error?.name === 'AbortError') return;
        if (errorBox) { errorBox.textContent = error.message; errorBox.hidden = false; }
        else if (status) status.textContent = error.message;
    };
    const nearBottom = () => messages && messages.scrollHeight - messages.scrollTop - messages.clientHeight < 80;
    const scrollBottom = () => { if (messages) messages.scrollTop = messages.scrollHeight; };
    const messageElements = () => [...(messages?.querySelectorAll('[data-chat-message]') || [])];
    const latestId = () => Math.max(0, ...messageElements().map(item => Number(item.dataset.chatMessage)));
    const merge = (html, forceBottom = false) => {
        if (!messages) return;
        const pinned = nearBottom() || forceBottom;
        const previousTop = messages.scrollTop;
        const fragment = doc.createElement('template');
        fragment.innerHTML = html;
        for (const incoming of fragment.content.querySelectorAll('[data-chat-message]')) {
            const id = incoming.dataset.chatMessage;
            const existing = messages.querySelector(`[data-chat-message="${id}"]`);
            if (existing) {
                const audio = existing.querySelector('audio');
                const editing = existing.contains(doc.activeElement) || existing.querySelector('details[open]');
                const changed = existing.dataset.chatRevision !== incoming.dataset.chatRevision;
                if (changed && (incoming.classList.contains('is-recalled') || (!editing && (!audio || (audio.paused && !audio.dataset.mediaLoading))))) {
                    const player = existing.querySelector('.chat-voice-player');
                    const replacementPlayer = incoming.querySelector('.chat-voice-player');
                    if (player && replacementPlayer && !incoming.classList.contains('is-recalled')) replacementPlayer.replaceWith(player);
                    existing.replaceWith(incoming);
                }
            } else {
                const next = messageElements().find(item => Number(item.dataset.chatMessage) > Number(id));
                messages.insertBefore(incoming, next || root.querySelector('[data-chat-empty]'));
            }
        }
        const empty = root.querySelector('[data-chat-empty]');
        if (empty) empty.hidden = messageElements().length > 0;
        if (pinned) scrollBottom();
        else messages.scrollTop = previousTop;
    };
    let lastRead = 0, reading = false;
    const markRead = async () => {
        const id = latestId();
        if (!messages || root.dataset.chatMutual !== '1' || doc.hidden || !doc.hasFocus() || !nearBottom() || id <= lastRead || reading || expired) return;
        reading = true;
        try { const data = await request(root.dataset.readUrl, { method: 'POST', body: JSON.stringify({ message_id: id }) }); updateBadge(data.unread_count, data.request_count || 0); lastRead = id; }
        catch (error) { showError(error); }
        finally { reading = false; }
    };
    let polling = false;
    const poll = async () => {
        if (doc.hidden || polling || expired || lifetime.signal.aborted) return;
        polling = true;
        try {
            if (root) {
                const inboxUrl = new URL(root.dataset.inboxUrl, win.location.href);
                inboxUrl.searchParams.set('folder', root.dataset.chatFolder || 'inbox');
                inboxUrl.searchParams.set('page', new URL(win.location.href).searchParams.get('page') || '1');
                const inbox = await request(inboxUrl);
                root.querySelector('[data-chat-inbox]').innerHTML = inbox.html;
                root.querySelectorAll('.chat-conversation').forEach(link => { const active = new URL(link.href).pathname === win.location.pathname; link.classList.toggle('is-active', active); if (active) link.setAttribute('aria-current', 'page'); });
                updateBadge(inbox.unread_count, inbox.request_count || 0);
                root.querySelectorAll('[data-chat-request-count]').forEach(count => { count.textContent = inbox.request_count || ''; });
                if (messages) {
                    const url = new URL(root.dataset.messagesUrl, win.location.href);
                    url.searchParams.set('after', latestId());
                    messageElements().slice(-80).forEach(item => url.searchParams.append('refresh[]', item.dataset.chatMessage));
                    const data = await request(url);
                    if (String(Number(data.can_send)) !== root.dataset.chatCanSend || String(Number(data.mutual)) !== root.dataset.chatMutual) { win.location.reload(); return; }
                    merge(data.html);
                    root.querySelector('[data-chat-typing]').hidden = !data.typing;
                    messageElements().filter(item => item.dataset.own === '1').forEach(item => {
                        const delivery = item.querySelector('[data-chat-delivery]');
                        if (delivery) delivery.textContent = Number(item.dataset.chatMessage) <= data.read_id ? '· Đã xem' : '· Đã gửi';
                    });
                    if (status) status.textContent = '';
                    await markRead();
                    if (data.has_newer) win.setTimeout(poll, 0);
                }
            } else await refreshBadge();
        } catch (error) {
            if (messages && [403, 404].includes(error.status)) {
                expired = true; messages.querySelectorAll('audio').forEach(audio => audio.pause()); messages.replaceChildren();
                root.querySelector('[data-chat-search-results]')?.replaceChildren();
                form.querySelectorAll('button,textarea').forEach(control => { control.disabled = true; });
                if (status) status.textContent = 'Hội thoại không còn khả dụng. Quay lại danh sách Chat.';
                return;
            }
            if (status && error.name !== 'AbortError') status.textContent = expired ? error.message : 'Đang kết nối lại…';
        } finally { polling = false; }
    };
    const schedule = () => { win.clearTimeout(timer); timer = win.setTimeout(async () => { await poll(); if (!expired) schedule(); }, root ? 4000 : 20000); };
    win.addEventListener('focus', () => { poll(); markRead(); }, { signal: lifetime.signal });
    doc.addEventListener('visibilitychange', () => { if (!doc.hidden) { poll(); markRead(); } }, { signal: lifetime.signal });
    win.addEventListener('pagehide', () => { lifetime.abort(); win.clearTimeout(timer); if (form) { cleanupRecording(true); releaseFile(); } }, { once: true });
    schedule();
    if (!root) { refreshBadge().catch(() => {}); return; }

    const peopleDialog = root.querySelector('[data-chat-people-dialog]');
    const peopleInput = root.querySelector('[data-chat-people-input]');
    let peopleTimer, peopleRequest;
    const searchPeople = async () => {
        peopleRequest?.abort(); peopleRequest = new AbortController();
        const results = root.querySelector('[data-chat-people-results]');
        const error = root.querySelector('[data-chat-people-error]');
        error.hidden = true;
        try {
            const url = new URL(root.dataset.peopleUrl, win.location.href); url.searchParams.set('q', peopleInput.value.trim());
            const data = await request(url, { signal: peopleRequest.signal }); results.innerHTML = data.html;
        } catch (problem) { if (problem.name !== 'AbortError') { error.textContent = problem.message; error.hidden = false; } }
    };
    root.querySelectorAll('[data-chat-new]').forEach(button => button.addEventListener('click', () => { peopleDialog.showModal(); peopleInput.focus(); searchPeople(); }));
    root.querySelector('[data-chat-people-close]').addEventListener('click', () => peopleDialog.close());
    peopleInput.addEventListener('input', () => { win.clearTimeout(peopleTimer); peopleTimer = win.setTimeout(searchPeople, 250); });
    root.addEventListener('submit', async event => {
        const start = event.target.closest('[data-chat-start]');
        if (!start) return;
        event.preventDefault(); const button = start.querySelector('button'); button.disabled = true;
        try { win.location.assign((await request(start.action, { method: 'POST', body: new FormData(start) })).url); }
        catch (error) { const box = root.querySelector('[data-chat-people-error]'); box.textContent = error.message; box.hidden = false; button.disabled = false; }
    });
    if (!form) { poll(); return; }

    const draftKey = `threads-chat-draft:${form.action}`;
    try { const draft = JSON.parse(win.sessionStorage.getItem(draftKey) || 'null'); if (draft) { text.value = draft.body || ''; form.elements.client_token.value = draft.token || form.elements.client_token.value; } } catch {}
    const saveDraft = () => { try { win.sessionStorage.setItem(draftKey, JSON.stringify({ body: text.value, token: form.elements.client_token.value })); } catch {} };
    const autoSize = () => { text.style.height = 'auto'; text.style.height = Math.min(130, text.scrollHeight + 2) + 'px'; text.style.overflowY = text.scrollHeight > 128 ? 'auto' : 'hidden'; };
    const context = root.querySelector('[data-chat-context]');
    let editUrl = '', busy = false, attachment = null, previewUrl = '', voiceDuration = 0;
    const resetContext = () => { editUrl = ''; form.elements.reply_to_id.value = ''; context.hidden = true; };
    function releaseFile() {
        if (previewUrl) win.URL.revokeObjectURL(previewUrl);
        previewUrl = ''; attachment = null; voiceDuration = 0;
        if (!form) return;
        form.elements.kind.value = 'text'; root.querySelector('[data-chat-file]').value = '';
        root.querySelector('[data-chat-attachment]').hidden = true;
        const audio = root.querySelector('[data-chat-voice-preview]'); audio.pause(); audio.removeAttribute('src');
        root.querySelector('[data-chat-voice-preview-player]').hidden = true;
    }
    const attachFile = (file, kind, duration = 0) => {
        releaseFile(); if (editUrl) resetContext(); attachment = file; voiceDuration = duration;
        form.elements.kind.value = kind; previewUrl = win.URL.createObjectURL(file);
        const image = root.querySelector('[data-chat-image-preview]'); const audio = root.querySelector('[data-chat-voice-preview]');
        image.hidden = kind !== 'image'; audio.hidden = kind !== 'voice';
        root.querySelector('[data-chat-voice-preview-player]').hidden = kind !== 'voice';
        audio.dataset.duration = String(duration / 1000);
        (kind === 'image' ? image : audio).src = previewUrl;
        audio.dispatchEvent(new Event('durationchange'));
        root.querySelector('[data-chat-file-name]').textContent = kind === 'voice' ? `Ghi âm ${Math.ceil(duration / 1000)} giây` : file.name;
        root.querySelector('[data-chat-attachment]').hidden = false;
    };
    root.querySelector('[data-chat-attach]').addEventListener('click', () => root.querySelector('[data-chat-file]').click());
    root.querySelector('[data-chat-file]').addEventListener('change', event => {
        const file = event.target.files[0]; if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type) || file.size > 5 * 1024 * 1024) { showError(new Error('Chọn ảnh JPEG, PNG, WebP hoặc GIF, tối đa 5 MB.')); event.target.value = ''; return; }
        attachFile(file, 'image');
    });
    root.querySelector('[data-chat-remove-file]').addEventListener('click', releaseFile);
    root.querySelector('[data-chat-context-cancel]').addEventListener('click', () => { if (editUrl) text.value = ''; resetContext(); saveDraft(); });
    let typingAt = 0;
    text.addEventListener('input', () => {
        autoSize(); saveDraft(); errorBox.hidden = true;
        if (root.dataset.chatMutual === '1' && Date.now() - typingAt > 3000 && text.value.trim()) { typingAt = Date.now(); request(root.dataset.typingUrl, { method: 'POST', body: JSON.stringify({ typing: true }) }).catch(() => {}); }
    });
    text.addEventListener('keydown', event => { if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) { event.preventDefault(); form.requestSubmit(); } });
    const picker = root.querySelector('[data-chat-picker]');
    root.querySelector('[data-chat-picker-toggle]').addEventListener('click', () => { picker.hidden = !picker.hidden; });
    root.querySelector('[data-chat-picker-close]').addEventListener('click', () => { picker.hidden = true; });
    const send = async sticker => {
        if (busy || recorder || recordingPending || expired) return;
        if (!sticker && !text.value.trim() && !attachment) return;
        busy = true; errorBox.hidden = true;
        form.querySelectorAll('button,textarea').forEach(control => { control.disabled = true; });
        try {
            let data;
            if (editUrl) data = await request(editUrl, { method: 'PATCH', body: JSON.stringify({ body: text.value }) });
            else {
                const body = new FormData(); body.append('client_token', form.elements.client_token.value); body.append('kind', sticker ? 'sticker' : form.elements.kind.value);
                body.append('body', sticker ? '' : text.value);
                if (form.elements.reply_to_id.value) body.append('reply_to_id', form.elements.reply_to_id.value);
                if (sticker) body.append('sticker', sticker);
                else if (attachment) { body.append(form.elements.kind.value === 'image' ? 'image' : 'voice', attachment); if (voiceDuration) body.append('duration_ms', voiceDuration); }
                data = await request(form.action, { method: 'POST', body });
            }
            merge(data.html, true); text.value = ''; resetContext(); releaseFile(); picker.hidden = true;
            form.elements.client_token.value = win.crypto.randomUUID(); saveDraft(); autoSize(); await poll();
        } catch (error) { showError(error); }
        finally { busy = false; form.querySelectorAll('button,textarea').forEach(control => { control.disabled = false; }); text.focus(); }
    };
    form.addEventListener('submit', event => { event.preventDefault(); send(); });
    root.addEventListener('click', async event => {
        const tab = event.target.closest('[data-chat-picker-tab]');
        if (tab) { root.querySelector('[data-chat-emoji-panel]').hidden = tab.dataset.chatPickerTab !== 'emoji'; root.querySelector('[data-chat-sticker-panel]').hidden = tab.dataset.chatPickerTab !== 'sticker'; root.querySelectorAll('[data-chat-picker-tab]').forEach(button => button.setAttribute('aria-pressed', String(button === tab))); }
        const emoji = event.target.closest('[data-chat-emoji]');
        if (emoji) { const inserted = insertEmoji(text.value, emoji.dataset.chatEmoji, text.selectionStart, text.selectionEnd); text.value = inserted.value; text.focus(); text.setSelectionRange(inserted.caret, inserted.caret); autoSize(); saveDraft(); }
        const sticker = event.target.closest('[data-chat-sticker]'); if (sticker) { if (editUrl || attachment || text.value.trim()) { showError(new Error('Gửi hoặc hủy nội dung đang soạn trước khi gửi sticker.')); return; } send(sticker.dataset.chatSticker); }
        const item = event.target.closest('[data-chat-message]'); if (!item) return;
        if (event.target.closest('[data-chat-edit], [data-chat-recall]')) item.querySelector('[data-chat-actions]')?.removeAttribute('open');
        if (event.target.closest('[data-chat-reply], [data-chat-edit]')) {
            if (busy) { showError(new Error('Đang gửi tin nhắn. Chờ một chút rồi thử lại.')); return; }
            const editing = !!event.target.closest('[data-chat-edit]'); resetContext();
            if (editing) { releaseFile(); editUrl = item.dataset.messageUrl; text.value = item.querySelector('[data-chat-body]')?.textContent || ''; }
            else form.elements.reply_to_id.value = item.dataset.chatMessage;
            root.querySelector('[data-chat-context-title]').textContent = editing ? 'Chỉnh sửa tin nhắn' : `Trả lời ${item.dataset.author}`;
            root.querySelector('[data-chat-context-text]').textContent = item.querySelector('[data-chat-body]')?.textContent || ({ image: 'Ảnh', voice: 'Tin nhắn thoại', sticker: 'Sticker' }[item.dataset.kind] || 'Tin nhắn');
            context.hidden = false; text.focus(); autoSize();
        }
        const reaction = event.target.closest('[data-chat-react]');
        if (reaction) { try { merge((await request(item.dataset.reactionUrl, { method: 'PUT', body: JSON.stringify({ reaction: reaction.dataset.chatReact || null }) })).html); item.querySelector('details')?.removeAttribute('open'); await poll(); } catch (error) { showError(error); } }
        if (event.target.closest('[data-chat-recall]') && win.confirm('Thu hồi tin nhắn này với cả hai người?')) { try { merge((await request(item.dataset.messageUrl, { method: 'DELETE' })).html); await poll(); } catch (error) { showError(error); } }
        const jump = event.target.closest('[data-chat-jump]'); if (jump) { const target = messages.querySelector(`[data-chat-message="${jump.dataset.chatJump}"]`); if (target) { target.scrollIntoView({ block: 'center' }); target.classList.remove('is-highlighted'); win.requestAnimationFrame(() => target.classList.add('is-highlighted')); } else status.textContent = 'Tin gốc nằm trong lịch sử trước đó. Chọn “Xem tin nhắn trước”.'; }
        const image = event.target.closest('[data-chat-image]'); if (image && win.openThreadGallery) { event.preventDefault(); win.openThreadGallery([{ href: image.href, alt: 'Ảnh trong hội thoại' }], 0, image); }
    });
    root.querySelector('[data-chat-older]').addEventListener('click', async event => {
        const button = event.currentTarget; if (button.disabled) return; button.disabled = true;
        try {
            const url = new URL(root.dataset.messagesUrl, win.location.href); url.searchParams.set('before', messageElements()[0]?.dataset.chatMessage || 1);
            const beforeHeight = messages.scrollHeight; const beforeTop = messages.scrollTop;
            const data = await request(url); merge(data.html); messages.scrollTop = beforeTop + messages.scrollHeight - beforeHeight; button.hidden = !data.has_older;
        } catch (error) { showError(error); } finally { button.disabled = false; }
    });
    const searchForm = root.querySelector('[data-chat-search]');
    root.querySelector('[data-chat-search-toggle]').addEventListener('click', () => { searchForm.hidden = !searchForm.hidden; if (!searchForm.hidden) searchForm.querySelector('input').focus(); });
    root.querySelector('[data-chat-search-close]').addEventListener('click', () => { searchForm.hidden = true; });
    searchForm.addEventListener('submit', async event => { event.preventDefault(); const q = searchForm.querySelector('input').value.trim(); if (!q) return; try { const url = new URL(root.dataset.messagesUrl, win.location.href); url.searchParams.set('q', q); const data = await request(url); root.querySelector('[data-chat-search-results]').innerHTML = data.html || '<p class="chat-empty-search">Không tìm thấy tin nhắn.</p>'; } catch (error) { showError(error); } });

    let recorder = null, recordingPending = false, recordingCancelled = false, stream = null, recordTimer, started = 0;
    function cleanupRecording(cancel = false) {
        if (cancel) recordingCancelled = true;
        if (recorder?.state === 'recording') recorder.stop();
        stream?.getTracks().forEach(track => track.stop()); stream = null;
        win.clearInterval(recordTimer);
        root?.querySelector('[data-chat-recording]')?.setAttribute('hidden', '');
    }
    root.querySelector('[data-chat-record]').addEventListener('click', async () => {
        if (recorder || recordingPending || busy) return;
        if (!win.MediaRecorder || !win.navigator.mediaDevices?.getUserMedia) { showError(new Error('Trình duyệt chưa hỗ trợ ghi âm. Hãy dùng Chrome/Edge qua HTTPS hoặc localhost.')); return; }
        if (attachment || editUrl) { showError(new Error('Gửi hoặc hủy tệp/chỉnh sửa hiện tại trước khi ghi âm.')); return; }
        recordingPending = true; recordingCancelled = false;
        try {
            stream = await win.navigator.mediaDevices.getUserMedia({ audio: true });
            if (lifetime.signal.aborted || recordingCancelled) { stream.getTracks().forEach(track => track.stop()); return; }
            const mimeType = recordingType(win.MediaRecorder);
            recorder = new win.MediaRecorder(stream, mimeType ? { mimeType } : {});
            const chunks = []; let bytes = 0;
            recorder.ondataavailable = event => { if (event.data.size) { chunks.push(event.data); bytes += event.data.size; if (bytes > 5 * 1024 * 1024) { showError(new Error('Ghi âm vượt quá 5 MB. Thử ghi ngắn hơn.')); cleanupRecording(true); } } };
            recorder.onerror = () => { showError(new Error('Không ghi âm được. Kiểm tra microphone rồi thử lại.')); cleanupRecording(true); recorder = null; };
            recorder.onstop = () => {
                const duration = Math.min(120000, Date.now() - started); const type = recorder?.mimeType || mimeType || 'audio/webm'; recorder = null;
                cleanupRecording();
                if (!recordingCancelled && !lifetime.signal.aborted && bytes && duration > 100) {
                    const extension = type.includes('mp4') ? 'm4a' : type.includes('ogg') ? 'ogg' : 'webm';
                    attachFile(new win.File(chunks, `ghi-am.${extension}`, { type: type.split(';')[0] }), 'voice', duration);
                }
            };
            recorder.start(250); started = Date.now(); root.querySelector('[data-chat-recording]').hidden = false;
            root.querySelector('[data-chat-recording-time]').textContent = '00:00';
            recordTimer = win.setInterval(() => { const seconds = Math.floor((Date.now() - started) / 1000); root.querySelector('[data-chat-recording-time]').textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`; if (seconds >= 120) cleanupRecording(); }, 250);
        } catch (error) { showError(new Error(error.name === 'NotAllowedError' ? 'Cho phép microphone trong trình duyệt để ghi âm.' : 'Không mở được microphone. Vui lòng thử lại.')); cleanupRecording(true); recorder = null; }
        finally { recordingPending = false; }
    });
    root.querySelector('[data-chat-record-stop]').addEventListener('click', () => cleanupRecording());
    root.querySelector('[data-chat-record-cancel]').addEventListener('click', () => cleanupRecording(true));
    doc.addEventListener('visibilitychange', () => { if (doc.hidden && recorder) cleanupRecording(); }, { signal: lifetime.signal });
    messages.addEventListener('play', event => { if (event.target.tagName === 'AUDIO') doc.querySelectorAll('audio,video').forEach(media => { if (media !== event.target) media.pause(); }); }, true);
    messages.addEventListener('scroll', () => { if (nearBottom()) markRead(); }, { passive: true });
    messages.addEventListener('load', event => { if (event.target.tagName === 'IMG' && nearBottom()) scrollBottom(); }, true);
    scrollBottom(); autoSize(); poll(); markRead();
}
