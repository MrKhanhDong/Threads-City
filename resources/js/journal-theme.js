export function setupJournalTheme(pageDocument = document, pageWindow = window) {
    if (!pageDocument.body?.classList.contains('journal-body')) return;

    const root = pageDocument.documentElement;
    const choices = pageDocument.querySelectorAll('[data-theme-choice]');
    const toggles = pageDocument.querySelectorAll('[data-theme-toggle]');
    const systemTheme = pageWindow.matchMedia('(prefers-color-scheme: dark)');
    const validPreference = value => ['light', 'dark', 'auto'].includes(value) ? value : 'dark';
    let preference = validPreference(root.dataset.themePreference ?? root.dataset.theme);

    const applyTheme = value => {
        preference = validPreference(value);
        const theme = preference === 'auto' ? (systemTheme.matches ? 'dark' : 'light') : preference;
        root.dataset.themePreference = preference;
        root.dataset.theme = theme;
        root.dataset.bsTheme = theme;
        choices.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.themeChoice === preference)));
        toggles.forEach(button => {
            button.setAttribute('aria-pressed', String(theme === 'light'));
            button.setAttribute('aria-label', `Chuyển sang giao diện ${theme === 'light' ? 'tối' : 'sáng'}`);
        });
    };

    const chooseTheme = value => {
        applyTheme(value);
        try { pageWindow.localStorage.setItem('journal-theme', preference); } catch {}
    };
    applyTheme(preference);
    choices.forEach(button => button.addEventListener('click', () => chooseTheme(button.dataset.themeChoice)));
    toggles.forEach(button => button.addEventListener('click', () => chooseTheme(root.dataset.theme === 'dark' ? 'light' : 'dark')));
    systemTheme.addEventListener('change', () => {
        if (preference === 'auto') applyTheme('auto');
    });
    pageWindow.addEventListener('storage', event => {
        if (event.key === 'journal-theme' || event.key === null) applyTheme(event.key === null ? 'dark' : event.newValue);
    });
    pageDocument.querySelectorAll('[data-site-menu]').forEach(menu => {
        const mainPanel = menu.querySelector('[data-menu-main]');
        const themePanel = menu.querySelector('[data-menu-theme]');
        const appearanceButton = menu.querySelector('[data-menu-appearance]');
        const showThemePanel = show => {
            mainPanel.hidden = show;
            themePanel.hidden = !show;
            appearanceButton.setAttribute('aria-expanded', String(show));
            if (show) themePanel.querySelector(`[data-theme-choice="${preference}"]`)?.focus();
        };
        appearanceButton.addEventListener('click', () => showThemePanel(true));
        menu.querySelector('[data-menu-back]').addEventListener('click', () => {
            showThemePanel(false);
            appearanceButton.focus();
        });
        menu.addEventListener('hidden.bs.dropdown', () => showThemePanel(false));
    });
}
