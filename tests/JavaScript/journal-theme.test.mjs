import test from 'node:test';
import assert from 'node:assert/strict';
import { setupJournalTheme } from '../../resources/js/journal-theme.js';

function fixture(preference = 'dark', prefersDark = false, denyStorage = false, bodyClass = 'journal-body') {
    const control = dataset => Object.assign(new EventTarget(), {
        dataset, attributes: {}, focused: false,
        setAttribute(name, value) { this.attributes[name] = value; },
        focus() { this.focused = true; }
    });
    const choices = ['light', 'dark', 'auto', 'light', 'dark', 'auto'].map(themeChoice => control({ themeChoice }));
    const toggle = control({});
    const appearance = control({});
    const back = control({});
    const mainPanel = { hidden: false };
    const themePanel = { hidden: true, querySelector: selector => choices.find(button => selector.includes(`"${button.dataset.themeChoice}"`)) };
    const menu = Object.assign(new EventTarget(), { querySelector: selector => ({ '[data-menu-main]': mainPanel, '[data-menu-theme]': themePanel, '[data-menu-appearance]': appearance, '[data-menu-back]': back })[selector] });
    const root = { dataset: bodyClass === 'journal-body' ? { theme: 'dark', themePreference: preference } : { bsTheme: 'light' } };
    const pageDocument = { body: { classList: { contains: name => name === bodyClass } }, documentElement: root, querySelectorAll: selector => ({ '[data-theme-choice]': choices, '[data-theme-toggle]': [toggle], '[data-site-menu]': [menu] })[selector] };
    const media = Object.assign(new EventTarget(), { matches: prefersDark });
    const saved = new Map();
    const pageWindow = Object.assign(new EventTarget(), {
        matchMedia: () => media,
        localStorage: { setItem(key, value) { if (denyStorage) throw new Error('Storage blocked'); saved.set(key, value); } }
    });
    setupJournalTheme(pageDocument, pageWindow);
    const click = button => button.dispatchEvent(new Event('click'));
    const choose = mode => click(choices.find(button => button.dataset.themeChoice === mode));
    return { root, media, pageWindow, choices, saved, toggle, appearance, back, mainPanel, themePanel, menu, click, choose };
}

test('admin pages stay light when the public theme or system preference changes', () => {
    const ui = fixture('dark', true, false, 'admin-body');
    assert.deepEqual(ui.root.dataset, { bsTheme: 'light' });

    ui.pageWindow.dispatchEvent(Object.assign(new Event('storage'), { key: 'journal-theme', newValue: 'dark' }));
    ui.pageWindow.dispatchEvent(Object.assign(new Event('storage'), { key: null, newValue: null }));
    ui.media.dispatchEvent(new Event('change'));

    assert.deepEqual(ui.root.dataset, { bsTheme: 'light' });
    assert.equal(ui.saved.size, 0);
});

test('explicit choices update both menus, Bootstrap theme and persistent preference', () => {
    const ui = fixture();
    ui.choose('light');
    assert.equal(ui.root.dataset.theme, 'light');
    assert.equal(ui.root.dataset.bsTheme, 'light');
    assert.equal(ui.saved.get('journal-theme'), 'light');
    assert.equal(ui.choices.filter(button => button.attributes['aria-pressed'] === 'true').length, 2);
    ui.choose('dark');
    assert.equal(ui.root.dataset.theme, 'dark');
    assert.equal(ui.saved.get('journal-theme'), 'dark');
});

test('automatic preference follows system changes until an explicit mode is chosen', () => {
    const ui = fixture('auto', false);
    assert.equal(ui.root.dataset.theme, 'light');
    ui.media.matches = true;
    ui.media.dispatchEvent(new Event('change'));
    assert.equal(ui.root.dataset.theme, 'dark');
    assert.equal(ui.root.dataset.themePreference, 'auto');
    ui.choose('light');
    ui.media.dispatchEvent(new Event('change'));
    assert.equal(ui.root.dataset.theme, 'light');
    ui.choose('auto');
    assert.equal(ui.saved.get('journal-theme'), 'auto');
    assert.equal(ui.root.dataset.theme, 'dark');
});

test('another tabs preference and clearing storage synchronize without rewriting storage', () => {
    const ui = fixture();
    ui.pageWindow.dispatchEvent(Object.assign(new Event('storage'), { key: 'journal-theme', newValue: 'auto' }));
    assert.equal(ui.root.dataset.themePreference, 'auto');
    assert.equal(ui.root.dataset.theme, 'light');
    ui.pageWindow.dispatchEvent(Object.assign(new Event('storage'), { key: null, newValue: null }));
    assert.equal(ui.root.dataset.theme, 'dark');
    assert.equal(ui.saved.size, 0);
});

test('blocked storage still allows theme selection and the existing shortcut', () => {
    const ui = fixture('dark', false, true);
    ui.choose('light');
    assert.equal(ui.root.dataset.theme, 'light');
    ui.click(ui.toggle);
    assert.equal(ui.root.dataset.theme, 'dark');
    assert.equal(ui.saved.size, 0);
});

test('appearance view supports back navigation, focuses selection and resets when dismissed', () => {
    const ui = fixture();
    ui.click(ui.appearance);
    assert.equal(ui.mainPanel.hidden, true);
    assert.equal(ui.themePanel.hidden, false);
    assert.equal(ui.choices.find(button => button.dataset.themeChoice === 'dark').focused, true);
    ui.click(ui.back);
    assert.equal(ui.mainPanel.hidden, false);
    assert.equal(ui.themePanel.hidden, true);
    assert.equal(ui.appearance.focused, true);
    ui.click(ui.appearance);
    ui.menu.dispatchEvent(new Event('hidden.bs.dropdown'));
    assert.equal(ui.mainPanel.hidden, false);
    assert.equal(ui.appearance.attributes['aria-expanded'], 'false');
});
