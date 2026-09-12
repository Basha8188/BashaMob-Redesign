// Keep the shared entry point; there is no palette, mode, persistence or OS listener.
const applyUnifiedTheme = () => {
    const root = document.documentElement;
    root.dataset.themeName = 'premium-glass';
    root.dataset.theme = 'unified';
    root.removeAttribute('data-palette');
    root.removeAttribute('data-mode');
    root.classList.remove('dark', 'theme-picker-open');
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', '#2e3170');
};
applyUnifiedTheme();

// Compatibility exports for existing consumers; both always apply the fixed identity.
export { applyUnifiedTheme as applyMode, applyUnifiedTheme as applyPalette };
