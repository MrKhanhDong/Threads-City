export function musicStreamUrl(id) {
    return /^[a-zA-Z0-9]{1,64}$/.test(id || '') ? `https://api.audius.co/v1/tracks/${id}/stream?app_name=ThreadsCity` : null;
}

export async function searchMusic(endpoint, query, offset, signal, pageWindow = window) {
    const url = new URL(endpoint, pageWindow.location.href);
    url.searchParams.set('q', query); url.searchParams.set('offset', String(offset));
    const response = await pageWindow.fetch(url, { signal, credentials: 'same-origin', headers: { Accept: 'application/json' } });
    const data = await response.json();
    if (!response.ok) throw new Error(response.status === 401 ? 'Đăng nhập lại để chọn nhạc.' : response.status === 429 ? 'Bạn tìm nhạc quá nhanh. Hãy đợi một chút rồi thử lại.' : data.message || 'Không tìm được nhạc. Vui lòng thử lại.');
    if (!Array.isArray(data.items)) throw new Error('Kho nhạc trả về dữ liệu không hợp lệ.');
    return data;
}

export function setupMusicPicker(doc = document, pageWindow = window) {
    doc.addEventListener('play', event => {
        if (event.target.matches?.('[data-music-audio], [data-music-audition]')) {
            doc.querySelectorAll('audio, video').forEach(media => { if (media !== event.target) media.pause(); });
        }
    }, true);
    doc.addEventListener('error', event => {
        if (event.target.matches?.('[data-music-audio]')) {
            const message = event.target.closest('[data-music-card]')?.querySelector('[data-music-error]');
            if (message) message.hidden = false;
        }
    }, true);
    const dialog = doc.querySelector('[data-music-picker]');
    const trigger = doc.getElementById('modalAddMusicBtn');
    const modal = doc.getElementById('newThreadModal');
    const selected = doc.querySelector('[data-selected-music]');
    const musicId = doc.getElementById('modalMusicId');
    if (!dialog || !trigger || !selected || !musicId) return;
    const form = dialog.querySelector('[data-music-search]'), input = form.querySelector('input');
    const results = dialog.querySelector('[data-music-results]'), status = dialog.querySelector('[data-music-status]');
    const more = dialog.querySelector('[data-music-more]'), audition = dialog.querySelector('[data-music-audition]');
    let controller, generation = 0, debounce, offset = 0, busy = false, listening;
    const close = () => {
        pageWindow.clearTimeout(debounce); controller?.abort(); generation++;
        audition.pause(); audition.removeAttribute('src'); audition.load(); audition.hidden = true;
        listening = null; dialog.close(); trigger.focus();
    };
    const clear = () => {
        selected.querySelectorAll('audio').forEach(audio => audio.pause());
        selected.replaceChildren(); selected.hidden = true; musicId.value = '';
        pageWindow.updateComposerSubmitState?.();
    };
    const image = track => {
        const cover = doc.createElement('img'); cover.src = track.artwork; cover.alt = 'Bìa bài hát'; cover.loading = 'lazy';
        return cover;
    };
    const info = track => {
        const block = doc.createElement('div'); block.className = 'music-track-info';
        const title = doc.createElement('strong'); title.textContent = track.title;
        const artist = doc.createElement('span'); artist.textContent = `${track.artist} · Audius`;
        block.append(title, artist); return block;
    };
    const choose = track => {
        const source = musicStreamUrl(track.id);
        if (!source) return;
        clear(); musicId.value = track.id; selected.hidden = false;
        const card = doc.createElement('section'); card.className = 'music-card';
        card.dataset.musicCard = ''; card.setAttribute('aria-label', 'Nhạc đã chọn');
        const heading = doc.createElement('div'); heading.className = 'music-card-heading';
        if (track.artwork) heading.append(image(track));
        const remove = doc.createElement('button'); remove.type = 'button'; remove.className = 'music-remove';
        remove.textContent = '✕'; remove.setAttribute('aria-label', 'Gỡ bài hát'); remove.addEventListener('click', clear);
        heading.append(info(track), remove);
        const audio = doc.createElement('audio'); audio.controls = true; audio.preload = 'none'; audio.src = source; audio.dataset.musicAudio = '';
        audio.setAttribute('aria-label', `Nghe ${track.title}`);
        card.append(heading, audio); selected.append(card);
        pageWindow.updateComposerSubmitState?.(); close();
    };
    const load = async (append = false) => {
        controller?.abort(); controller = new AbortController();
        const request = controller, version = ++generation;
        const timeout = pageWindow.setTimeout(() => request.abort(), 12000);
        if (!append) { offset = 0; results.replaceChildren(); audition.pause(); }
        busy = true; more.hidden = true; results.setAttribute('aria-busy', 'true'); status.textContent = 'Đang tìm nhạc…';
        try {
            const data = await searchMusic(dialog.dataset.searchUrl, input.value.trim(), offset, request.signal, pageWindow);
            if (version !== generation || !dialog.open) return;
            for (const track of data.items) {
                if (!musicStreamUrl(track.id)) continue;
                const row = doc.createElement('div'); row.className = 'music-result'; row.setAttribute('role', 'listitem');
                if (track.artwork) row.append(image(track));
                row.append(info(track));
                const actions = doc.createElement('div'); actions.className = 'music-result-actions';
                const listen = doc.createElement('button'); listen.type = 'button'; listen.textContent = 'Nghe'; listen.setAttribute('aria-label', `Nghe thử ${track.title}`);
                listen.addEventListener('click', async () => {
                    if (listening === track.id && !audition.paused) { audition.pause(); return; }
                    audition.src = musicStreamUrl(track.id); audition.hidden = false; listening = track.id;
                    try { await audition.play(); status.textContent = `Đang nghe: ${track.title}`; }
                    catch { status.textContent = 'Chưa phát được bài hát. Hãy thử lại hoặc chọn bài khác.'; }
                });
                const pick = doc.createElement('button'); pick.type = 'button'; pick.textContent = 'Chọn'; pick.setAttribute('aria-label', `Chọn ${track.title}`); pick.addEventListener('click', () => choose(track));
                actions.append(listen, pick); row.append(actions); results.append(row);
            }
            offset = data.next_offset; more.hidden = !data.has_more || offset > 500;
            status.textContent = results.children.length ? (input.value.trim() ? 'Nghe thử rồi chọn bài hát bạn thích' : 'Nhạc đang thịnh hành trên Audius') : 'Không tìm thấy bài hát. Thử tên bài hoặc nghệ sĩ khác nhé.';
        } catch (error) {
            if (version === generation && dialog.open) status.textContent = error.name === 'AbortError' ? 'Tìm nhạc quá lâu. Bấm Tìm để thử lại.' : error.message;
        } finally { pageWindow.clearTimeout(timeout); if (version === generation) { busy = false; results.setAttribute('aria-busy', 'false'); } }
    };
    trigger.addEventListener('click', () => { if (!dialog.open) { dialog.showModal(); input.focus(); load(); } });
    dialog.querySelector('[data-music-close]').addEventListener('click', close);
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    dialog.addEventListener('keydown', event => { if (event.key === 'Escape') event.stopPropagation(); });
    dialog.addEventListener('click', event => { if (event.target === dialog) close(); });
    form.addEventListener('submit', event => { event.preventDefault(); pageWindow.clearTimeout(debounce); load(); });
    input.addEventListener('input', () => { pageWindow.clearTimeout(debounce); controller?.abort(); generation++; debounce = pageWindow.setTimeout(() => load(), 500); });
    more.addEventListener('click', () => { if (!busy) load(true); });
    modal.addEventListener('hide.bs.modal', close);
    modal.addEventListener('composer:reset', clear);
}
