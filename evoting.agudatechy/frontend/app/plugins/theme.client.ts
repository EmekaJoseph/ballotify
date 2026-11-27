/** Keeps the theme in sync with the OS setting while "System" is selected. */
export default defineNuxtPlugin(() => {
    const theme = useTheme();
    theme.init();
    window.matchMedia?.('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (theme.pref.value === 'system') theme.init();
    });
});
