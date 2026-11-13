export function waveformPeaks(samples, count = 48) {
    return Array.from({ length: count }, (_, index) => {
        const start = Math.floor(index * samples.length / count), end = Math.max(start + 1, Math.floor((index + 1) * samples.length / count));
        return Math.max(8, Math.min(100, Math.round(Math.max(0, ...samples.slice(start, end)) * 100)));
    });
}

export function recordingMime(Recorder) {
    return ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/webm'].find(type => Recorder.isTypeSupported(type));
}

export function setupVoiceRecorder(doc = document, pageWindow = window) {
    const button = doc.getElementById('modalAddAudioBtn'), modal = doc.getElementById('newThreadModal');
    if (!button || !modal) return;
    const panel = doc.createElement('section'); panel.className = 'voice-recorder'; panel.hidden = true;
    panel.setAttribute('aria-label', 'Ghi âm giọng nói');
    panel.innerHTML = '<div class="voice-recorder-heading"><span data-record-status role="status">Ghi âm tối đa 60 giây</span><strong data-record-time>0:00 / 1:00</strong></div><div class="recording-wave" aria-hidden="true"></div><audio controls hidden></audio><div class="voice-recorder-actions"><button type="button" data-record-stop>Dừng ghi âm</button><button type="button" data-record-attach hidden>Đính kèm</button><button type="button" data-record-again hidden>Ghi lại</button><button type="button" data-record-cancel>Hủy</button></div>';
    doc.getElementById('modalMediaPreview').before(panel);
    const status = panel.querySelector('[data-record-status]'), time = panel.querySelector('[data-record-time]');
    const stopButton = panel.querySelector('[data-record-stop]'), attachButton = panel.querySelector('[data-record-attach]'), againButton = panel.querySelector('[data-record-again]');
    const preview = panel.querySelector('audio'), wave = panel.querySelector('.recording-wave');
    wave.innerHTML = '<span></span>'.repeat(32);
    let generation = 0, recorder, stream, context, frame, timeout, objectUrl, blob, duration, peaks = [], upload;
    const stopTracks = () => {
        stream?.getTracks().forEach(track => track.stop()); stream = null;
        pageWindow.cancelAnimationFrame(frame); pageWindow.clearTimeout(timeout);
        void context?.close().catch(() => {}); context = null;
    };
    const clear = () => {
        generation++; upload?.abort(); upload = null;
        if (recorder?.state === 'recording') recorder.stop();
        stopTracks(); recorder = null; blob = null; peaks = [];
        preview.pause(); preview.removeAttribute('src'); preview.hidden = true;
        if (objectUrl) pageWindow.URL.revokeObjectURL(objectUrl); objectUrl = null;
        delete modal.dataset.recording; button.disabled = false; panel.hidden = true;
        pageWindow.updateComposerSubmitState?.();
    };
    const begin = async () => {
        clear();
        if (!pageWindow.MediaRecorder || !pageWindow.navigator.mediaDevices?.getUserMedia) {
            pageWindow.showThreadsToast?.('Trình duyệt chưa hỗ trợ ghi âm. Hãy dùng HTTPS hoặc localhost với trình duyệt mới.'); return;
        }
        if (!pageWindow.threadMediaComposer?.available()) { pageWindow.showThreadsToast?.('Gỡ bình chọn hoặc chừa một chỗ đính kèm trước khi ghi âm.'); return; }
        const current = generation;
        panel.hidden = false; button.disabled = true; modal.dataset.recording = '1';
        status.textContent = 'Đang xin quyền micro…'; time.textContent = '0:00 / 1:00';
        stopButton.hidden = true; attachButton.hidden = true; againButton.hidden = true;
        pageWindow.updateComposerSubmitState?.();
        try {
            const granted = await pageWindow.navigator.mediaDevices.getUserMedia({ audio: {
                channelCount: { ideal: 1 }, sampleRate: { ideal: 48000 },
                autoGainControl: false, noiseSuppression: false, echoCancellation: false,
            } });
            if (current !== generation) { granted.getTracks().forEach(track => track.stop()); return; }
            stream = granted;
            const mimeType = recordingMime(pageWindow.MediaRecorder);
            recorder = new pageWindow.MediaRecorder(stream, { ...(mimeType ? { mimeType } : {}), audioBitsPerSecond: 128000 });
            const chunks = [], started = pageWindow.performance.now();
            recorder.ondataavailable = event => { if (event.data.size) chunks.push(event.data); };
            recorder.onstop = () => {
                if (current !== generation) return;
                duration = Math.min(60, (pageWindow.performance.now() - started) / 1000);
                const type = recorder.mimeType || mimeType || 'audio/webm';
                blob = new Blob(chunks, { type }); stopTracks();
                delete modal.dataset.recording; pageWindow.updateComposerSubmitState?.();
                stopButton.hidden = true; againButton.hidden = false;
                if (duration < 0.3 || !blob.size) { status.textContent = 'Ghi âm quá ngắn. Hãy ghi lại.'; return; }
                objectUrl = pageWindow.URL.createObjectURL(blob); preview.src = objectUrl; preview.hidden = false;
                attachButton.hidden = false; status.textContent = 'Nghe lại trước khi đính kèm';
            };
            recorder.onerror = () => { clear(); panel.hidden = false; status.textContent = 'Không ghi được âm thanh. Hãy thử lại.'; againButton.hidden = false; };
            const AudioContext = pageWindow.AudioContext || pageWindow.webkitAudioContext;
            let analyser, buffer;
            if (AudioContext) {
                context = new AudioContext(); analyser = context.createAnalyser(); analyser.fftSize = 256;
                context.createMediaStreamSource(stream).connect(analyser); buffer = new Uint8Array(analyser.fftSize);
            }
            let lastPaint = 0;
            const tick = () => {
                if (current !== generation || recorder.state !== 'recording') return;
                frame = pageWindow.requestAnimationFrame(tick);
                const now = pageWindow.performance.now();
                if (now - lastPaint < 50) return;
                lastPaint = now;
                const elapsed = Math.min(60, (pageWindow.performance.now() - started) / 1000);
                const clock = `${Math.floor(elapsed / 60)}:${String(Math.floor(elapsed % 60)).padStart(2, '0')} / 1:00`;
                if (time.textContent !== clock) time.textContent = clock;
                let amplitude = 0.08;
                if (analyser) { analyser.getByteTimeDomainData(buffer); amplitude = Math.min(1, Math.sqrt(buffer.reduce((sum, value) => sum + ((value - 128) / 128) ** 2, 0) / buffer.length) * 4); }
                peaks.push(amplitude);
                [...wave.children].forEach((bar, index) => { bar.style.height = `${Math.max(8, (peaks[peaks.length - 32 + index] || 0) * 100)}%`; });
            };
            recorder.start(1000); status.textContent = 'Đang ghi âm'; stopButton.hidden = false; tick();
            timeout = pageWindow.setTimeout(() => { if (recorder?.state === 'recording') recorder.stop(); }, 60000);
        } catch (error) {
            if (current !== generation) return;
            clear(); panel.hidden = false; againButton.hidden = false;
            status.textContent = error.name === 'NotAllowedError' ? 'Chưa được cấp quyền micro. Cho phép micro rồi nhấn Ghi lại.' : 'Không tìm thấy micro hoặc micro đang bận. Hãy thử lại.';
        }
    };
    button.addEventListener('click', begin); againButton.addEventListener('click', begin);
    stopButton.addEventListener('click', () => { if (recorder?.state === 'recording') recorder.stop(); });
    panel.querySelector('[data-record-cancel]').addEventListener('click', clear);
    attachButton.addEventListener('click', async () => {
        if (!blob || upload) return;
        const current = generation;
        upload = new pageWindow.AbortController(); attachButton.disabled = true; againButton.disabled = true;
        modal.dataset.recording = 'uploading'; pageWindow.updateComposerSubmitState?.();
        status.textContent = 'Đang tải ghi âm…';
        const data = new FormData();
        const extension = blob.type.includes('mp4') ? 'm4a' : blob.type.includes('ogg') ? 'ogg' : 'webm';
        data.append('file', blob, `ghi-am.${extension}`); data.append('kind', 'audio'); data.append('duration', duration);
        waveformPeaks(peaks).forEach(value => data.append('waveform[]', value));
        try {
            const response = await pageWindow.fetch(modal.dataset.uploadUrl, { method: 'POST', body: data, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': doc.querySelector('meta[name="csrf-token"]').content }, signal: upload.signal });
            const media = await response.json();
            if (!response.ok) throw new Error(media.message || 'Không tải được ghi âm.');
            if (current !== generation) return;
            pageWindow.threadMediaComposer.add(media); clear();
        } catch (error) { if (current === generation) status.textContent = error.message || 'Không tải được ghi âm. Hãy thử lại.'; }
        finally { if (current === generation) { upload = null; delete modal.dataset.recording; pageWindow.updateComposerSubmitState?.(); } attachButton.disabled = false; againButton.disabled = false; }
    });
    modal.addEventListener('composer:reset', clear);
    modal.addEventListener('hide.bs.modal', clear);
    pageWindow.addEventListener('pagehide', clear);
}
