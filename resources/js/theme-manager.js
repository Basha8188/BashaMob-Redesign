const THEMES = ['default-violet', 'forest-moss', 'cocoa-dust', 'twilight-blue', 'moonstone'];
const MODES = ['light', 'dark', 'system'];
const root = document.documentElement;
const systemDark = window.matchMedia('(prefers-color-scheme: dark)');
let saveTimer;

const validTheme = value => THEMES.includes(value) ? value : 'default-violet';
const validMode = value => MODES.includes(value) ? value : 'dark';
const resolvedMode = mode => mode === 'system' ? (systemDark.matches ? 'dark' : 'light') : mode;

const syncControls = () => {
    document.querySelectorAll('[data-palette]').forEach(button => {
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

const initializeThemeControls = () => {
    applyPalette(root.dataset.themeName || root.dataset.palette, false);
    applyMode(root.dataset.mode || root.dataset.theme, false);

    document.querySelectorAll('.theme-picker').forEach(picker => {
        const toggle = picker.querySelector('.theme-picker-toggle');
        toggle?.addEventListener('click', event => {
            event.stopPropagation();
            const open = picker.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(open));
        });
        picker.addEventListener('click', event => {
            const option = event.target.closest('[data-palette]');
            if (!option) return;
            applyPalette(option.dataset.palette);
            picker.classList.remove('is-open');
            toggle?.setAttribute('aria-expanded', 'false');
        });
    });

    document.querySelectorAll('.theme-toggle').forEach(toggle => {
        toggle.addEventListener('click', () => applyMode(root.dataset.theme === 'dark' ? 'light' : 'dark'));
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.theme-picker.is-open').forEach(picker => {
            picker.classList.remove('is-open');
            picker.querySelector('.theme-picker-toggle')?.setAttribute('aria-expanded', 'false');
        });
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
