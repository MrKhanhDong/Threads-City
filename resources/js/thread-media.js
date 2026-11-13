export function mediaTime(seconds) {
    const value = Number.isFinite(seconds) ? Math.max(0, Math.floor(seconds)) : 0;
    return `${Math.floor(value / 60)}:${String(value % 60).padStart(2, '0')}`;
}

export function playbackDuration(media) {
    const saved = Number(media.dataset?.duration);
    if (Number.isFinite(saved) && saved > 0) return saved;
    return Number.isFinite(media.duration) && media.duration > 0 ? media.duration : 0;
}

export function galleryIndex(index, change, count) {
    return count ? (index + change + count) % count : 0;
}

export function mediaIcon(name) {
    const paths = {
        play: '<path d="m9 5 11 7-11 7Z" fill="currentColor" stroke="none"/>',
        pause: '<path d="M8 5v14M16 5v14" stroke-width="3"/>',
        muted: '<path d="M11 5 6 9H3v6h3l5 4Zm5 4 5 6m0-6-5 6"/>',
        volume: '<path d="M11 5 6 9H3v6h3l5 4Zm5 3a6 6 0 0 1 0 8m3-11a10 10 0 0 1 0 14"/>',
        fullscreen: '<path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5"/>',
        shrink: '<path d="M3 8h5V3m8 0v5h5M3 16h5v5m8 0v-5h5"/>',
    };
    return `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths[name] || paths.play}</svg>`;
}

