import test from 'node:test';
import assert from 'node:assert/strict';
import { mediaTime, galleryIndex, galleryHeight, inspectClip, loadVoiceBlob, mediaIcon, setupMediaDrag } from '../../resources/js/thread-media.js';
import { waveformPeaks, recordingMime } from '../../resources/js/voice-recorder.js';
import { pollPercent } from '../../resources/js/polls.js';

test('mixed photo ratios share a height while showing two images and part of the third', () => {
    const ratios = [0.75, 4 / 3, 0.75];
    const height = galleryHeight(560, ratios);
    assert.ok(Math.abs(height * (ratios[0] + ratios[1] + ratios[2] * 0.35) + 16 - 560) < 0.001);
    assert.ok(height < 340);
    const landscape = galleryHeight(560, [16 / 9, 16 / 9, 16 / 9]);
    assert.ok(landscape < height);
});

test('two photos fit the row and extreme panoramas remain inside its width', () => {
    const height = galleryHeight(560, [16 / 9, 1]);
    assert.ok(Math.abs(height * (16 / 9 + 1) + 8 - 560) < 0.001);
    assert.ok(galleryHeight(560, [8, 1, 1]) * 8 <= 560 * 0.95);
    assert.ok(Number.isFinite(galleryHeight(560, [NaN, 0])));
    assert.equal(galleryHeight(0, [1, 1]), 240);
});

test('gallery wraps in both directions and single photos remain at index zero', () => {
    assert.equal(galleryIndex(3, 1, 4), 0);
    assert.equal(galleryIndex(0, -1, 4), 3);
    assert.equal(galleryIndex(0, 1, 1), 0);
});

test('recording selects only a supported browser container including Safari MP4', () => {
    assert.equal(recordingMime({ isTypeSupported: type => type === 'audio/mp4' }), 'audio/mp4');
    assert.equal(recordingMime({ isTypeSupported: () => false }), undefined);
});

test('recorded waveform preserves quiet and loud segments with bounded bar heights', () => {
    const bars = waveformPeaks([0, 0.2, 0.5, 1], 4);
    assert.deepEqual(bars, [8, 20, 50, 100]);
    assert.deepEqual(waveformPeaks([], 4), [8, 8, 8, 8]);
});

test('media clocks and percentages handle unknown duration and empty polls', () => {
    assert.equal(mediaTime(Infinity), '0:00');
    assert.equal(mediaTime(60.2), '1:00');
    assert.equal(pollPercent(1, 3), 33);
    assert.equal(pollPercent(0, 0), 0);
    assert.equal(pollPercent(100, 1), 100);
});

test('clip inspection rejects long or unreadable media and releases every object URL', async () => {
    for (const duration of [12, 61, Infinity]) {
        let released = false, unloaded = false;
        const video = { duration, removeAttribute() { unloaded = true; }, load() {}, set src(value) { queueMicrotask(() => this.onloadedmetadata()); } };
        const result = inspectClip({}, { createElement: () => video }, { URL: { createObjectURL: () => 'blob:clip', revokeObjectURL() { released = true; } } });
        if (duration === 12) assert.equal(await result, 12);
        else await assert.rejects(result, /60 giây/);
        assert.equal(released, true);
        assert.equal(unloaded, true);
    }
});

test('voice playback waits for a complete valid audio body in the current session', async () => {
    const clip = new Blob(['recorded audio'], { type: 'audio/webm' });
    let downloaded = false;
    const pageWindow = { fetch: async (url, options) => {
        assert.equal(url, 'http://localhost:8000/media/1');
        assert.equal(options.credentials, 'same-origin');
        return { ok: true, blob: async () => { downloaded = true; return clip; } };
    } };
    assert.equal(await loadVoiceBlob('http://localhost:8000/media/1', pageWindow), clip);
    assert.equal(downloaded, true);
    await assert.rejects(loadVoiceBlob('/media/1', { fetch: async () => ({ ok: true, blob: async () => new Blob(['Login'], { type: 'text/html' }) }) }), /không hợp lệ/);
    await assert.rejects(loadVoiceBlob('/media/1', { fetch: async () => ({ ok: false }) }), /Không tải được/);
});

test('video icons expose consistent SVG paths for each playback state', () => {
    for (const name of ['play', 'pause', 'muted', 'volume', 'fullscreen', 'shrink']) {
        assert.match(mediaIcon(name), /<svg/);
        assert.match(mediaIcon(name), /aria-hidden="true"/);
    }
    assert.notEqual(mediaIcon('muted'), mediaIcon('volume'));
    assert.notEqual(mediaIcon('fullscreen'), mediaIcon('shrink'));
});

