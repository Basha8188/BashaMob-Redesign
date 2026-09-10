const THEMES = ['liquid-cosmos', 'default-violet', 'lavender', 'plum', 'forest-moss', 'cocoa-dust', 'twilight-blue', 'moonstone'];
const MODES = ['light', 'dark', 'system'];
const root = document.documentElement;
const systemDark = window.matchMedia('(prefers-color-scheme: dark)');
const paletteOptionSelector = '.theme-palette button[data-palette]';
let saveTimer;

const validTheme = value => THEMES.includes(value) ? value : 'liquid-cosmos';
const validMode = value => MODES.includes(value) ? value : 'dark';
const resolvedMode = mode => mode === 'system' ? (systemDark.matches ? 'dark' : 'light') : mode;

const syncControls = () => {
    document.querySelectorAll(paletteOptionSelector).forEach(button => {
        button.setAttribute('aria-pressed', String(button.dataset.palette === root.dataset.themeName));
    });
    document.querySelectorAll('.theme-toggle').forEach(button => {
        button.setAttribute('aria-pressed', String(root.dataset.theme === 'dark'));
        button.setAttribute('title', root.dataset.theme === 'dark' ? 'تفعيل الوضع الفاتح' : 'تفعيل الوضع الداكن');
    });
};

const savePreference = () => {
    const endpoint = document.querySelector('meta[name="theme-preference-url"]')?.content;
    if (!endpoint) return;

    window.clearTimeout(saveTimer);
    saveTimer = window.setTimeout(() => {
        fetch(endpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                theme_name: root.dataset.themeName,
                theme_mode: root.dataset.mode,
                use_system_mode: root.dataset.mode === 'system',
            }),
        }).catch(() => {});
    }, 260);
};

const notifyThemeChanged = () => {
    window.dispatchEvent(new CustomEvent('bashamob:theme-changed', {
        detail: { theme: root.dataset.themeName, mode: root.dataset.mode, resolvedMode: root.dataset.theme },
    }));
};

const applyPalette = (theme, persist = true) => {
    root.dataset.themeName = validTheme(theme);
    root.dataset.palette = root.dataset.themeName === 'default-violet' ? 'violet' : root.dataset.themeName;
    if (persist) {
        localStorage.setItem('basha-palette', root.dataset.themeName);
        savePreference();
    }
    syncControls();
    notifyThemeChanged();
};

const applyMode = (mode, persist = true) => {
    root.dataset.mode = validMode(mode);
    root.dataset.theme = resolvedMode(root.dataset.mode);
    if (persist) {
        localStorage.setItem('basha-theme-mode', root.dataset.mode);
        localStorage.setItem('basha-theme', root.dataset.mode);
        savePreference();
    }
    const themeColor = getComputedStyle(root).getPropertyValue('--theme-bg').trim()
        || (root.dataset.theme === 'dark' ? '#06030d' : '#f5f1fa');
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', themeColor);
    syncControls();
    notifyThemeChanged();
};

const closePicker = picker => {
    if (!picker) return;
    picker.classList.remove('is-open');
    picker.querySelector('.theme-picker-toggle')?.setAttribute('aria-expanded', 'false');

    if (!document.querySelector('.theme-picker.is-open')) {
        root.classList.remove('theme-picker-open');
    }
};

const closeAllPickers = except => {
    document.querySelectorAll('.theme-picker.is-open').forEach(picker => {
        if (picker !== except) closePicker(picker);
    });
};

const initializeThemeControls = () => {
    if (root.dataset.themeControlsReady === 'true') {
        syncControls();
        return;
    }
    root.dataset.themeControlsReady = 'true';

    applyPalette(root.dataset.themeName || root.dataset.palette, false);
    applyMode(root.dataset.mode || root.dataset.theme, false);

    /*
     * Delegate from the document so the picker keeps working in every staff,
     * storefront and public view, including controls rendered after page load.
     */
    document.addEventListener('click', event => {
        const target = event.target instanceof Element ? event.target : null;
        const toggle = target?.closest('.theme-picker-toggle');

        if (toggle) {
            const picker = toggle.closest('.theme-picker');
            if (!picker) return;
            event.preventDefault();
            event.stopPropagation();
            closeAllPickers(picker);
            const open = picker.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(open));
            root.classList.toggle('theme-picker-open', open);
            return;
        }

        const option = target?.closest(paletteOptionSelector);
        if (option) {
            event.preventDefault();
            event.stopPropagation();
            applyPalette(option.dataset.palette);
            const picker = option.closest('.theme-picker');
            closePicker(picker);
            picker?.querySelector('.theme-picker-toggle')?.focus({ preventScroll: true });
            return;
        }

        closeAllPickers();
    });

    document.querySelectorAll('.theme-toggle').forEach(toggle => {
        toggle.addEventListener('click', () => applyMode(root.dataset.theme === 'dark' ? 'light' : 'dark'));
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        const openPicker = document.querySelector('.theme-picker.is-open');
        if (!openPicker) return;
        closePicker(openPicker);
        openPicker.querySelector('.theme-picker-toggle')?.focus({ preventScroll: true });
    });
};

systemDark.addEventListener('change', () => {
    if (root.dataset.mode === 'system') applyMode('system', false);
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeThemeControls, { once: true });
} else {
    initializeThemeControls();
}

export { applyMode, applyPalette };