export async function loadVoiceBlob(source, pageWindow, signal) {
    const response = await pageWindow.fetch(source, { credentials: 'same-origin', cache: 'default', signal });
    if (!response.ok) throw new Error('Không tải được ghi âm. Hãy thử lại.');
    const blob = await response.blob();
    if (!blob.size || blob.size > 5 * 1024 * 1024 || (blob.type && !/^(audio|video)\//.test(blob.type) && blob.type !== 'application/octet-stream')) throw new Error('Tệp ghi âm không hợp lệ.');
    return blob;
}

export function setupMediaDrag(doc = document, pageWindow = window) {
    let gesture, motion, frame, suppressClick, suppressUntil = 0;
    const now = () => pageWindow.performance.now();
    const stopMotion = () => {
        if (frame !== undefined) pageWindow.cancelAnimationFrame(frame);
        frame = undefined;
        if (motion) { motion.strip.style.scrollBehavior = motion.behavior; delete motion.strip.dataset.mediaInteracting; }
        motion = null;
    };
    const coast = (strip, velocity, behavior) => {
        let previous = now(), position = strip.scrollLeft;
        motion = { strip, behavior };
        const tick = timestamp => {
            if (!strip.isConnected || doc.hidden) { stopMotion(); return; }
            const elapsed = Math.min(64, Math.max(0, timestamp - previous));
            previous = timestamp;
            const decay = Math.exp(-elapsed / 240);
            const maximum = strip.scrollWidth - strip.clientWidth;
            position = Math.max(0, Math.min(maximum, position + velocity * 240 * (1 - decay)));
            strip.scrollLeft = position;
            velocity *= decay;
            if (Math.abs(velocity) < 0.02 || strip.scrollLeft <= 0 || strip.scrollLeft >= maximum) { stopMotion(); return; }
            frame = pageWindow.requestAnimationFrame(tick);
        };
        frame = pageWindow.requestAnimationFrame(tick);
    };
    const finish = event => {
        if (!gesture || (event?.pointerId !== undefined && event.pointerId !== gesture.pointerId)) return;
        const { strip, pointerId, dragging, behavior, samples } = gesture;
        gesture = null;
        if (!dragging) return;
        strip.classList.remove('is-dragging');
        if (strip.hasPointerCapture?.(pointerId)) strip.releasePointerCapture(pointerId);
        suppressClick = strip; suppressUntil = now() + 350;
        const released = now();
        const recent = samples.filter(sample => released - sample.time <= 100);
        const first = recent[0], last = recent.at(-1);
        const velocity = first && last && released - last.time < 80 ? (strip.scrollLeft - first.scroll) / Math.max(1, released - first.time) : 0;
        if (event?.type === 'pointerup' && Math.abs(velocity) > 0.05 && !pageWindow.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            coast(strip, Math.max(-2.2, Math.min(2.2, velocity)), behavior);
        } else { strip.style.scrollBehavior = behavior; delete strip.dataset.mediaInteracting; }
    };
    doc.addEventListener('pointerdown', event => {
        stopMotion();
        suppressClick = null;
        if (gesture || event.button !== 0 || event.pointerType === 'touch' || event.ctrlKey || event.metaKey || event.shiftKey) return;
        const strip = event.target.closest('[data-media-gallery]');
        if (!strip || strip.scrollWidth <= strip.clientWidth + 1 || event.target.closest('button, input, audio, video[controls], .media-controls')) return;
        gesture = { strip, pointerId: event.pointerId, x: event.clientX, y: event.clientY, scroll: strip.scrollLeft, dragging: false, behavior: strip.style.scrollBehavior, samples: [{ scroll: strip.scrollLeft, time: now() }] };
    });
    doc.addEventListener('pointermove', event => {
        if (!gesture || event.pointerId !== gesture.pointerId) return;
        const dx = event.clientX - gesture.x, dy = event.clientY - gesture.y;
        if (!gesture.dragging) {
            if (Math.abs(dx) < 6) return;
            if (Math.abs(dy) > Math.abs(dx)) { gesture = null; return; }
            gesture.dragging = true;
            gesture.strip.dataset.mediaInteracting = '1';
            gesture.strip.style.scrollBehavior = 'auto';
            gesture.strip.classList.add('is-dragging'); gesture.strip.setPointerCapture?.(event.pointerId);
        }
        event.preventDefault();
        gesture.strip.scrollLeft = gesture.scroll - dx;
        const time = now();
        gesture.samples = [...gesture.samples.filter(sample => time - sample.time <= 100), { scroll: gesture.strip.scrollLeft, time }];
    });
    doc.addEventListener('pointerup', finish);
    doc.addEventListener('pointercancel', finish);
    doc.addEventListener('lostpointercapture', finish);
    const cancel = () => { finish(); stopMotion(); };
    pageWindow.addEventListener('blur', cancel);
    pageWindow.addEventListener('pagehide', cancel);
    doc.addEventListener('visibilitychange', () => { if (doc.hidden) cancel(); });
    doc.addEventListener('wheel', stopMotion, { passive: true });
    doc.addEventListener('keydown', stopMotion);
    doc.addEventListener('dragstart', event => { if (event.target.closest('[data-media-gallery]')) event.preventDefault(); });
    doc.addEventListener('click', event => {
        if (event.detail !== 0 && now() <= suppressUntil && suppressClick?.contains(event.target)) {
            suppressClick = null; event.preventDefault(); event.stopImmediatePropagation();
        }
    }, true);
}

export function inspectClip(file, doc = document, pageWindow = window) {
    return new Promise((resolve, reject) => {
        const video = doc.createElement('video');
        const url = pageWindow.URL.createObjectURL(file);
        const finish = (error) => {
            clearTimeout(timer);
            const duration = video.duration;
            video.removeAttribute('src'); video.load(); pageWindow.URL.revokeObjectURL(url);
            error ? reject(new Error(error)) : resolve(duration);
        };
        const timer = setTimeout(() => finish('Không đọc được video. Hãy thử tệp khác.'), 15000);
        video.onloadedmetadata = () => finish(!Number.isFinite(video.duration) || video.duration <= 0 || video.duration > 60 ? 'Video ngắn tối đa 60 giây.' : null);
        video.onerror = () => finish('Video không được hỗ trợ. Chọn MP4 hoặc WebM.');
        video.preload = 'metadata'; video.src = url;
    });
}

export function setupThreadMedia(doc = document, pageWindow = window) {
    setupMediaDrag(doc, pageWindow);
    pageWindow.inspectThreadClip = file => inspectClip(file, doc, pageWindow);
    const players = new Set();
    const ratios = new Map();
    const voiceSources = new Map();
    let playbackVersion = 0;
    const pauseOthers = current => {
        players.forEach(media => { if (media !== current) media.pause(); });
        doc.querySelectorAll('[data-music-audio], [data-music-audition]').forEach(media => { if (media !== current) media.pause(); });
    };
    const canAutoplay = () => !doc.hidden && !doc.fullscreenElement && !doc.querySelector('dialog[open], .modal.show') && ![...doc.querySelectorAll('[data-music-audio]')].some(media => !media.paused && !media.ended) && !pageWindow.matchMedia('(prefers-reduced-motion: reduce)').matches && !pageWindow.navigator.connection?.saveData;
    const observer = pageWindow.IntersectionObserver ? new pageWindow.IntersectionObserver(entries => {
        entries.forEach(entry => ratios.set(entry.target, entry.intersectionRatio));
        const best = [...ratios].filter(([video, ratio]) => video.isConnected && ratio >= 0.6 && !video.dataset.userPaused).sort((a, b) => b[1] - a[1])[0]?.[0];
        players.forEach(media => {
            if (!media.matches('[data-clip]')) return;
            if (doc.fullscreenElement?.contains(media)) return;
            if (media === best && canAutoplay() && ![...players].some(other => other.matches('[data-voice]') && (!other.paused || other.dataset.mediaLoading))) {
                if (media.paused) { media.muted = true; void media.play().catch(() => {}); }
            } else if ((ratios.get(media) || 0) < 0.6) media.pause();
        });
    }, { threshold: [0, 0.25, 0.6, 0.9, 1] }) : null;

    function initialize() {
        players.forEach(media => {
            if (!media.isConnected) {
                media.pause(); observer?.unobserve(media); ratios.delete(media); players.delete(media);
                const source = voiceSources.get(media); source?.controller?.abort();
                if (source?.url) pageWindow.URL.revokeObjectURL(source.url);
                voiceSources.delete(media);
            }
        });
        doc.querySelectorAll('[data-clip], [data-voice]').forEach(media => {
            if (players.has(media)) return;
            players.add(media);
            const player = media.closest('[data-player]');
            if (!player) return;
            const voice = media.matches('[data-voice]');
            const controls = doc.createElement('div'); controls.className = 'media-controls';
            controls.innerHTML = `<button type="button" data-media-toggle aria-label="Phát" title="Phát">${mediaIcon('play')}</button><input type="range" min="0" max="100" value="0" step="0.1" aria-label="Tua ${voice ? 'ghi âm' : 'video'}"><span class="media-time">0:00</span>${voice ? '' : `<button type="button" data-media-mute aria-label="Bật âm thanh" title="Bật âm thanh" aria-pressed="false">${mediaIcon('muted')}</button><button type="button" data-media-fullscreen aria-label="Toàn màn hình" title="Toàn màn hình">${mediaIcon('fullscreen')}</button>`}`;
            player.append(controls); media.controls = false; player.classList.add('is-enhanced');
            const toggle = controls.querySelector('[data-media-toggle]');
            const range = controls.querySelector('input');
            const time = controls.querySelector('.media-time');
            const update = () => {
                const duration = playbackDuration(media);
                const current = media.ended && duration ? duration : media.currentTime;
                const progress = duration ? Math.min(100, Math.max(0, current / duration * 100)) : 0;
                range.value = String(progress);
                const clock = `${mediaTime(duration ? Math.min(current, duration) : current)} / ${mediaTime(duration)}`;
                if (!media.dataset.mediaLoading && time.textContent !== clock) time.textContent = clock;
                player.querySelectorAll('.voice-waveform > span').forEach((bar, index, bars) => bar.classList.toggle('is-played', index / bars.length * 100 <= progress && progress > 0));
            };
            media.addEventListener('loadedmetadata', update); media.addEventListener('durationchange', update); media.addEventListener('timeupdate', update); media.addEventListener('ended', update);
            media.addEventListener('play', () => {
                pauseOthers(media); media.dataset.mediaPlaying = '1';
                toggle.innerHTML = mediaIcon('pause'); toggle.setAttribute('aria-label', 'Tạm dừng'); toggle.title = 'Tạm dừng';
            });
            const paused = () => { delete media.dataset.mediaPlaying; toggle.innerHTML = mediaIcon('play'); toggle.setAttribute('aria-label', 'Phát'); toggle.title = 'Phát'; };
            media.addEventListener('pause', paused); media.addEventListener('ended', paused);
            media.addEventListener('error', () => { time.textContent = 'Không tải được tệp'; });
            toggle.addEventListener('click', async () => {
                const version = ++playbackVersion;
                if (media.paused) {
                    delete media.dataset.userPaused; pauseOthers(media);
                    try {
                        if (voice && !media.src.startsWith('blob:')) {
                            const resumeAt = media.currentTime;
                            toggle.disabled = true; player.setAttribute('aria-busy', 'true'); media.dataset.mediaLoading = '1';
                            time.textContent = 'Đang tải…';
                            const controller = new pageWindow.AbortController(); voiceSources.set(media, { controller });
                            const timeout = pageWindow.setTimeout(() => controller.abort(), 20000);
                            let blob;
                            try { blob = await loadVoiceBlob(media.src, pageWindow, controller.signal); }
                            finally { pageWindow.clearTimeout(timeout); }
                            if (!media.isConnected) return;
                            const url = pageWindow.URL.createObjectURL(blob);
                            voiceSources.set(media, { url }); media.src = url; media.preload = 'auto';
                            if (resumeAt > 0) media.addEventListener('loadedmetadata', () => { media.currentTime = resumeAt; }, { once: true });
                            media.load();
                        }
                        if (version === playbackVersion && media.isConnected) await media.play();
                    } catch { time.textContent = 'Chạm để thử lại'; }
                    finally { delete media.dataset.mediaLoading; player.removeAttribute('aria-busy'); toggle.disabled = false; }
                }
                else { media.dataset.userPaused = '1'; media.pause(); }
            });
            range.addEventListener('input', () => { const duration = playbackDuration(media); if (duration > 0) media.currentTime = Number(range.value) / 100 * duration; });
            const muteButton = controls.querySelector('[data-media-mute]');
            const updateVolume = () => {
                if (!muteButton) return;
                const muted = media.muted || media.volume === 0;
                muteButton.innerHTML = mediaIcon(muted ? 'muted' : 'volume');
                muteButton.setAttribute('aria-label', muted ? 'Bật âm thanh' : 'Tắt âm thanh');
                muteButton.setAttribute('aria-pressed', String(!muted)); muteButton.title = muted ? 'Bật âm thanh' : 'Tắt âm thanh';
            };
            media.addEventListener('volumechange', updateVolume); updateVolume();
            muteButton?.addEventListener('click', () => {
                media.muted = !media.muted;
            });
            const fullscreenButton = controls.querySelector('[data-media-fullscreen]');
            fullscreenButton?.addEventListener('click', () => {
                if (doc.fullscreenElement === player) void doc.exitFullscreen().catch(() => {});
                else if (player.requestFullscreen) void player.requestFullscreen().catch(() => { time.textContent = 'Không mở được toàn màn hình'; });
                else media.webkitEnterFullscreen?.();
            });
            const updateFullscreen = () => {
                if (!fullscreenButton) return;
                const active = doc.fullscreenElement === player;
                fullscreenButton.innerHTML = mediaIcon(active ? 'shrink' : 'fullscreen');
                fullscreenButton.setAttribute('aria-label', active ? 'Thoát toàn màn hình' : 'Toàn màn hình');
                fullscreenButton.title = active ? 'Thoát toàn màn hình' : 'Toàn màn hình';
            };
            player.addEventListener('fullscreenchange', updateFullscreen);
            controls.addEventListener('click', event => event.stopPropagation());
            update();
            if (!voice) observer?.observe(media);
        });
    }

    const dialog = doc.createElement('dialog'); dialog.className = 'thread-lightbox';
    dialog.setAttribute('aria-label', 'Trình xem ảnh');
    dialog.innerHTML = '<div class="lightbox-toolbar"><span data-lightbox-counter aria-live="polite"></span><button type="button" data-lightbox-close aria-label="Đóng ảnh">✕</button></div><div class="lightbox-stage"><button type="button" data-lightbox-prev aria-label="Ảnh trước">‹</button><img alt=""><button type="button" data-lightbox-next aria-label="Ảnh tiếp theo">›</button></div><p class="lightbox-caption"></p>';
    doc.body.append(dialog);
    let gallery = [], index = 0, trigger;
    const show = change => {
        index = galleryIndex(index, change, gallery.length);
        const link = gallery[index], image = dialog.querySelector('img');
        image.src = link.href; image.alt = link.alt || 'Ảnh đính kèm';
        dialog.querySelector('[data-lightbox-counter]').textContent = `${index + 1} / ${gallery.length}`;
        dialog.querySelector('.lightbox-caption').textContent = image.alt;
        dialog.querySelectorAll('[data-lightbox-prev], [data-lightbox-next]').forEach(button => { button.hidden = gallery.length < 2; });
    };
    pageWindow.openThreadGallery = (items, selected = 0, opener = null) => {
        gallery = items; index = selected; trigger = opener;
        pauseOthers(null);
        doc.querySelectorAll('[data-desktop-column-frame]').forEach(frame => {
            try { frame.contentDocument?.querySelectorAll('video, audio').forEach(media => media.pause()); } catch {}
        });
        show(0); dialog.showModal(); doc.documentElement.classList.add('lightbox-open');
    };
    doc.addEventListener('click', event => {
        const link = event.target.closest('[data-lightbox]'); if (!link || event.ctrlKey || event.metaKey) return;
        event.preventDefault(); event.stopPropagation();
        const links = [...link.closest('[data-media-gallery]').querySelectorAll('[data-lightbox]')];
        const items = links.map(item => ({ href: item.href, alt: item.querySelector('img')?.alt }));
        try {
            if (pageWindow.frameElement?.hasAttribute('data-desktop-column-frame') && pageWindow.parent.openThreadGallery) {
                pageWindow.parent.openThreadGallery(items, links.indexOf(link), link); return;
            }
        } catch {}
        pageWindow.openThreadGallery(items, links.indexOf(link), link);
    }, true);
    dialog.querySelector('[data-lightbox-close]').addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-lightbox-prev]').addEventListener('click', () => show(-1));
    dialog.querySelector('[data-lightbox-next]').addEventListener('click', () => show(1));
    dialog.addEventListener('keydown', event => { if (event.key === 'ArrowLeft') { event.preventDefault(); show(-1); } if (event.key === 'ArrowRight') { event.preventDefault(); show(1); } });
    dialog.addEventListener('click', event => { if (event.target === dialog || event.target.classList.contains('lightbox-stage')) dialog.close(); });
    dialog.addEventListener('close', () => { doc.documentElement.classList.remove('lightbox-open'); dialog.querySelector('img').removeAttribute('src'); trigger?.focus(); });
    let touch;
    dialog.addEventListener('touchstart', event => { touch = event.touches.length === 1 ? { x: event.touches[0].clientX, y: event.touches[0].clientY } : null; }, { passive: true });
    dialog.addEventListener('touchend', event => {
        if (!touch || event.touches.length) return;
        const dx = event.changedTouches[0].clientX - touch.x, dy = event.changedTouches[0].clientY - touch.y;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) show(dx < 0 ? 1 : -1);
        touch = null;
    }, { passive: true });
    initialize();
    let queued = false;
    new pageWindow.MutationObserver(records => {
        const changedMedia = records.some(record => [...record.addedNodes, ...record.removedNodes].some(node => node.nodeType === 1 && (node.matches('[data-player], [data-clip], [data-voice]') || node.querySelector('[data-player], [data-clip], [data-voice]'))));
        if (changedMedia && !queued) { queued = true; pageWindow.requestAnimationFrame(() => { queued = false; initialize(); }); }
    }).observe(doc.querySelector('.journal-workspace') || doc.body, { childList: true, subtree: true });
    doc.addEventListener('visibilitychange', () => { if (doc.hidden) pauseOthers(null); });
    doc.addEventListener('show.bs.modal', () => pauseOthers(null));
    pageWindow.addEventListener('pagehide', () => pauseOthers(null));
}