function dragFixture({ reducedMotion = false } = {}) {
    const listeners = new Map(), classes = new Set(), frames = new Map();
    let time = 0, frameId = 0, scroll = 0;
    const strip = {
        scrollWidth: 900, clientWidth: 300, isConnected: true, style: { scrollBehavior: '' }, dataset: {},
        get scrollLeft() { return scroll; },
        set scrollLeft(value) { scroll = Math.max(0, Math.min(this.scrollWidth - this.clientWidth, value)); },
        classList: { add: name => classes.add(name), remove: name => classes.delete(name) },
        setPointerCapture(id) { this.capture = id; }, hasPointerCapture(id) { return this.capture === id; }, releasePointerCapture() { this.capture = null; },
        contains: target => target === image,
    };
    const image = { closest: selector => selector === '[data-media-gallery]' ? strip : null };
    const controls = { closest: selector => selector === '[data-media-gallery]' ? strip : {} };
    const doc = { addEventListener: (name, handler) => listeners.set(name, handler) };
    setupMediaDrag(doc, {
        addEventListener: (name, handler) => listeners.set(name, handler),
        performance: { now: () => time }, matchMedia: () => ({ matches: reducedMotion }),
        requestAnimationFrame: handler => { frames.set(++frameId, handler); return frameId; },
        cancelAnimationFrame: id => frames.delete(id),
    });
    const send = (type, extra = {}) => {
        const event = { type, target: image, button: 0, pointerId: 1, pointerType: 'mouse', clientX: 250, clientY: 100, prevented: false,
            preventDefault() { this.prevented = true; }, stopImmediatePropagation() { this.stopped = true; }, ...extra };
        listeners.get(type)(event); return event;
    };
    const advance = milliseconds => { time += milliseconds; };
    const frame = (milliseconds = 1000 / 60) => {
        advance(milliseconds);
        const callbacks = [...frames.values()]; frames.clear();
        callbacks.forEach(callback => callback(time));
    };
    return { strip, image, controls, classes, send, advance, frame, frames };
}

test('dragging attachments scrolls one to one and suppresses only the release click', () => {
    const ui = dragFixture();
    ui.send('pointerdown'); ui.send('pointermove', { clientX: 130 });
    assert.equal(ui.strip.scrollLeft, 120);
    assert.equal(ui.classes.has('is-dragging'), true);
    ui.send('pointerup');
    assert.equal(ui.classes.has('is-dragging'), false);
    assert.equal(ui.send('click').stopped, true);
    ui.send('pointerdown'); ui.send('pointerup');
    assert.equal(ui.send('click').prevented, false);
});

function flick(ui) {
    ui.send('pointerdown');
    ui.advance(20); ui.send('pointermove', { clientX: 220 });
    ui.advance(20); ui.send('pointermove', { clientX: 180 });
    ui.advance(20); ui.send('pointermove', { clientX: 130 });
    ui.advance(8); ui.send('pointerup');
}

test('a flick coasts after release, slows gradually and stops between photos', () => {
    const ui = dragFixture(); flick(ui);
    assert.equal(ui.strip.dataset.mediaInteracting, '1');
    const released = ui.strip.scrollLeft;
    ui.frame(); const firstStep = ui.strip.scrollLeft - released;
    const beforeSecond = ui.strip.scrollLeft;
    ui.frame(); const secondStep = ui.strip.scrollLeft - beforeSecond;
    assert.ok(firstStep > secondStep && secondStep > 0);
    for (let i = 0; i < 180; i++) ui.frame();
    assert.ok(ui.strip.scrollLeft > released + 300 && ui.strip.scrollLeft < 590);
    assert.equal(ui.frames.size, 0);
    assert.equal(ui.strip.dataset.mediaInteracting, undefined);
    assert.equal(ui.strip.style.scrollBehavior, '');
});

test('coasting travels the same distance on 60 Hz and 120 Hz screens', () => {
    const distances = [60, 120].map(rate => {
        const ui = dragFixture(); flick(ui);
        for (let i = 0; i < rate * 3; i++) ui.frame(1000 / rate);
        return ui.strip.scrollLeft;
    });
    assert.ok(Math.abs(distances[0] - distances[1]) < 1);
});

test('coasting yields immediately to new gestures, wheel input and page dismissal', () => {
    for (const event of ['pointerdown', 'wheel', 'blur', 'pagehide']) {
        const ui = dragFixture(); flick(ui); ui.frame();
        const position = ui.strip.scrollLeft;
        ui.send(event, { target: ui.controls });
        ui.frame();
        assert.equal(ui.strip.scrollLeft, position);
        assert.equal(ui.frames.size, 0);
    }
    const ui = dragFixture(); flick(ui);
    ui.strip.isConnected = false; ui.frame();
    assert.equal(ui.frames.size, 0);
});

test('reduced motion, a held release and pointer cancellation do not coast', () => {
    const reduced = dragFixture({ reducedMotion: true }); flick(reduced);
    assert.equal(reduced.frames.size, 0);
    for (const event of ['pointerup', 'pointercancel', 'lostpointercapture']) {
        const ui = dragFixture(); ui.send('pointerdown');
        ui.advance(20); ui.send('pointermove', { clientX: 150 });
        if (event === 'pointerup') ui.advance(150);
        ui.send(event);
        assert.equal(ui.frames.size, 0);
        assert.equal(ui.strip.style.scrollBehavior, '');
    }
});

test('coasting stops at the boundary and does not suppress keyboard photo activation', () => {
    const ui = dragFixture(); ui.strip.scrollLeft = 550; flick(ui);
    ui.frame();
    assert.equal(ui.strip.scrollLeft, 600);
    assert.equal(ui.frames.size, 0);
    assert.equal(ui.send('click', { detail: 0 }).prevented, false);
});

test('tiny clicks, touch swipes and playback controls retain native behavior', () => {
    const ui = dragFixture();
    ui.send('pointerdown'); ui.send('pointermove', { clientX: 248 }); ui.send('pointerup');
    assert.equal(ui.strip.scrollLeft, 0);
    assert.equal(ui.send('click').prevented, false);
    ui.send('pointerdown', { pointerType: 'touch' }); ui.send('pointermove', { pointerType: 'touch', clientX: 100 }); ui.send('pointerup');
    assert.equal(ui.strip.scrollLeft, 0);
    ui.send('pointerdown', { target: ui.controls }); ui.send('pointermove', { target: ui.controls, clientX: 100 }); ui.send('pointerup');
    assert.equal(ui.strip.scrollLeft, 0);
});
